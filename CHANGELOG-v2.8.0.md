# Profit Tracker v2.8.0 — inventory, P&L snapshots & streaks

Three features: **G2** inventory value + low-stock, **G6** daily P&L snapshots, **A8** profitable-day streak. All browser-verified.

## ✅ G2 · Inventory value + low-stock / days-of-cover
- New **"Lager"** view (Verktyg → tool): total inventory value at cost, low-stock and out-of-stock counts,
  and a list sorted most-urgent-first with **days of cover** (stock ÷ 30-day sales velocity), stock, value and a status badge.
- Backend `inventory` endpoint pulls WooCommerce stock, joins your COGS, and computes velocity from 30 days of orders. Cached 30 min.
- Verified: 84 200 kr value, 3 low + 1 out; NiteGuard (Slut, 0 days) → AquaFlow (8 days) → SleepMask (12 days) → CozyBlanket (OK).

## ✅ G6 · Daily P&L snapshots
- New `profit_snapshots` table + a `daily_pnl_series()` that computes each day's net profit (refund-aware, with per-day ad spend + pro-rated fixed).
- **Verktyg → "Frys P&L-historik"** backfills the last 60 days on demand; a secret-guarded `snapshot-cron` freezes yesterday nightly.
- Freezing history makes past months stable and fast, and powers the streak below.
- Verified: backfill stored 60 days.

## ✅ A8 · Profitable-day streak
- A **"🔥 X lönsamma dagar i rad"** badge in the focus hero — consecutive profitable days from the daily snapshots.
- The `streak` endpoint auto-backfills ~30 days if history is thin, so it works on first use.
- Verified: "🔥 7 lönsamma dagar i rad".

## New endpoints
| Action | Auth | Purpose |
|---|---|---|
| `inventory` | user | stock value, low-stock, days-of-cover (G2); cached 30 min |
| `snapshots` (`&days=` / `&backfill=`) | user | read or backfill daily P&L snapshots (G6) |
| `snapshot-cron` | secret | freeze one day's P&L (default yesterday) (G6) |
| `streak` | user | profitable-day streak (A8) |

## New table
- `profit_snapshots (snap_date PK, revenue, net_profit, …)` — auto-created.

## Cron setup (optional, for nightly snapshots)
Add to Hostinger Cron (uses the same secret as the daily summary — save it once in Settings → Allmänt):
`curl -s "https://profit.klivrapps.com/api.php?action=snapshot-cron&secret=…"`

## Note
The `inventory` and first `streak`/`snapshots backfill` calls each fetch up to 30–60 days of orders — cached
afterwards, but the first uncached load on a high-volume store can be heavy. Worth a look on staging.

## Verification
`php -l` clean · 26/26 unit tests pass · all inline JS parses · page boots with **zero console errors** ·
G2 tiles/list, G6 backfill, and A8 streak badge all exercised in-browser.
