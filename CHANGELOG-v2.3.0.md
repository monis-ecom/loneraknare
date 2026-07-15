# Profit Tracker v2.3.0 — Finance correctness + Break-Even ROAS

Backend is fully implemented and **verified with 26 passing unit tests** (`php tests/calc_test.php`)
and clean `php -l` on every file. Numbers you already rely on now behave correctly, and the
Break-Even ROAS planner (merged from the standalone ROAS app) is live.

## Done & tested (shipped in this build)

### Safety (M0)
- **Config hardening** — `.htaccess` now denies every `db-config*.php` and `*.example.php`, and 404s the
  `lib/` and `tests/` folders. Added `.gitignore` that excludes real config files. The app already prefers
  `db-config-profit.php` *outside* the web root (in-root file is only a fallback).
- **S3 · Config backup** — new `config-backup` admin endpoint returns a portable JSON of all costs, fees,
  fixed costs, shipping, VAT and status settings. *(A one-click download button in Settings is still to wire.)*
- ⚠️ **S1 · Secret rotation is still yours to do** — I can't rotate the DB / WooCommerce / admin passwords.
  Do it in Hostinger + WooCommerce and put the secrets in `db-config-profit.php` outside web root.

### Finance correctness (M1)
- **C1 · Refund-aware profit** — refunds now reduce revenue, VAT, COGS **and** units proportionally, so a
  refunded order no longer keeps its full product cost. Gross margin stays truthful across refunds.
  *(Fees & carrier shipping stay on the gross order — a deliberate, documented conservative choice.)*
- **C2 · Variation-level COGS** — new `profit_variation_costs` table; a variation's cost overrides the
  parent product cost everywhere COGS is computed (dashboard, product profit, trend, monthly, attribution).
- **C6 · Effective-dated cost history** — new `profit_cost_history` table; each order now uses the unit cost
  that applied **on its own date**, so last month's P&L stops drifting when supplier prices change.
- **C5 · Configurable counted statuses** — `counted_statuses` setting (default `completed,processing`;
  drops the risky `on-hold`). Returned/saved via the Costs endpoint.
- **C4 · Spend-overlap warning** — the dashboard payload now includes `spend_warnings` when manual and
  API-synced Meta spend collide on the same day (double-count risk).
- **C3 · Missing-COGS** — gaps are still counted (`cogs_gaps`) and the new catalog flags ⚠ products with no cost.
  *(The one-tap "fix now" nudge UI is still to wire.)*

### Break-Even ROAS & planning (M5 · R-tasks)
- **R6 · VAT-correct break-even** — computed on ex-VAT contribution (matches your actuals) and now includes
  the `other_costs` the old app silently ignored. `be_roas = price_incl ÷ contribution`, so it compares
  directly to Meta's ROAS.
- **R1 · Break-even calculator** — `beroas.html`: live sliders for price/COGS/shipping/fee/VAT/other →
  BE ROAS, ex-VAT margin, contribution, and a 1×–5× profit/loss scenario ladder, all traffic-lit.
- **R2 · Auto-fill from real data** — `break-even-catalog` endpoint joins your real WooCommerce products
  with real COGS; the calculator's product picker loads it so there's no double entry.
- **R3 · Actual-vs-break-even** — every `attribution-profit` row now returns `be_roas` + a
  `verdict` of `scale` / `hold` / `kill`. *(In-table badge rendering in the main dashboard is still to wire.)*

## New API endpoints
| Action | Auth | Purpose |
|---|---|---|
| `break-even` | user | Single-product BE ROAS from query params (R1/R6) |
| `break-even-catalog` | user | All products + real costs → BE ROAS (R2) |
| `config-backup` | admin | JSON backup of all cost/fee config (S3) |
| `attribution-profit` | user | now also returns `be_roas` + `verdict` per row (R3) |
| `costs` (POST) | user | now also saves `counted_statuses`, `vat_rate`, `variation_costs`, `cost_history` |

## New database tables (auto-created on first run)
- `profit_variation_costs (variation_id, product_id, unit_cost)`
- `profit_cost_history (cost_key, unit_cost, effective_from)`

## Frontend wired into index.html (v2.3.0)
- **R1** Break-even ROAS calculator — Verktyg → "Break-even ROAS-kalkylator" (live sliders, traffic-lit, product picker). Verified rendering + math in the integrated app (2.40× on the default numbers).
- **R3** "ROAS vs Break-even" view — Verktyg → lists every product's live ROAS vs break-even with a **Skala / Behåll / Pausa** badge (period tabs).
- **C5 + VAT** — Kostnader tab: counted-status checkboxes + VAT-rate field, saved via `costs`.
- **C4** — spend-overlap warning now appears in the dashboard anomaly banner.
- **C3** — the COGS-gap badge is a tap-to-fix that jumps straight to product costs.
- **S3** — "Säkerhetskopiera inställningar" button downloads the config JSON.
- Verified: `php -l` clean, 26/26 unit tests pass, all inline JS parses, page boots with **zero console errors**, calculator renders correctly in-app.

## C2 / C6 editors (v2.3.0 · shipped)
- **C2** — new `product-variations` endpoint lists a variable product's variations from WooCommerce; the "Varianter & historik" editor (Verktyg) shows a cost input per variation.
- **C6** — the same editor lets you add dated cost rows per product (date + cost, with delete), saved to `profit_cost_history`.
- Verified: `product-variations` lints clean; the editor overlay opens, the history-row add/fill/delete flow works, and the page boots with zero console errors.

## Remaining on the roadmap
- **S1** — rotate the DB / WooCommerce / admin secrets (your action, in Hostinger + WooCommerce).
- Milestones 2 (decision features), 3 (ADHD UX: order push, focus mode, …) and 4 (breadth) — not started.

## Files changed / added
- `api.php` — refactored compute paths onto the tested library; new endpoints & settings.
- `lib/profit_calc.php` — **new**, pure tested math (refund/variation/history/break-even/verdict).
- `tests/calc_test.php` — **new**, 26 assertions.
- `beroas.html` — **new**, the Break-Even ROAS calculator.
- `.htaccess`, `.gitignore` — hardened.
