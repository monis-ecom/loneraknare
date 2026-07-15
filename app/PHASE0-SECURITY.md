# Phase 0 — Security Runbook (Nordic Trend + ROAS, combined)

**Status:** the code hardening is DONE (in this `app/` folder). The credential **rotation below is your job** — I can't touch your live WordPress/Meta/Hostinger accounts.

---

## Why this is urgent
Your real secrets shipped inside the `nordictrend.zip` and `roas.zip` files: WooCommerce API keys, the WordPress application password, the Meta access token, and the ROAS MySQL database password. **Treat every one of them as compromised.** The moment a zip with live secrets leaves your machine, assume it can be read. Rotating them is the only fix.

---

## ✅ What I already did (code — no action needed)
- **Merged both apps into one backend** (`api.php`) behind Nordic Trend's login.
- **Closed the open ROAS API** — its product endpoints (`?action=roas-products`) now require login. *(Was: anyone with the URL could read/create/delete.)* — verified: returns `401` without a token.
- **Moved secrets to `config.php`** — a PHP file web servers never serve as source (safe on Apache *and* Nginx), instead of the readable `config.json`.
- Externalised the ROAS **database credentials** into config (were hardcoded in source).
- Dropped the dead `other_costs` column and removed the Hostinger `default.php` placeholder.
- Added `.gitignore` (real `config.php` + session files never get committed) and a hardened `.htaccess`.

---

## 🔑 What YOU must do — rotate the 4 secrets

### 1. WooCommerce API keys
WP Admin → **WooCommerce → Settings → Advanced → REST API** →
- **Revoke** the old leaked key (the one currently in your live store).
- **Add key** → Description "Nordic Trend Dashboard", Permissions **Read/Write** → Generate.
- Copy the new `ck_…` and `cs_…` into `config.php` → `wc_consumer_key` / `wc_consumer_secret`.

### 2. WordPress application password
WP Admin → **Users → your profile** (`nordic.ecom2030@gmail.com`) → **Application Passwords** →
- **Revoke** the old one.
- Create a new one named "Nordic Trend" → copy the spaced value into `config.php` → `wp_app_password`.

### 3. Meta access token
**Meta Business Settings → Users → System Users** → your system user →
- **Generate new token** with scopes `ads_read` + `ads_management`.
- **Remove/expire** the old token, then paste the new one into `config.php` → `meta_access_token`.

### 4. ROAS database password
Hostinger **hPanel → Databases → MySQL Databases** →
- Change the password for user `u137498257_monis` (database `u137498257_hmsalary`).
- Put the DB name/user/new-password into `config.php` → `roas_db`.

---

## 🔒 Set your login password
`config.php` ships with a **temporary** password so you can log in once:

```
Username: monis
Temp password: NordicTrend-Temp-094e1d!
```

Log in, then immediately use the **Password** button in the header to set your own. *(Or regenerate a hash now: `php tools/make-hash.php "your-new-password"` and paste it into `config.php` → `auth_password_hash`.)*

---

## 🚀 Deploy the combined app
1. Upload everything in this `app/` folder to the Nordic Trend web root.
2. Make sure **`config.php` exists on the server with your rotated values** (not the example).
3. **Delete from the server:** the old `config.json`, the old `default.php`, and the old ROAS `api.php` if it lived at a separate path.
4. Confirm `.htaccess` uploaded.
5. The combined app reads the **same** `roas_products` table, so your saved products carry over — no migration needed. Once the Planner tab ships (Phase 4), you can retire the standalone `roas.klivrapps.com` app.

## ✔️ Verify after deploy
- Visit `…/api.php?action=roas-products` in a browser with no login → should say **Unauthorized** (401).
- Log in → dashboard loads, orders/visitors show.
- Change the password.
- Open Settings → confirm Meta connects and Sync pulls spend.
- If the Planner/products fail with a DB error, re-check the `roas_db` block.

---

## Residual note
Session/action-log files (`nordic_sessions.json`, `nordic_meta_actions.json`) are protected by `.htaccess`, which **your host (Hostinger/LiteSpeed) honours**. Secrets themselves are in `config.php` and safe regardless of server. If you ever move to raw Nginx, add an equivalent `location` deny for those two `.json` files.
