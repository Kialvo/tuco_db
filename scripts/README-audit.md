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

## Run — TWO passes, both required

`/websites` renders two different pages by role: guests get
`marketplace/domains.blade.php`, everyone else the admin DataTables view. A
single session cannot cover both, because `RestrictGuestToDomainsMiddleware`
redirects guests off 10 of the 12 admin routes. So there are two passes.

```bash
php artisan serve --port=8000          # app must be running — NOT `composer dev` (see below)

# Pass 1 — admin (12 routes, audit-readability.config.json)
node scripts/capture-auth-state.mjs
npm run audit:readability

# Pass 2 — guest marketplace (7 routes, audit/guest/audit-readability.config.json)
node scripts/capture-auth-state.mjs --as=guest --out=.readability-auth-guest.json
npm run audit:readability:guest

npm run doctor                         # verifies wired lints match this project's stack (laravel)
```

⚠️ **Never start the app with `composer dev` for an audit.** It runs `queue:listen`,
and `.env` points `QUEUE_CONNECTION=database` at the live production database.

### Read coverage from stdout, not from the report JSON

The audit records a redirect as a coverage error only when the landing path looks
like a login route. Every other redirect lands HTTP 200 and is graded as the page
you asked for. It does print `⚠ redirected <asked> → <landed>` to the console, but
that never reaches the report: the success-path result object carries `url`,
`theme`, `label` and the violations — and **no `landedPath`**. So:

```bash
npm run audit:readability:guest 2>&1 | tee /tmp/audit-guest.txt
grep -c 'auditing '    /tmp/audit-guest.txt   # routes attempted
grep -c 'NOT AUDITED'  /tmp/audit-guest.txt   # must be 0
grep   '⚠ redirected'  /tmp/audit-guest.txt   # must be EMPTY
```

Always report findings **beside the number of routes measured**. `0 findings` over
`0 routes` is not a pass.

### Why `?as=guest` filters on `email_verified_at`

`/dev-login?as=guest` picks a guest that is **verified** and not in a forced
password change. This is not defensive coding: every authenticated route sits
behind the `verified` middleware, and the overwhelming majority of guest accounts
are unverified. Picking one of those redirects every request to `/verify-email` —
which is guest-allowlisted, renders 200, and is *not* matched by the bounce
detector. The audit would then grade the verification prompt seven times and
report a clean bill. Do not remove that filter.

`capture-auth-state.mjs` additionally asserts, after login, that `/websites`
rendered the view it expected (`domainListUI` is unique to the guest marketplace;
`btnOpenCart` is **not** — it also appears in the admin view's own guest branch).
It exits non-zero on a mismatch, so a stale or wrong session fails loudly instead
of producing an unearned clean run.

### Checking the layout fills the viewport

The audit pins its viewport to 1440x900 and only screenshots pages that already
have violations, so it cannot answer "does this page fill a tall screen".
`scripts/verify-guest-render.mjs` does that:

```bash
node scripts/verify-guest-render.mjs                        # 1270px + 900px
node scripts/verify-guest-render.mjs --path '/websites?domain_name=zzzz'  # empty state
node scripts/verify-guest-render.mjs --state .readability-auth.json       # must FAIL
```

The last form is the calibration: pointed at an admin session it must exit 1, or
the guest/admin discriminator is not actually discriminating.

Findings print to the console; screenshots land in `audit-readability-screenshots/`
and the report in `audit-readability-report.json` (both gitignored). Edit the
route list and `themes` in `audit-readability.config.json`.

Scopes: this project declares `{"stack":"laravel"}` in `website-lints.json`, so
only the stack-agnostic `shared` audits apply (the Next.js `check:*` lints do
not). See the package's `SCOPES.md`.
