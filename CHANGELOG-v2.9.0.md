# Profit Tracker v2.9.0 — sales heatmap, product benchmark & coupon profitability

Three features: **G4** day/hour heatmap, **R5** product benchmark, **F6** coupon profitability. All browser-verified.

## ✅ G4 · "När säljer du?" heatmap
- New Verktyg view: a **7 × 24 heatmap** of revenue by weekday × hour over the last 90 days, with a
  **"Bästa tiden"** callout for the single best slot. Darker = more revenue; hover a cell for kr + order count.
- Backend `heatmap` endpoint buckets orders by `date_created` weekday/hour. Cached 1h.
- Verified: Friday 19:00 peak rendered brightest; 168 cells; best-time callout correct.

## ✅ R5 · Product benchmark
- New Verktyg view ranking every product (real COGS) with **medals 🥇🥈🥉** and bars, sortable by
  **margin**, **break-even ROAS**, or **profit per unit**. Reuses the `break-even-catalog` endpoint.
- Verified: by margin (SleepMask 68 % → NiteGuard 52 % → AquaFlow 28 %); switching to BE ROAS reorders (lowest first).

## ✅ F6 · Coupon profitability
- New Verktyg view: per-coupon **uses, revenue, discount given, and profit** (contribution before ads),
  sorted by profit, with period tabs. Backend `coupons` endpoint reads WooCommerce `coupon_lines`.
- Verified: sommar20 +6 800 kr, welcome10 +9 200 kr, vip50 −500 kr (loss shown red).

## New endpoints
| Action | Auth | Purpose |
|---|---|---|
| `heatmap` `&days=` | user | weekday × hour revenue grid (G4); cached 1h |
| `coupons` `&period=` | user | per-coupon revenue/discount/profit (F6) |
| `break-even-catalog` | user | reused by the R5 benchmark UI |

## Note
`heatmap` (90 days) and `coupons` fetch orders over their window on first (uncached) load — heavier on
high-volume stores. Both are cached afterwards.

## Verification
`php -l` clean · 26/26 unit tests pass · all inline JS parses · page boots with **zero console errors** ·
G4 grid + best-time, R5 medal ranking + sort, and F6 coupon list all exercised in-browser.
