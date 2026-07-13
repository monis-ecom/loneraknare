import { chromium } from 'playwright-core';
import { readdirSync } from 'node:fs';

const EXE = '/opt/pw-browsers/chromium_headless_shell-1194/chrome-linux/headless_shell';
const dir = new URL('./out/', import.meta.url);
const files = readdirSync(dir).filter((f) => f.endsWith('.html'));

const browser = await chromium.launch({ executablePath: EXE });
const page = await browser.newPage({ viewport: { width: 1100, height: 900 } });
for (const f of files) {
  await page.goto('file://' + new URL(f, dir).pathname, { waitUntil: 'networkidle' });
  const shot = new URL(f.replace('.html', '.png'), dir).pathname;
  await page.screenshot({ path: shot, fullPage: true });
  console.log('shot', f);
}
await browser.close();
