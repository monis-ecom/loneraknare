# Profit Tracker v2.6.0 — refund rate, setup wizard & quick-capture

Three features: **F4** refund-rate KPI, **A6** setup wizard, **A7** quick-capture. All browser-verified.

## ✅ F4 · Refund / return-rate KPI
- New **Återbetalningsgrad** stat in the details grid: refunds ÷ gross revenue.
- Backend adds `refund_rate` to the revenue payload and to the F1 comparison, so it also shows a
  period-over-period delta — **inverted** (more refunds is worse, so an increase shows red).
- Verified: 4,2 % with a red "▲ +1,5 pp" delta.

## ✅ A6 · Setup wizard / onboarding checklist
- A **"🚀 Kom igång"** card (below the nudge center, visible in focus mode) with a live checklist:
  connect store · add product costs · set fees & shipping · set a profit goal.
- Each incomplete step is a one-tap link to the right settings tab; the card shows "X / 4 klart",
  auto-hides when complete, and can be dismissed. Re-checks after every settings change.
- Verified: "1 / 4 klart", store step checked, others link to their tabs.

## ✅ A7 · Quick-capture
- A floating **+** button (always visible when logged in) opens a bottom sheet to add an
  **ad-spend** entry or a **fixed cost** in two taps — today's date prefilled, sensible defaults.
- Reuses the existing `adspend` / `costs` endpoints; refreshes the dashboard after saving.
- Verified: sheet opens, tab switch works, saving posts `{spend_date, channel:'Meta', amount:…}` and closes.

## Changes
- `revenue_payload` now returns `refund_rate`; F1 `delta` includes `refunded` + `refund_rate`.
- Frontend: refund-rate mini stat, `#setup-wizard`, quick-capture FAB + sheet; `setDelta` gained an
  `invert` option for "higher = worse" metrics.

## Verification
`php -l` clean · 26/26 unit tests pass · all inline JS parses · page boots with **zero console errors** ·
F4 stat + delta, A6 checklist, and A7 open→switch→save all exercised in-browser.
