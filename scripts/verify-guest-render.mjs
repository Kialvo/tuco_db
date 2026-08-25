#!/usr/bin/env node
// Measure the guest Domains page at chosen viewport heights and screenshot it.
//
// Why this exists rather than reusing the readability audit: that tool pins its
// viewport to 1440x900 and only screenshots pages that already have violations,
// so it structurally cannot answer "does this page fill a 1270px-tall screen".
//
//   node scripts/verify-guest-render.mjs                       # 1270 + 900
//   node scripts/verify-guest-render.mjs --height 1600
//   node scripts/verify-guest-render.mjs --state .readability-auth.json
//   node scripts/verify-guest-render.mjs --path '/websites?domain_name=zzzzzz'
//
// Exits non-zero when the session is not the guest rendering, or when a
// viewport still shows dead space below the pagination bar.

import puppeteer from 'puppeteer';
import { readFileSync, mkdirSync } from 'node:fs';

const argv = process.argv.slice(2);
const heights = [];
let statePath = '.readability-auth-guest.json';
let targetPath = '/websites';
let base = 'http://localhost:8000';
let expectGuest = true;

for (let i = 0; i < argv.length; i++) {
  const [key, inline] = argv[i].replace(/^--/, '').split('=');
  const value = inline ?? argv[++i];
  if (key === 'height') heights.push(Number(value));
  else if (key === 'state') statePath = value;
  else if (key === 'path') targetPath = value;
  else if (key === 'base') base = value;
  else if (key === 'expect') expectGuest = value !== 'admin';
}
if (!heights.length) heights.push(1270, 900);

// Anything above this reads as a gap on screen. Sub-pixel layout rounding and
// the card's 1px border make an exact 0 unreachable.
const GAP_TOLERANCE_PX = 4;
const OUT_DIR = 'audit-guest-render';

const { cookies } = JSON.parse(readFileSync(statePath, 'utf8'));
mkdirSync(OUT_DIR, { recursive: true });

const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
let failures = 0;

try {
  for (const height of heights) {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height });
    await page.setCookie(...cookies);
    await page.goto(base + targetPath, { waitUntil: 'networkidle2', timeout: 30000 });

    const landed = new URL(page.url()).pathname;
    if (landed !== targetPath.split('?')[0]) {
      console.error(`  ✗ ${height}px — redirected to ${landed}; measuring would grade the wrong page`);
      failures++;
      await page.close();
      continue;
    }

    const m = await page.evaluate(() => {
      const main = document.querySelector('main');
      const card = document.querySelector('.ds-table--fill');
      const empty = document.querySelector('main .text-center.py-16');
      // Last flex child of the page column = the pagination wrapper, or the
      // card itself when the result set fits on one page.
      const last = card?.parentElement?.lastElementChild;
      const isGuestRender = document.documentElement.innerHTML.includes('domainListUI');
      if (!main) return { isGuestRender, error: 'no <main>' };
      const mainBottom = main.getBoundingClientRect().bottom;
      const anchor = last ?? empty;
      if (!anchor) return { isGuestRender, error: 'no card, pagination or empty state found' };
      return {
        isGuestRender,
        hasFillCard: !!card,
        // padding-bottom on <main> is real layout, not dead space.
        gap: mainBottom - anchor.getBoundingClientRect().bottom
             - parseFloat(getComputedStyle(main).paddingBottom || '0'),
        cardScrolls: card ? card.scrollHeight > card.clientHeight + 1 : null,
        theadSticky: card
          ? getComputedStyle(card.querySelector('thead th')).position === 'sticky'
          : null,
        rows: document.querySelectorAll('.ds-table--fill tbody tr').length,
      };
    });

    const shot = `${OUT_DIR}/guest-domains-${height}.png`;
    await page.screenshot({ path: shot });

    if (m.isGuestRender !== expectGuest) {
      console.error(`  ✗ ${height}px — expected the ${expectGuest ? 'GUEST' : 'ADMIN'} rendering, got the other one`);
      failures++;
    } else if (m.error) {
      console.error(`  ✗ ${height}px — ${m.error}`);
      failures++;
    } else if (m.gap > GAP_TOLERANCE_PX) {
      console.error(`  ✗ ${height}px — ${m.gap.toFixed(1)}px of dead space below the last element (rows=${m.rows})`);
      failures++;
    } else {
      console.log(
        `  ✓ ${height}px — gap ${m.gap.toFixed(1)}px, rows ${m.rows}, ` +
        `card scrolls ${m.cardScrolls}, thead sticky ${m.theadSticky}`,
      );
    }
    console.log(`      screenshot: ${shot}`);
    await page.close();
  }
} finally {
  await browser.close();
}

process.exit(failures ? 1 : 0);
