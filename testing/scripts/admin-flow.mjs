import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';
import dotenv from 'dotenv';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const testingRoot = path.resolve(__dirname, '..');
const reportsRoot = path.join(testingRoot, 'reports', 'latest');
const configPath = path.join(testingRoot, 'config', 'site.json');

dotenv.config({ path: path.join(testingRoot, '.env') });

const args = parseArgs(process.argv.slice(2));
const siteConfig = JSON.parse(fs.readFileSync(configPath, 'utf8'));
const baseUrl = normalizeBaseUrl(process.env.SITE_URL || args.url || siteConfig.baseUrl);
const headed = args.headed === true || String(process.env.HEADED || '').toLowerCase() === 'true';
const adminEmail = process.env.ADMIN_EMAIL;
const adminPassword = process.env.ADMIN_PASSWORD;
const processOrderId = process.env.ADMIN_ORDER_PROCESS_ID || '';
const cancelOrderId = process.env.ADMIN_ORDER_CANCEL_ID || '';

if (!adminEmail || !adminPassword) {
  console.error('ADMIN_EMAIL and ADMIN_PASSWORD are required. Copy .env.example to .env and fill them in.');
  process.exit(1);
}

fs.mkdirSync(reportsRoot, { recursive: true });

const report = {
  generatedAt: new Date().toISOString(),
  baseUrl,
  steps: []
};

let browser;
let adminContext;
let adminPage;

const runtime = {
  createdProductName: '',
  editedProductName: '',
  createdUserEmail: ''
};

try {
  browser = await chromium.launch({ headless: !headed });
  adminContext = await browser.newContext({ ignoreHTTPSErrors: true });
  adminPage = await adminContext.newPage();
  globalThis.pageForScreenshots = adminPage;
  adminPage.on('dialog', async (dialog) => {
    await dialog.accept();
  });

  await recordStep(report, 'Admin login', async () => {
    await loginAsAdmin(adminPage, baseUrl, adminEmail, adminPassword);
    await expectPath(adminPage, '/admin/index.php');
    return {
      finalUrl: adminPage.url()
    };
  });

  await recordStep(report, 'Overview page', async () => {
    await goToAdminPage(adminPage, baseUrl, '/admin/index.php', 'Dashboard Overview');
  });

  await recordStep(report, 'Products page', async () => {
    await goToAdminPage(adminPage, baseUrl, '/admin/products.php', 'Product Management');
  });

  await recordStep(report, 'Inventory page', async () => {
    await goToAdminPage(adminPage, baseUrl, '/admin/inventory.php', 'Inventory Management');
  });

  await recordStep(report, 'Orders page', async () => {
    await goToAdminPage(adminPage, baseUrl, '/admin/orders.php', 'Order Management');
  });

  await recordStep(report, 'Users page', async () => {
    await goToAdminPage(adminPage, baseUrl, '/admin/manage_users.php', 'User Management');
  });

  await recordStep(report, 'Product add/edit/delete flow', async () => {
    const timestamp = Date.now();
    const productName = `Playwright Test Product ${timestamp}`;
    const editedName = `${productName} Edited`;
    runtime.createdProductName = productName;
    runtime.editedProductName = editedName;

    await ensurePageOk(adminPage, toAbsoluteUrl(baseUrl, '/admin/products.php'));

    await adminPage.getByRole('button', { name: /add product/i }).click();
    await adminPage.locator('#addProductModal').waitFor({ state: 'visible' });

    await adminPage.locator('#add-name').fill(productName);
    await adminPage.locator('#add-model').fill(`PW-${timestamp}`);
    await adminPage.locator('#add-category').fill('Playwright Testing');
    await adminPage.locator('#add-description').fill('Disposable product created by the admin Playwright flow.');
    await adminPage.locator('#add-price').fill('123.45');
    await adminPage.locator('#add-old_price').fill('150.00');
    await adminPage.locator('#add-stock').fill('5');
    await adminPage.locator('#add-image_url').fill('assets/logo.png');
    await adminPage.locator('#add-badge').selectOption('New');

    await Promise.all([
      adminPage.waitForURL(/\/admin\/products\.php/),
      adminPage.locator('#addProductModal form').getByRole('button', { name: /add product/i }).click()
    ]);

    await expectFlash(adminPage, /added successfully/i);
    await searchProducts(adminPage, productName);
    await expectProductRow(adminPage, productName);

    const row = await getProductRow(adminPage, productName);
    await row.getByRole('button', { name: /edit/i }).click();
    await adminPage.locator('#editProductModal').waitFor({ state: 'visible' });

    await adminPage.locator('#edit-name').fill(editedName);
    await adminPage.locator('#edit-description').fill('Edited by the admin Playwright flow.');
    await adminPage.locator('#edit-price').fill('111.11');
    await adminPage.locator('#edit-stock').fill('9');
    await adminPage.locator('#edit-badge').selectOption('Sale');

    await Promise.all([
      adminPage.waitForURL(/\/admin\/products\.php/),
      adminPage.locator('#editProductModal form').getByRole('button', { name: /save changes/i }).click()
    ]);

    await expectFlash(adminPage, /updated successfully/i);
    await searchProducts(adminPage, editedName);
    await expectProductRow(adminPage, editedName);

    await ensurePageOk(
      adminPage,
      `${toAbsoluteUrl(baseUrl, '/admin/inventory.php')}?search=${encodeURIComponent(editedName)}`
    );

    const inventoryRow = await getRowByText(adminPage, 'table tbody tr', editedName);
    const stockInput = inventoryRow.locator('input[name="stock_quantity"]');
    await stockInput.fill('7');
    await Promise.all([
      adminPage.waitForURL(/\/admin\/inventory\.php/),
      inventoryRow.getByRole('button', { name: /save stock/i }).click()
    ]);

    await expectFlash(adminPage, /stock updated/i);
    await ensurePageOk(
      adminPage,
      `${toAbsoluteUrl(baseUrl, '/admin/inventory.php')}?search=${encodeURIComponent(editedName)}`
    );
    const refreshedInventoryRow = await getRowByText(adminPage, 'table tbody tr', editedName);
    const refreshedStockValue = await refreshedInventoryRow.locator('input[name="stock_quantity"]').inputValue();
    if (refreshedStockValue !== '7') {
      throw new Error(`Expected stock quantity 7 after update, got ${refreshedStockValue}.`);
    }

    await searchProducts(adminPage, editedName);
    const deleteRow = await getProductRow(adminPage, editedName);
    await Promise.all([
      adminPage.waitForURL(/\/admin\/products\.php/),
      deleteRow.getByRole('button', { name: /delete/i }).click()
    ]);
    await expectFlash(adminPage, /deleted/i);
    await searchProducts(adminPage, editedName);
    await expectNoRow(adminPage, editedName);

    runtime.createdProductName = '';
    runtime.editedProductName = '';

    return {
      productName,
      editedName
    };
  });

  await recordStep(report, 'User role/deactivate/activate/delete flow', async () => {
    const timestamp = Date.now();
    const userPassword = `PomeTest!${timestamp}`;
    const testUser = {
      firstName: 'Playwright',
      lastName: `User${timestamp}`,
      email: `playwright-user-${timestamp}@example.test`,
      password: userPassword
    };
    runtime.createdUserEmail = testUser.email;

    await createDisposableUser(browser, baseUrl, testUser);

    await adminPage.goto(
      `${toAbsoluteUrl(baseUrl, '/admin/manage_users.php')}?search=${encodeURIComponent(testUser.email)}`,
      { waitUntil: 'domcontentloaded' }
    );
    let userRow = await getUserRow(adminPage, testUser.email);

    const roleSelect = userRow.locator('select[name="new_role"]');
    await roleSelect.selectOption('employee');
    await Promise.all([
      adminPage.waitForNavigation(),
      roleSelect.evaluate((element) => element.form.requestSubmit())
    ]);
    await expectFlash(adminPage, /role updated/i);

    await adminPage.goto(
      `${toAbsoluteUrl(baseUrl, '/admin/manage_users.php')}?search=${encodeURIComponent(testUser.email)}`,
      { waitUntil: 'domcontentloaded' }
    );
    userRow = await getUserRow(adminPage, testUser.email);
    await expectSelectedRole(userRow, 'employee');

    await Promise.all([
      adminPage.waitForURL(/\/admin\/manage_users\.php/),
      userRow.getByRole('button', { name: /deactivate/i }).click()
    ]);
    await expectFlash(adminPage, /account status updated/i);

    await adminPage.goto(
      `${toAbsoluteUrl(baseUrl, '/admin/manage_users.php')}?search=${encodeURIComponent(testUser.email)}`,
      { waitUntil: 'domcontentloaded' }
    );
    userRow = await getUserRow(adminPage, testUser.email);
    await expectStatusBadge(userRow, 'Inactive');

    await Promise.all([
      adminPage.waitForURL(/\/admin\/manage_users\.php/),
      userRow.getByRole('button', { name: /activate/i }).click()
    ]);
    await expectFlash(adminPage, /account status updated/i);

    await adminPage.goto(
      `${toAbsoluteUrl(baseUrl, '/admin/manage_users.php')}?search=${encodeURIComponent(testUser.email)}`,
      { waitUntil: 'domcontentloaded' }
    );
    userRow = await getUserRow(adminPage, testUser.email);
    await expectStatusBadge(userRow, 'Active');

    await Promise.all([
      adminPage.waitForURL(/\/admin\/manage_users\.php/),
      userRow.getByRole('button', { name: /delete/i }).click()
    ]);
    await expectFlash(adminPage, /permanently deleted/i);

    await adminPage.goto(
      `${toAbsoluteUrl(baseUrl, '/admin/manage_users.php')}?search=${encodeURIComponent(testUser.email)}`,
      { waitUntil: 'domcontentloaded' }
    );
    await expectNoRow(adminPage, testUser.email);

    runtime.createdUserEmail = '';

    return {
      testUserEmail: testUser.email
    };
  });

  await recordStep(report, 'Order process flow', async () => {
    if (!processOrderId) {
      return {
        skipped: true,
        reason: 'Set ADMIN_ORDER_PROCESS_ID to run a real order status advance.'
      };
    }

    await adminPage.goto(
      `${toAbsoluteUrl(baseUrl, '/admin/orders.php')}?search=${encodeURIComponent(processOrderId)}`,
      { waitUntil: 'domcontentloaded' }
    );
    const orderRow = await getRowByText(adminPage, 'table tbody tr', `#${processOrderId}`);
    const button = orderRow.getByRole('button', {
      name: /processing|shipped|delivered/i
    }).first();

    if (await button.count() === 0) {
      throw new Error(`Order #${processOrderId} has no forward status button.`);
    }

    const actionLabel = (await button.innerText()).trim();
    await Promise.all([
      adminPage.waitForURL(/\/admin\/orders\.php/),
      button.click()
    ]);
    await expectFlash(adminPage, /updated to/i);
    return {
      orderId: processOrderId,
      action: actionLabel
    };
  });

  await recordStep(report, 'Order cancel flow', async () => {
    if (!cancelOrderId) {
      return {
        skipped: true,
        reason: 'Set ADMIN_ORDER_CANCEL_ID to run a real order cancellation.'
      };
    }

    await adminPage.goto(
      `${toAbsoluteUrl(baseUrl, '/admin/orders.php')}?search=${encodeURIComponent(cancelOrderId)}`,
      { waitUntil: 'domcontentloaded' }
    );
    const orderRow = await getRowByText(adminPage, 'table tbody tr', `#${cancelOrderId}`);
    const cancelButton = orderRow.getByRole('button', { name: /cancel/i }).first();

    if (await cancelButton.count() === 0) {
      throw new Error(`Order #${cancelOrderId} does not expose a cancel button.`);
    }

    await Promise.all([
      adminPage.waitForURL(/\/admin\/orders\.php/),
      cancelButton.click()
    ]);
    await expectFlash(adminPage, /updated to "Cancelled"/i);
    return {
      orderId: cancelOrderId
    };
  });
} catch (error) {
  report.fatalError = serializeError(error);
  process.exitCode = 1;
} finally {
  if (adminPage && runtime.editedProductName) {
    await safeDeleteProduct(adminPage, baseUrl, runtime.editedProductName);
  }
  if (adminPage && runtime.createdProductName) {
    await safeDeleteProduct(adminPage, baseUrl, runtime.createdProductName);
  }
  if (adminPage && runtime.createdUserEmail) {
    await safeDeleteUser(adminPage, baseUrl, runtime.createdUserEmail);
  }

  await adminContext?.close();
  await browser?.close();

  writeJson(path.join(reportsRoot, 'admin-flow.json'), report);
  console.log(JSON.stringify(report, null, 2));
}

async function recordStep(reportObject, name, fn) {
  const startedAt = new Date().toISOString();
  try {
    const details = await fn();
    reportObject.steps.push({
      name,
      status: details?.skipped ? 'skipped' : 'passed',
      startedAt,
      finishedAt: new Date().toISOString(),
      details: details ?? null
    });
  } catch (error) {
    const screenshotPath = path.join(
      reportsRoot,
      `admin-failure-${slugify(name)}.png`
    );

    try {
      const page = globalThis.pageForScreenshots;
      if (page) {
        await page.screenshot({ path: screenshotPath, fullPage: true });
      }
    } catch {
      // Ignore screenshot failures.
    }

    reportObject.steps.push({
      name,
      status: 'failed',
      startedAt,
      finishedAt: new Date().toISOString(),
      error: serializeError(error),
      screenshot: fs.existsSync(screenshotPath)
        ? relativeFromTestingRoot(screenshotPath)
        : null
    });
  }
}

async function loginAsAdmin(page, siteBaseUrl, email, password) {
  globalThis.pageForScreenshots = page;
  await page.goto(toAbsoluteUrl(siteBaseUrl, '/account/login.php'), { waitUntil: 'domcontentloaded' });
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="pwd"]').fill(password);
  await Promise.all([
    page.waitForURL(/\/admin\/index\.php/),
    page.getByRole('button', { name: /log in/i }).click()
  ]);
}

async function createDisposableUser(browserInstance, siteBaseUrl, user) {
  const guestContext = await browserInstance.newContext({ ignoreHTTPSErrors: true });
  const page = await guestContext.newPage();
  try {
    await page.goto(toAbsoluteUrl(siteBaseUrl, '/account/signup.php'), { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="fname"]').fill(user.firstName);
    await page.locator('input[name="lname"]').fill(user.lastName);
    await page.locator('input[name="email"]').fill(user.email);
    await page.locator('input[name="pwd"]').fill(user.password);
    await page.locator('input[name="pwd_confirm"]').fill(user.password);
    await page.locator('input[type="checkbox"]').check();

    await Promise.all([
      page.waitForURL(/\/account\/login\.php/),
      page.getByRole('button', { name: /create account/i }).click()
    ]);

    await expectAlert(page, /registration successful/i);
  } finally {
    await guestContext.close();
  }
}

async function goToAdminPage(page, siteBaseUrl, relativePath, headingText) {
  globalThis.pageForScreenshots = page;
  await ensurePageOk(page, toAbsoluteUrl(siteBaseUrl, relativePath));
  await page.getByRole('heading', { name: new RegExp(escapeRegExp(headingText), 'i') }).waitFor();
}

async function searchProducts(page, productName) {
  await page.goto(
    `${toAbsoluteUrl(baseUrl, '/admin/products.php')}?search=${encodeURIComponent(productName)}`,
    { waitUntil: 'domcontentloaded' }
  );
}

async function getProductRow(page, productName) {
  return getRowByText(page, 'table tbody tr', productName);
}

async function getUserRow(page, email) {
  return getRowByText(page, 'table tbody tr', email);
}

async function getRowByText(page, selector, text) {
  const row = page.locator(selector).filter({ hasText: text }).first();
  await row.waitFor();
  return row;
}

async function expectProductRow(page, productName) {
  const row = page.locator('table tbody tr').filter({ hasText: productName }).first();
  await row.waitFor();
}

async function expectNoRow(page, text) {
  const row = page.locator('table tbody tr').filter({ hasText: text });
  const count = await row.count();
  if (count !== 0) {
    throw new Error(`Expected no row containing "${text}", but found ${count}.`);
  }
}

async function expectRowContains(page, anchorText, contentRegex) {
  const row = await getRowByText(page, 'table tbody tr', anchorText);
  await expectRowContainsLocator(row, contentRegex);
}

async function expectRowContainsLocator(locator, contentRegex) {
  const text = await locator.innerText();
  if (!contentRegex.test(text)) {
    throw new Error(`Row content did not match ${contentRegex}. Actual text: ${text}`);
  }
}

async function expectSelectedRole(rowLocator, expectedValue) {
  const selected = await rowLocator.locator('select[name="new_role"]').inputValue();
  if (selected !== expectedValue) {
    throw new Error(`Expected selected role "${expectedValue}" but got "${selected}".`);
  }
}

async function expectStatusBadge(rowLocator, expectedText) {
  const badgeText = (await rowLocator.locator('td .badge').first().innerText()).trim();
  if (badgeText !== expectedText) {
    throw new Error(`Expected status badge "${expectedText}" but got "${badgeText}".`);
  }
}

async function expectFlash(page, regex) {
  const alert = page.locator('.alert').first();
  await alert.waitFor();
  const text = await alert.innerText();
  if (!regex.test(text)) {
    throw new Error(`Flash message did not match ${regex}. Actual text: ${text}`);
  }
}

async function expectAlert(page, regex) {
  const alert = page.locator('.alert').first();
  await alert.waitFor();
  const text = await alert.innerText();
  if (!regex.test(text)) {
    throw new Error(`Expected alert matching ${regex}. Actual text: ${text}`);
  }
}

async function expectPath(page, relativePath) {
  const currentPath = new URL(page.url()).pathname;
  if (currentPath !== relativePath) {
    throw new Error(`Expected path ${relativePath} but got ${currentPath}.`);
  }
}

async function safeDeleteProduct(page, siteBaseUrl, productName) {
  try {
    await ensurePageOk(
      page,
      `${toAbsoluteUrl(siteBaseUrl, '/admin/products.php')}?search=${encodeURIComponent(productName)}`
    );
    const rows = page.locator('table tbody tr').filter({ hasText: productName });
    if ((await rows.count()) === 0) {
      return;
    }
    await Promise.all([
      page.waitForURL(/\/admin\/products\.php/),
      rows.first().getByRole('button', { name: /delete/i }).click()
    ]);
  } catch {
    // Cleanup is best effort only.
  }
}

async function safeDeleteUser(page, siteBaseUrl, email) {
  try {
    await ensurePageOk(
      page,
      `${toAbsoluteUrl(siteBaseUrl, '/admin/manage_users.php')}?search=${encodeURIComponent(email)}`
    );
    const rows = page.locator('table tbody tr').filter({ hasText: email });
    if ((await rows.count()) === 0) {
      return;
    }
    await Promise.all([
      page.waitForURL(/\/admin\/manage_users\.php/),
      rows.first().getByRole('button', { name: /delete/i }).click()
    ]);
  } catch {
    // Cleanup is best effort only.
  }
}

function parseArgs(argv) {
  const parsed = {};
  for (let index = 0; index < argv.length; index += 1) {
    const current = argv[index];
    if (!current.startsWith('--')) {
      continue;
    }
    const trimmed = current.slice(2);
    const [key, inlineValue] = trimmed.split('=');
    if (inlineValue !== undefined) {
      parsed[key] = inlineValue;
      continue;
    }
    const next = argv[index + 1];
    if (next && !next.startsWith('--')) {
      parsed[key] = next;
      index += 1;
    } else {
      parsed[key] = true;
    }
  }
  return parsed;
}

function normalizeBaseUrl(rawUrl) {
  const parsed = new URL(rawUrl);
  parsed.hash = '';
  return parsed.toString().replace(/\/$/, '');
}

function toAbsoluteUrl(siteBaseUrl, entry) {
  return new URL(entry, `${siteBaseUrl}/`).toString();
}

function writeJson(filename, data) {
  fs.writeFileSync(filename, JSON.stringify(data, null, 2), 'utf8');
}

function serializeError(error) {
  if (error instanceof Error) {
    return {
      message: error.message,
      stack: error.stack
    };
  }
  return {
    message: String(error)
  };
}

function slugify(input) {
  return input
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function relativeFromTestingRoot(targetPath) {
  return path.relative(testingRoot, targetPath).replaceAll('\\', '/');
}

async function ensurePageOk(page, url) {
  const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
  if (response && response.status() >= 400) {
    throw new Error(`Request for ${url} returned HTTP ${response.status()}.`);
  }
  return response;
}
