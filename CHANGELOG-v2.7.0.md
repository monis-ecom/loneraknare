# Profit Tracker v2.7.0 — customers, blended efficiency & undo

Three features: **F5** new-vs-returning + LTV, **F3** blended MER + CAC, **A9** undo toasts. All browser-verified.

## ✅ F5 · New vs returning + LTV  &  ✅ F3 · Blended MER + CAC
- New **"Kunder & effektivitet"** view (Verktyg → tool). One `customers` endpoint powers both:
  - **F5:** active / new / returning customers, **repeat rate**, **LTV** (avg revenue per customer, 365-day basis),
    avg orders per customer, and a new-vs-returning split bar. "New" = a customer whose *first order ever*
    (within a 365-day lookback) falls inside the period.
  - **F3:** **MER** (blended = total revenue ÷ total ad spend) and **CAC** (ad spend ÷ new customers), traffic-lit.
- Period tabs (7d/14d/30d/MTD), cached 1h. Verified: 120 active (78 new / 42 returning), 35 % repeat, LTV 640 kr, MER 4,2×, CAC 192 kr.

## ✅ A9 · Undo toasts
- Deleting an ad-spend entry no longer shows a scary `confirm()` — it deletes immediately and shows an
  **"Annonsutgift raderad · Ångra"** toast (6 s). Tapping **Ångra** re-creates the entry exactly.
- New reusable `toastUndo(message, undoFn)` helper for future destructive actions.
- Verified end-to-end: delete fires, undo toast shows, "Ångra" re-posts the full entry.

## New endpoint
| Action | Auth | Purpose |
|---|---|---|
| `customers` `&period=` | user | new/returning, repeat rate, LTV, MER, CAC (F5 + F3); cached 1h |

## Note on the 365-day lookback
The customers report fetches up to a year of orders to establish each customer's first-order date. It's
cached for an hour, but on very high-volume stores that first uncached load can be heavy — worth a look on staging.

## Verification
`php -l` clean · 26/26 unit tests pass · all inline JS parses · page boots with **zero console errors** ·
F5/F3 metrics rendered and A9 delete→undo→restore all exercised in-browser.
