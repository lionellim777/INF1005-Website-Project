# Testing Workspace

This folder contains repo-local website testing tools and scripts. It is isolated from the PHP application so you can keep testing setup, reports, and tool versions separate from the website code.

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

Playwright helpers:

```powershell
npm run playwright:open -- https://example.com
npm run playwright:codegen -- https://example.com
```

## Output

Each run writes files into `reports/latest/`, including:

- `summary.json`
- `pages.json`
- `a11y.json`
- `headers.json`
- `links.txt`
- `markup.txt`
- `lighthouse-*.json`
