# Profit Tracker v2.11.7

## Meta ad spend now auto-syncs with the selected period (real root-cause fix)

Diagnosis (verified against the live Meta Ads API for account **SM 1**): the account
truly spent **kr 12 507.81** over the last 30 days, but the dashboard showed only
**kr 4 112**. The dashboard reads ad spend from the app's own `profit_adspend` table,
and that table was only ~1/3 populated: the old builds only ever pulled a 1–2 day
window per sync, and **selecting a longer period never triggered a sync** — so most of
the 30-day window was simply never fetched into the database. It was not a
calculation error and not pagination (only ~166 ad-day rows exist for the period).

### What changed
- **Selecting a period now syncs that exact date range automatically.** Pick 7d / 14d /
  30d / MTD / custom and the app pulls Meta for that window and mirrors it into ad
  spend, then reloads the KPIs — no separate sync step. The opening period is also
  auto-synced on app start.
- **Lightweight auto-sync.** Automatic period syncs fetch spend only and skip the slow
  per-ad status/budget refresh, so switching periods stays fast. The manual refresh
  button (top right) still runs a full sync including statuses and budgets.
- Auto-sync is guarded against overlap and throttled to at most once per 20 seconds.
- **Pagination hardened:** insights now request 500 rows per page and allow up to 100
  pages, so long ranges on large accounts are never truncated.

### Recommended for durable freshness
Add the cron URLs from **Settings › Annonsering** in Hostinger so the database stays
current automatically even when the app isn't open.
