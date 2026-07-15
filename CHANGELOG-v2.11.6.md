# Profit Tracker v2.11.6

## Meta Ads sync now matches the selected period (fixes under-reported spend)

- **Fixes the "half the spend" bug.** Every sync (top refresh, the dashboard Meta
  panel, and Settings › Annonsering › *Synca nu*) previously asked Meta for a fixed
  1‑day window regardless of the period shown on the dashboard. When you looked at
  7d / 14d / 30d / MTD, only the last day or two were ever refreshed into the
  database, so the reported spend came out roughly half of what Meta Ads Manager
  showed for the same range. Sync now fetches the **full date range of the selected
  period**, so ad spend, ROAS, CPA and net profit line up with Meta.
- Custom date ranges are now honoured by the Meta panel too.

## Single date/sync control (removes the duplicate input point)

- Removed the **separate period tabs and *Synca nu* button** inside the *Meta Ads
  Performance* panel. The panel now follows the single period selector and refresh
  button at the top of the dashboard — one place to pick the range, one place to sync.

## Quick overview at the top of the dashboard

- Added a **Snabböversikt** strip that groups the most important KPIs for an
  at‑a‑glance read: Omsättning (revenue), Vinst/Förlust (profit — green/red by sign),
  COGS, Annonsutgift (ad spend), a single **Övriga avgifter** card combining payment
  fees + shipping + fixed costs, plus ROAS, CPA and AOV.
- The detailed breakdown remains below for drill‑down.
