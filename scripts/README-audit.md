# Readability audit (runtime UI-quality check)

Runs `@kialvo/website-lints`' `audit-readability` against the running app — axe
`color-contrast` + DOM readability checks (dim text, sub-floor font sizes,
colored-pill-on-dark) on the rendered dashboard pages. Stack-agnostic: it reads
the rendered DOM via a URL, so it works on Blade with no porting.

## One-time setup

The browser deps are intentionally NOT in `package.json` (so `npm install` on
deploy never pulls ~170MB of Chromium):

```bash
npm install --save-dev puppeteer @axe-core/puppeteer
npx puppeteer browsers install chrome
```

Add a valid login to `.env` (gitignored — never commit real credentials):

```
AUDIT_EMAIL=you@example.com
AUDIT_PASSWORD=...
```

## Run

```bash
php artisan serve --port=8000          # app must be running
node scripts/capture-auth-state.mjs    # logs in via /login, writes .readability-auth.json
npm run audit:readability              # audits the routes in audit-readability.config.json
npm run doctor                         # verifies wired lints match this project's stack (laravel)
```

Findings print to the console; screenshots land in `audit-readability-screenshots/`
and the report in `audit-readability-report.json` (both gitignored). Edit the
route list and `themes` in `audit-readability.config.json`.

Scopes: this project declares `{"stack":"laravel"}` in `website-lints.json`, so
only the stack-agnostic `shared` audits apply (the Next.js `check:*` lints do
not). See the package's `SCOPES.md`.
