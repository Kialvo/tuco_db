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

const cfg = JSON.parse(readFileSync('audit-readability.config.json', 'utf8'));
const env = { ...readEnvFile('.env'), ...process.env };
const base = cfg.baseUrl || 'http://localhost:8000';
const out = cfg.storageStatePath || '.readability-auth.json';
const email = env.AUDIT_EMAIL;
const password = env.AUDIT_PASSWORD;
// No creds in .env → use the local-only /dev-login shortcut: a GET that logs in
// as the first non-guest, password-set admin. No password is sent and, with
// SESSION_DRIVER=file, the session is written to disk — never to the (production)
// DB. Set AUDIT_EMAIL/AUDIT_PASSWORD to force the real /login form instead
// (e.g. to audit as a specific user, or when APP_ENV is not local).
const useDevLogin = !email || !password;

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
try {
  const page = await browser.newPage();
  let finalUrl;
  if (useDevLogin) {
    await page.goto(base + '/dev-login?to=/dashboard', { waitUntil: 'networkidle2', timeout: 30000 });
    finalUrl = page.url();
    if (/\/dev-login|\/login(\?|$)/.test(finalUrl)) {
      console.error('[capture] /dev-login did not authenticate — is APP_ENV=local, the server up, and a non-guest user present? URL:', finalUrl);
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
  const cookies = await page.cookies();
  if (!cookies.length) {
    console.error('[capture] no cookies captured.');
    process.exit(1);
  }
  writeFileSync(out, JSON.stringify({ cookies }, null, 2) + '\n');
  console.log(`[capture] ${cookies.length} cookies -> ${out} (authenticated at ${finalUrl})`);
} finally {
  await browser.close();
}
