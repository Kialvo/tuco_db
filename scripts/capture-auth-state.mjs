#!/usr/bin/env node
// Capture a signed-in session so `audit-readability` can reach the auth-gated
// dashboard routes instead of silently auditing only /login.
//
// Drives the real /login form with AUDIT_EMAIL / AUDIT_PASSWORD (read from
// .env, gitignored), then writes { cookies: [...] } (Puppeteer format) to the
// storageStatePath the audit replays via page.setCookie().
//
// One-time local setup (these are NOT app dependencies — they stay out of
// package.json so `npm install` on deploy never pulls Chromium):
//   npm install --save-dev puppeteer @axe-core/puppeteer
//   npx puppeteer browsers install chrome
// Then set AUDIT_EMAIL / AUDIT_PASSWORD in .env and run:
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

if (!email || !password) {
  console.error('[capture] set AUDIT_EMAIL and AUDIT_PASSWORD in .env (gitignored).');
  process.exit(1);
}

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
try {
  const page = await browser.newPage();
  await page.goto(base + '/login', { waitUntil: 'networkidle2', timeout: 30000 });
  await page.type('input[name="email"]', email);
  await page.type('input[name="password"]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
  const finalUrl = page.url();
  if (/\/login(\?|$)/.test(finalUrl)) {
    console.error('[capture] still on /login after submit — check AUDIT_EMAIL/AUDIT_PASSWORD. URL:', finalUrl);
    process.exit(1);
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
