# Nordic Trend v2.0.0 — Deploy to Hostinger

This is the **combined profit app** (Nordic Trend dashboard + ROAS Calculator, merged).
Full security + rotation steps are in **PHASE0-SECURITY.md** — read that first.

## Quick deploy (Hostinger File Manager)
1. In hPanel → **File Manager**, open your app's web root (where the old `index.html`/`api.php` live).
2. **Delete the old files** first: old `index.html`, `api.php`, `config.json`, `default.php`, and the old standalone ROAS app if it was in the same folder.
3. Upload this zip and **Extract** it here. The files must sit at the web root (so `api.php` is reachable at `https://your-app-url/api.php`).
4. Open **`config.php`** and paste your **freshly rotated** credentials (see PHASE0-SECURITY.md):
   - WooCommerce key/secret, WP application password, Meta token, and the `roas_db` MySQL details.
   - ⚠️ The values in `config.php` are PLACEHOLDERS. The app will not pull live data until you replace them.
   - 💡 After the first deploy you no longer need to edit this file to rotate keys — log in and go to
     **Settings → Store API Keys** to paste new WooCommerce / WordPress credentials and reconnect. If the
     dashboard can't load because the keys are wrong, the "Connection failed" screen now shows an
     **Open Settings · Fix API keys** button that takes you straight there.
5. Confirm the hidden **`.htaccess`** uploaded (File Manager → Settings → "Show hidden files").
6. Open the app URL and log in:
   - Username: `monis`
   - Temp password: `NordicTrend-Temp-094e1d!`  ← **change it immediately** via the "Password" button.

## Verify after deploy
- Visit `…/api.php?action=roas-products` with no login → should say **Unauthorized** (401).
- Log in → Overview shows Revenue / **Profit** / **Margin**.
- Settings → **Payment Fees** loads; **Save Fees** works.
- Ads tab → press **Sync** → per-ad **actual vs break-even** verdicts appear.
- Money tab → Net Sales → Net Profit waterfall.

## Files
- `index.html` — the app (PWA shell)
- `api.php` — unified backend (WooCommerce + Meta + ROAS products, all behind login)
- `config.php` — YOUR secrets (edit this) · `config.example.php` — template
- `.htaccess` — blocks direct access to sensitive files
- `manifest.json`, `icon-*.png` — installable app icons
- `tools/make-hash.php` — generate a new login password hash: `php tools/make-hash.php "new-pass"`

## Push notifications (optional)
The app includes full web-push (VAPID). To turn it on:
1. `config.php` already has a **generated VAPID keypair** — to make your own, run `php tools/make-vapid.php` and paste the two values in. Set `push_subject` to your `mailto:` and `push_cron_token` to a random string.
2. Open the app → **Settings → Notifications → Enable notifications**, allow the browser prompt. (On iPhone: add the app to the Home Screen first, then enable — iOS only allows push for installed PWAs.)
3. Press **Send test** to confirm a notification arrives.
4. **Daily morning summary** — add a Hostinger cron job (hPanel → Advanced → Cron Jobs):
   `php /home/USER/domains/APP/public_html/push-cron.php`  at e.g. 08:00.
   (Or URL cron: `https://APP-URL/push-cron.php?token=YOUR_TOKEN`.)
- Delivery works only over **HTTPS** with the app deployed; it can't be tested from a local file.

## Notes
- Requires PHP 8.x with the WooCommerce native **Cost of Goods Sold** feature ON (it already is on your store).
- `push.php` is pure PHP (no Composer) — self-tests pass at `api.php?action=push-selftest` (should show `jwt:true, encrypt:true`).
- Profit is only accurate for products that have a cost set — see the cost-coverage list you were given; the app flags periods/ads with missing costs.
