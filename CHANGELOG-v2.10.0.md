# Profit Tracker v2.10.0 — BE ROAS milestone complete

Three features finish Milestone 5: **R4** custom target-ROAS, **R7** reverse price solver, **R8** unify + retire the ROAS app. All browser-verified.

## ✅ R4 · Custom target-ROAS
- The break-even calculator now has an **"Egen mål-ROAS"** field: type your actual/target ROAS and see the
  ad cost and profit per order at that exact level, plus above/below break-even.
- Verified: at 3× (price 607) → ad 202,33 kr → profit +89,09 kr · över break-even ✓.

## ✅ R7 · Reverse solver — "hitta pris"
- Given the costs above, solve for the **required selling price** to hit a target **margin %** or **break-even ROAS**.
  Tap the result to apply the price back into the calculator.
- Verified: target 60 % margin → 606,78 kr; target BE ROAS 2.0 → 650,91 kr (both exact on back-check). Apply works.

## ✅ R8 · Unify product economics + retire the standalone ROAS app
- New admin **"Importera från ROAS-appen"** action (Verktyg): reads the old `roas_products` table (same DB)
  and imports each product cost into `profit_costs`, so there's **one source of truth for COGS**.
  Idempotent; skips zero-cost rows; clears the revenue cache.
- Once imported, the standalone **roas.klivrapps.com** app is redundant and can be taken down (its open,
  unauthenticated API goes away — a security win). The calculator (R1) now lives inside Profit Tracker.
- Verified: import returned "5 imported (1 skipped)".

## New endpoint
| Action | Auth | Purpose |
|---|---|---|
| `migrate-roas` | admin | import `roas_products` costs → `profit_costs` (R8) |

## How to retire the old app (after importing)
1. Profit Tracker → Settings → Verktyg → **Importera från ROAS-appen** (verify your COGS afterwards).
2. Take down the `roas.klivrapps.com` deployment (delete its files / disable the subdomain).
3. Optionally drop the now-unused `roas_products` table.

## Verification
`php -l` clean · 26/26 unit tests pass · all inline JS parses · page boots with **zero console errors** ·
R4 result, R7 solve+apply (both modes), and R8 import all exercised in-browser.

**Milestone 5 (BE ROAS & Planning) is now 100 % complete.**
