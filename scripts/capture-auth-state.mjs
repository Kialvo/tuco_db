#!/usr/bin/env node
// Capture a signed-in session so `audit-readability` can reach the auth-gated
// dashboard routes instead of silently auditing only /login.
//
// Default (local): logs in via the /dev-login shortcut — no credentials needed,
// no DB write (SESSION_DRIVER=file). If AUDIT_EMAIL / AUDIT_PASSWORD are set in
// .env (gitignored), it drives the real /login form as that user instead. Either
// way it writes { cookies: [...] } (Puppeteer format) to the storageStatePath the
// audit replays via page.setCookie().
//
// One-time local setup (these are NOT app dependencies — they stay out of
// package.json so `npm install` on deploy never pulls Chromium):
//   npm install --save-dev puppeteer @axe-core/puppeteer
//   npx puppeteer browsers install chrome
// Then run (no creds needed when APP_ENV=local):
//   node scripts/capture-auth-state.mjs
//   npx website-lints audit-readability

import puppeteer from 'puppeteer';
import { readFileSync, writeFileSync } from 'node:fs';

function readEnvFile(file) {
  try {
    return Object.fromEntries(
      readFileSync(file, 'utf8')
        .split('\n')
        .map((l) => l.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)$/))
        .filter(Boolean)
        .map((m) => [m[1], m[2].replace(/^["']|["']$/g, '').trim()]),
    );
  } catch {
    return {};
  }
}

// --as=guest  capture a GUEST session (the marketplace views) instead of admin.
// --out=FILE  write somewhere other than the config's storageStatePath, so the
//             guest capture does not clobber the admin one.
const args = Object.fromEntries(
  process.argv.slice(2)
    .map((a) => a.match(/^--([a-z-]+)(?:=(.*))?$/))
    .filter(Boolean)
    .map((m) => [m[1], m[2] ?? true]),
);

const cfg = JSON.parse(readFileSync('audit-readability.config.json', 'utf8'));
const env = { ...readEnvFile('.env'), ...process.env };
const base = cfg.baseUrl || 'http://localhost:8000';
const asGuest = args.as === 'guest';
const out = args.out || cfg.storageStatePath || '.readability-auth.json';
const email = env.AUDIT_EMAIL;
const password = env.AUDIT_PASSWORD;
// No creds in .env → use the local-only /dev-login shortcut: a GET that logs in
// as the first non-guest, password-set admin. No password is sent and, with
// SESSION_DRIVER=file, the session is written to disk — never to the (production)
// DB. Set AUDIT_EMAIL/AUDIT_PASSWORD to force the real /login form instead
// (e.g. to audit as a specific user, or when APP_ENV is not local).
//
// --as=guest always takes /dev-login: AUDIT_EMAIL/AUDIT_PASSWORD are an admin's,
// and silently logging in as the admin would hand the guest audit the admin's
// DataTables view — exactly the confusion this flag exists to prevent.
const useDevLogin = asGuest || !email || !password;

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
try {
  const page = await browser.newPage();
  let finalUrl;
  if (useDevLogin) {
    const devUrl = asGuest ? '/dev-login?as=guest&to=/websites' : '/dev-login?to=/dashboard';
    await page.goto(base + devUrl, { waitUntil: 'networkidle2', timeout: 30000 });
    finalUrl = page.url();
    if (/\/dev-login|\/login(\?|$)/.test(finalUrl)) {
      console.error(`[capture] /dev-login did not authenticate — is APP_ENV=local, the server up, and a ${asGuest ? 'VERIFIED guest' : 'non-guest'} user present? URL:`, finalUrl);
      process.exit(1);
    }
  } else {
    await page.goto(base + '/login', { waitUntil: 'networkidle2', timeout: 30000 });
    await page.type('input[name="email"]', email);
    await page.type('input[name="password"]', password);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 }).catch(() => {}),
      page.click('button[type="submit"]'),
    ]);
    finalUrl = page.url();
    if (/\/login(\?|$)/.test(finalUrl)) {
      console.error('[capture] still on /login after submit — check AUDIT_EMAIL/AUDIT_PASSWORD. URL:', finalUrl);
      process.exit(1);
    }
  }
  // ── Identity assertion ────────────────────────────────────────────────────
  // Nothing downstream re-checks who this session belongs to: the audit loads
  // storageStatePath if the file merely EXISTS, and it only reports a bounce
  // when the landing path looks like a login route. So a wrong or expired
  // session produces a confident, entirely unearned "0 violations".
  //
  // domainListUI() is defined only in marketplace/domains.blade.php — the guest
  // rendering of /websites. (btnOpenCart is NOT a valid discriminator: it also
  // appears in the admin view's own guest branch.)
  await page.goto(base + '/websites', { waitUntil: 'networkidle2', timeout: 30000 });
  const landed = new URL(page.url()).pathname;
  const isGuestRender = await page.evaluate(
    () => document.documentElement.innerHTML.includes('domainListUI'),
  );

  if (landed !== '/websites') {
    console.error(`[capture] /websites redirected to ${landed} — this session cannot audit the marketplace.`);
    if (landed.startsWith('/verify-email')) {
      console.error('[capture] the selected user is unverified; /dev-login?as=guest must filter on email_verified_at.');
    }
    process.exit(1);
  }
  if (asGuest !== isGuestRender) {
    console.error(
      `[capture] session identity mismatch: expected the ${asGuest ? 'GUEST marketplace' : 'ADMIN DataTables'} view at /websites, ` +
      `got the ${isGuestRender ? 'GUEST marketplace' : 'ADMIN DataTables'} one.`,
    );
    process.exit(1);
  }

  const cookies = await page.cookies();
  if (!cookies.length) {
    console.error('[capture] no cookies captured.');
    process.exit(1);
  }
  writeFileSync(out, JSON.stringify({ cookies }, null, 2) + '\n');
  console.log(`[capture] ${cookies.length} cookies -> ${out} (${asGuest ? 'GUEST' : 'ADMIN'} session, verified at /websites)`);
} finally {
  await browser.close();
}
