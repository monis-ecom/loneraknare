# Profit Tracker v2.2.0

## What Changed

- Moves detailed Meta ad performance and controls from Settings into the main dashboard under the Ads KPIs.
- Keeps Meta API configuration in Settings > Annonsering while removing the mixed performance table from Settings.
- The dashboard Meta table follows the selected dashboard period by default.
- Keeps pause/activate and budget controls with typed confirmations and action logging.
- Removes the store name/domain text from the login screen.
- Resets the admin password one time for this release if an older `monis` user already exists.
- Fixes Hostinger/MariaDB startup error caused by prepared placeholders in schema checks.
- Fixes the blank-screen risk after deployment by showing the login screen as the safe fallback and guarding startup errors.
- Adds Meta ad-level reporting, pause/activate, budget changes, and audit logging.
- Adds Meta API settings, manual sync, and protected cron sync.
- Imports Meta ad-level spend, clicks, impressions, purchases, and purchase value.
- Mirrors synced Meta spend into the existing ad-spend table so ROAS, CPA, and net profit continue to work.
- Reads WooCommerce order meta for `adid`, `asid`, and `cid` to attribute sales to ads when those values are stored on orders.
- Adds the authenticated `attribution-profit` API endpoint for grouped revenue/profit attribution by campaign, ad set, ad, source, product, or date.
- Keeps manual ad-spend entry available.

## Meta Permissions

Use a server-side System User token with:

```text
ads_read
ads_management
```

Do not paste the token into browser JavaScript, screenshots, or chat. Paste it only into the live app's Settings > Annonsering > Meta API screen or place it in a private server config outside web root.

## Deployment

1. Upload the contents of this folder to `profit.klivrapps.com`.
2. Keep or move `db-config.php` outside web root as described in that file.
3. Hard refresh the browser once after upload so old app cache is cleared.
4. Log in as admin.
5. Open Settings > `Annonsering`.
6. Enter the ad account ID, API version, and token.
7. Click `Spara Meta`.
8. Click `Synca nu`.
9. Return to the main dashboard and compare the Meta ad table against Meta Ads Manager.
10. Add the cron URLs shown by the app to Hostinger if you want automatic sync.

## Safety

- All write actions require admin login.
- Pause/activate actions require typed confirmation.
- Budget changes require typed confirmation.
- Every write action is logged in `profit_meta_action_log`.
- The Meta token is never returned to the browser after saving.

## Rollback

The previous live build should be archived as `profit-tracker-v2.1.5.zip` in `builds/Old Versions`.
