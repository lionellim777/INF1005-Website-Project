# Testing Workspace

This folder contains repo-local website testing tools and scripts. It is isolated from the PHP application so you can keep testing setup, reports, and tool versions separate from the website code.

## Tools

This workspace uses a small set of website testing tools, each covering a different type of check:

- `Pa11y`: automated accessibility testing. It scans pages for WCAG issues such as missing form labels, unnamed buttons, and other common accessibility failures.
- `Lighthouse`: performance, accessibility, best-practices, and SEO auditing. It is useful for report-style scoring and finding page-speed problems.
- `broken-link-checker` (`blc`): recursive link checking. It crawls internal links and reports broken pages, bad assets, and redirect chains.
- `Nu HTML Checker` (`vnu`): HTML validation. It checks markup against HTML standards and flags structural issues such as invalid element placement and heading-order problems.
- `Playwright`: browser automation. It is included for manual inspection, flow testing, and future scripted checks for login, cart, checkout, or admin pages.
- Header check in `audit-site.mjs`: a lightweight built-in check for common security headers such as `CSP`, `HSTS`, and `X-Frame-Options`.

Some tools are installed locally in this `testing` workspace through `npm`, while `vnu` uses the system-level validator command already set up on this machine.

## Install

```powershell
cd testing
npm.cmd install
```

If you want Playwright's bundled browser in this workspace:

```powershell
npx playwright install chromium
```

## Configure

Edit `config/site.json` to change:

- `baseUrl`: site root
- `auditPaths`: important pages to check directly
- `lighthousePaths`: pages to run Lighthouse on
- `crawlLimit`: how many internal pages to discover from the site

## Run

From `testing`:

```powershell
npm run audit:site
```

Override the URL for one run:

```powershell
npm run audit:site -- --url=https://example.com
```

Individual checks:

```powershell
npm run audit:a11y
npm run audit:headers
npm run audit:lighthouse
npm run audit:links
npm run audit:markup
```

What each command does:

- `audit:site`: runs the full workflow and writes one combined summary.
- `audit:a11y`: runs only accessibility checks with `Pa11y`.
- `audit:headers`: runs only the security-header check.
- `audit:lighthouse`: runs only Lighthouse on the configured `lighthousePaths`.
- `audit:links`: runs only recursive link checking.
- `audit:markup`: runs only HTML validation with `vnu`.

Playwright helpers:

```powershell
npm run playwright:open -- https://example.com
npm run playwright:codegen -- https://example.com
```

## Output

Each run writes files into `reports/latest/`, including:

- `summary.json`: top-level summary of the run and headline results
- `pages.json`: the final list of pages checked after combining configured paths and crawled pages
- `a11y.json`: detailed accessibility issues from `Pa11y`
- `headers.json`: values for the checked security headers
- `links.txt`: raw output from the link checker
- `markup.txt`: raw output from `Nu HTML Checker`
- `lighthouse-*.json`: structured Lighthouse report data
- `lighthouse-*.html`: browser-friendly Lighthouse report
