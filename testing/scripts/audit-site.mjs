import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import http from 'node:http';
import https from 'node:https';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';
import * as cheerio from 'cheerio';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const testingRoot = path.resolve(__dirname, '..');
const reportsRoot = path.join(testingRoot, 'reports', 'latest');
const configPath = path.join(testingRoot, 'config', 'site.json');
const nodeBinDir = path.join(testingRoot, 'node_modules', '.bin');

const args = parseArgs(process.argv.slice(2));
const mode = args.mode ?? 'all';
const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
const baseUrl = normalizeBaseUrl(args.url ?? config.baseUrl);
const crawlLimit = Number(args.crawlLimit ?? config.crawlLimit ?? 20);
const auditPaths = config.auditPaths ?? ['/'];
const lighthousePaths = config.lighthousePaths ?? ['/'];

fs.mkdirSync(reportsRoot, { recursive: true });

const pages = dedupeUrls([
  ...auditPaths.map((entry) => toAbsoluteUrl(baseUrl, entry)),
  ...(await crawlSite(baseUrl, config.seedPaths ?? ['/'], crawlLimit))
]);

writeJson('pages.json', {
  generatedAt: new Date().toISOString(),
  baseUrl,
  pages
});

const summary = {
  generatedAt: new Date().toISOString(),
  baseUrl,
  mode,
  pageCount: pages.length
};

if (mode === 'all' || mode === 'a11y') {
  summary.a11y = runPa11y(pages);
}

if (mode === 'all' || mode === 'headers') {
  summary.headers = await checkHeaders(baseUrl);
}

if (mode === 'all' || mode === 'links') {
  summary.links = runLinks(baseUrl);
}

if (mode === 'all' || mode === 'markup') {
  summary.markup = runMarkup(baseUrl);
}

if (mode === 'all' || mode === 'lighthouse') {
  summary.lighthouse = runLighthouse(
    lighthousePaths.map((entry) => toAbsoluteUrl(baseUrl, entry))
  );
}

writeJson('summary.json', summary);
console.log(JSON.stringify(summary, null, 2));

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
  const url = new URL(rawUrl);
  url.hash = '';
  return url.toString().replace(/\/$/, '');
}

function toAbsoluteUrl(base, entry) {
  return new URL(entry, `${base}/`).toString();
}

function dedupeUrls(urls) {
  const seen = new Set();
  const result = [];
  for (const url of urls) {
    const normalized = stripHash(url);
    if (seen.has(normalized)) {
      continue;
    }
    seen.add(normalized);
    result.push(normalized);
  }
  return result;
}

function stripHash(url) {
  const parsed = new URL(url);
  parsed.hash = '';
  return parsed.toString();
}

async function crawlSite(base, seedEntries, limit) {
  const queue = seedEntries.map((entry) => toAbsoluteUrl(base, entry));
  const visited = new Set();
  const discovered = [];
  const origin = new URL(base).origin;

  while (queue.length > 0 && discovered.length < limit) {
    const current = queue.shift();
    if (!current || visited.has(current)) {
      continue;
    }
    visited.add(current);

    try {
      const response = await fetch(current, { redirect: 'follow' });
      const contentType = response.headers.get('content-type') ?? '';
      const finalUrl = stripHash(response.url);
      if (!finalUrl.startsWith(origin) || !contentType.includes('text/html')) {
        continue;
      }

      discovered.push(finalUrl);
      const html = await response.text();
      const $ = cheerio.load(html);

      $('a[href]').each((_, element) => {
        const href = $(element).attr('href');
        const nextUrl = resolveInternalUrl(origin, finalUrl, href);
        if (!nextUrl || visited.has(nextUrl) || queue.includes(nextUrl)) {
          return;
        }
        queue.push(nextUrl);
      });
    } catch {
      // Keep crawling even if one page fails.
    }
  }

  return discovered;
}

function resolveInternalUrl(origin, currentUrl, candidate) {
  if (!candidate) {
    return null;
  }

  const trimmed = candidate.trim();
  if (
    trimmed === '' ||
    trimmed.startsWith('#') ||
    trimmed.startsWith('mailto:') ||
    trimmed.startsWith('tel:') ||
    trimmed.startsWith('javascript:')
  ) {
    return null;
  }

  try {
    const resolved = new URL(trimmed, currentUrl);
    resolved.hash = '';
    if (resolved.origin !== origin) {
      return null;
    }

    if (/\.(jpg|jpeg|png|gif|svg|webp|css|js|pdf|zip|glb)$/i.test(resolved.pathname)) {
      return null;
    }

    return resolved.toString();
  } catch {
    return null;
  }
}

function runPa11y(targets) {
  const pa11yPath = resolveCommand('pa11y');
  const results = [];

  for (const url of targets) {
    const run = runCommand(pa11yPath, [url, '--reporter', 'json']);

    if (run.error) {
      results.push({
        url,
        issueCount: 0,
        issues: [],
        error: run.error.message
      });
      continue;
    }

    const stdout = run.stdout?.trim() || '[]';
    let issues = [];
    try {
      issues = JSON.parse(stdout);
    } catch {
      issues = [];
    }

    results.push({
      url,
      issueCount: issues.length,
      issues,
      exitCode: run.status
    });
  }

  writeJson('a11y.json', results);
  return {
    pagesScanned: results.length,
    totalIssues: results.reduce((sum, item) => sum + item.issueCount, 0)
  };
}

async function checkHeaders(base) {
  try {
    const keys = [
      'strict-transport-security',
      'content-security-policy',
      'x-frame-options',
      'x-content-type-options',
      'referrer-policy',
      'permissions-policy',
      'server'
    ];

    const responseHeaders = await requestHeaders(base);
    const headers = Object.fromEntries(keys.map((key) => [key, responseHeaders[key] ?? null]));

    writeJson('headers.json', headers);
    return headers;
  } catch (error) {
    const failure = {
      error: error.message
    };
    writeJson('headers.json', failure);
    return failure;
  }
}

function requestHeaders(url, redirectLimit = 5) {
  return new Promise((resolve, reject) => {
    const parsed = new URL(url);
    const client = parsed.protocol === 'https:' ? https : http;

    const request = client.request(
      parsed,
      {
        method: 'GET',
        headers: {
          'user-agent': 'pomeshop-testing/1.0'
        }
      },
      (response) => {
        const status = response.statusCode ?? 0;
        const location = response.headers.location;

        if (status >= 300 && status < 400 && location && redirectLimit > 0) {
          response.resume();
          const nextUrl = new URL(location, parsed).toString();
          requestHeaders(nextUrl, redirectLimit - 1).then(resolve, reject);
          return;
        }

        response.resume();
        resolve(response.headers);
      }
    );

    request.on('error', reject);
    request.end();
  });
}

function runLinks(base) {
  const linkCheckerPath = resolveCommand('blc');
  const run = runCommand(linkCheckerPath, ['--recursive', '--ordered', base]);

  const output = `${run.stdout ?? ''}${run.stderr ?? ''}${formatRunError(run)}`.trim();
  fs.writeFileSync(path.join(reportsRoot, 'links.txt'), output, 'utf8');

  return {
    ok: run.status === 0,
    exitCode: run.status ?? 1,
    report: 'reports/latest/links.txt',
    error: run.error?.message ?? null
  };
}

function runMarkup(base) {
  const vnuPath = resolveCommand('vnu', [
    path.join(process.env.LOCALAPPDATA ?? '', 'WebTools', 'bin', 'vnu.cmd')
  ]);

  const run = runCommand(vnuPath, ['--skip-non-html', '--errors-only', base]);

  const output = `${run.stdout ?? ''}${run.stderr ?? ''}${formatRunError(run)}`.trim();
  fs.writeFileSync(path.join(reportsRoot, 'markup.txt'), output, 'utf8');

  return {
    ok: run.status === 0,
    exitCode: run.status ?? 1,
    report: 'reports/latest/markup.txt',
    error: run.error?.message ?? null
  };
}

function runLighthouse(targets) {
  const chromePath = detectChromePath();
  const lighthousePath = resolveCommand('lighthouse');
  const results = [];

  for (const url of targets) {
    const slug = slugify(url);
    const outputPath = path.join(reportsRoot, `lighthouse-${slug}.json`);
    const existingHtmlReports = new Set(findRootLighthouseHtmlReports());
    const args = [
      url,
      '--only-categories=performance,accessibility,best-practices,seo',
      '--output=json',
      `--output-path=${outputPath}`,
      '--quiet'
    ];

    if (chromePath) {
      args.push(`--chrome-path=${chromePath}`);
    }

    const run = runCommand(lighthousePath, args);
    moveNewLighthouseHtmlReport(existingHtmlReports, slug);

    if (!fs.existsSync(outputPath)) {
      results.push({
        url,
        error: run.error?.message ?? 'Lighthouse report was not generated.'
      });
      continue;
    }

    const report = JSON.parse(fs.readFileSync(outputPath, 'utf8'));
    results.push({
      url,
      scores: {
        performance: Math.round((report.categories.performance.score ?? 0) * 100),
        accessibility: Math.round((report.categories.accessibility.score ?? 0) * 100),
        bestPractices: Math.round((report.categories['best-practices'].score ?? 0) * 100),
        seo: Math.round((report.categories.seo.score ?? 0) * 100)
      }
    });
  }

  return results;
}

function findRootLighthouseHtmlReports() {
  return fs
    .readdirSync(testingRoot, { withFileTypes: true })
    .filter((entry) => entry.isFile() && entry.name.endsWith('.report.html'))
    .map((entry) => entry.name);
}

function moveNewLighthouseHtmlReport(previousFiles, slug) {
  const currentFiles = findRootLighthouseHtmlReports();
  const newFile = currentFiles.find((filename) => !previousFiles.has(filename));
  if (!newFile) {
    return;
  }

  const fromPath = path.join(testingRoot, newFile);
  const toPath = path.join(reportsRoot, `lighthouse-${slug}.html`);
  fs.renameSync(fromPath, toPath);
}

function detectChromePath() {
  const candidates = [
    process.env.CHROME_PATH,
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'
  ].filter(Boolean);

  return candidates.find((candidate) => fs.existsSync(candidate)) ?? null;
}

function runCommand(command, args) {
  if (process.platform === 'win32' && /\.(cmd|bat)$/i.test(command)) {
    const commandLine = [quoteForCmd(command), ...args.map((arg) => quoteForCmd(arg))].join(' ');
    return spawnSync(process.env.ComSpec ?? 'cmd.exe', ['/d', '/s', '/c', commandLine], {
      encoding: 'utf8'
    });
  }

  return spawnSync(command, args, {
    encoding: 'utf8'
  });
}

function formatRunError(run) {
  return run.error ? `\n${run.error.message}` : '';
}

function quoteForCmd(value) {
  const stringValue = String(value);
  if (stringValue === '') {
    return '""';
  }

  if (!/[ \t"&()^\[\]{}=!'+,;`~]/.test(stringValue)) {
    return stringValue;
  }

  return `"${stringValue.replace(/"/g, '""')}"`;
}

function resolveCommand(name, fallbacks = []) {
  const extensions = process.platform === 'win32'
    ? [`${name}.cmd`, `${name}.exe`, `${name}.bat`, name]
    : [name];

  for (const extension of extensions) {
    const candidate = path.join(nodeBinDir, extension);
    if (fs.existsSync(candidate)) {
      return candidate;
    }
  }

  for (const fallback of fallbacks) {
    if (fallback && fs.existsSync(fallback)) {
      return fallback;
    }
  }

  return name;
}

function slugify(input) {
  return input
    .replace(/^https?:\/\//, '')
    .replace(/[^a-z0-9]+/gi, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase();
}

function writeJson(filename, data) {
  fs.writeFileSync(
    path.join(reportsRoot, filename),
    JSON.stringify(data, null, 2),
    'utf8'
  );
}
