# Profit Tracker v2.5.0 — comparisons, break-even, nudges & scheduled summary

Four features: **F1** period comparison, **F2** break-even panel, **A4** nudge center, **A5** scheduled summary.
All verified in-browser except A5's live send (needs a real webhook + cron).

## ✅ F1 · Period-over-period comparison
- Every dashboard load now also computes the **previous equivalent period** and shows a Δ chip on
  Revenue, Orders, AOV, Net profit (▲ green / ▼ red / flat) and Margin (percentage-point change).
- Backend: `compute_revenue` refactored into a reusable `revenue_payload()`; the endpoint returns
  `previous`, `delta` and `compare_range` when called with `&compare=1` (cache key namespaced).
- Verified: +25 % revenue, +33 % orders, −11 % net profit, −2,1 pp margin rendered correctly.

## ✅ F2 · Break-even panel
- A dashboard card: **blended break-even ROAS** (revenue ÷ all-non-ad-cost profit), **contribution margin %**,
  and **how many orders cover the period's fixed costs**. Traffic-lit, hidden until costs are configured.
- Verified: BE ROAS 3,3×, contribution 62,3 %, "2 orders cover 1 000 kr fixed".

## ✅ A4 · Nudge center
- Turns passive warnings into **one-tap actions**, shown between the focus hero and the details (visible even in focus mode):
  missing COGS → product costs · unconfigured costs → setup · double-counted ad spend → ad settings ·
  low ROAS → ROAS-vs-Break-even · negative net → scroll to break-even.
- Verified: nudges render with severity stripes and the taps navigate (COGS nudge → Settings → Produkter).

## ✅ A5 · Scheduled daily/weekly summary (server-side)
- The Slack/Telegram/Discord summary can now run **on a schedule without the app open**.
- Backend: `summary-settings` (GET/POST — store name, schedule, webhook saved server-side) and a
  secret-guarded `summary-cron` endpoint that computes yesterday's numbers and posts them.
- Settings → Allmänt: store name + schedule + **"Spara på servern & hämta cron-URL"**; paste the cron URL into Hostinger.
- ⚠️ The actual outbound POST is **deploy-tested** (needs a real webhook URL + cron). Settings/round-trip logic is done.

## New endpoints
| Action | Auth | Purpose |
|---|---|---|
| `revenue` `&compare=1` | user | now returns previous-period `delta` (F1) |
| `summary-settings` (GET/POST) | user | store name, schedule, server-side webhook (A5) |
| `summary-cron` | secret | scheduled summary → webhook (A5) |

## Cron setup for the scheduled summary
1. Settings → Allmänt → paste your Slack/Telegram webhook → set schedule → **Spara på servern**.
2. Copy the shown cron URL and add it to Hostinger Cron Jobs (e.g. daily 08:00):
   `curl -s "https://profit.klivrapps.com/api.php?action=summary-cron&secret=…"`
   (add `&period=7d` on a weekly job).

## Verification
`php -l` clean · 26/26 unit tests pass · all inline JS parses · page boots with **zero console errors** ·
F1 deltas, F2 panel, A4 nudges + one-tap nav all exercised in-browser.
