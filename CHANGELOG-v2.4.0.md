# Profit Tracker v2.4.0 — ADHD wins: order alerts, focus mode, health score

Milestone 3's top three. The dashboard now leads with **one clear answer** and gives
**instant feedback on every sale**.

## ✅ Shipped & verified in the browser

### A2 · Focus mode (progressive disclosure)
- A hero at the top of the dashboard answers one question: **"Är du lönsam idag? JA / NEJ"**
  in traffic-light color, with the period's net profit and a one-line margin/ROAS/orders summary.
- A **Fokusläge** toggle collapses everything else behind a tap (saved in the browser).
- Verified: profitable → "JA · +2 340 kr" (green); loss → "NEJ · −518 kr" (red); toggle hides/shows details.

### A3 · Store health score
- A single **🟢 Frisk / 🟡 OK / 🔴 Går back** badge in the hero, rolled up from net profit, margin and ROAS.
- Verified across green (margin ≥15 % & ROAS ≥2×), amber (thin margin) and red (loss) paths.

### A1 · Real-time order alert + sound
- **Foreground (works now, no setup):** while the app is open it polls `order-pulse` every 60 s;
  a new order plays `order-sound.mp3` (the "cha-ching") and shows a green **"🛍️ Ny order! +X kr · N varor · Name"** banner.
  Toggle + "Testa ljud" button in Settings → Allmänt. Verified: banner shows on a simulated new order.
- **Background (deploy-tested):** service worker now registers and handles `push`/`notificationclick`;
  a device can subscribe (Settings → "Aktivera bakgrundsavisering"). Ported from the old `push.php`,
  modernized to use native `openssl_pkey_derive()` (no shell-exec) and DB-backed subscriptions.

## New endpoints
| Action | Auth | Purpose |
|---|---|---|
| `order-pulse` | user | today's order count + newest order (foreground alert) |
| `push-vapid-key` | user | returns the VAPID public key |
| `push-subscribe` / `push-unsubscribe` | user | store/remove this device's push subscription |
| `push-webhook` | secret | WooCommerce *Order created* webhook → notify all subscribers |

## New files / tables
- `lib/webpush.php` — VAPID JWT + aes128gcm encryption (native ECDH).
- `generate-vapid.php` — run once to create VAPID keys.
- `order-sound.mp3`, updated `sw.js` (push handlers).
- Table `profit_push_subs` (auto-created).

## Setup for background push (optional — foreground works without it)
1. `php generate-vapid.php` on the server → copy the two keys.
2. Add to `db-config-profit.php`: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`, `PUSH_WEBHOOK_SECRET` (see `db-config.example.php`).
3. WooCommerce → Settings → Advanced → Webhooks → add **Order created** → delivery URL
   `https://profit.klivrapps.com/api.php?action=push-webhook&secret=YOUR_SECRET`.
4. In the app: Settings → Allmänt → **Aktivera bakgrundsavisering**, then place a test order.

⚠️ The push **encryption path is unverified in this environment** (needs a real push service + device).
Test on a staging device before relying on it. The foreground sound/banner is fully verified.

## Verification
`php -l` clean on all PHP · 26/26 unit tests pass · all inline JS parses · `sw.js` valid ·
page boots with **zero console errors** · focus hero, health badge, order banner and toggle all exercised in-browser.

## Note
The app previously **unregistered** its service worker each load (to avoid stale-cache bugs).
v2.4.0's `sw.js` caches nothing (no fetch handler), so it now **registers** safely to enable push.
