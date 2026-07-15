# Profit Tracker v2.11.0 — VAT report, multi-store & ad-spend import

The last three build features: **G3** VAT report, **G5** multi-store, **G1** Google/TikTok spend import.
All browser-verified. This leaves **only S1 (secret rotation)** open on the whole roadmap.

## ✅ G3 · VAT (moms) report
- New Verktyg **"Momsrapport"** view: output VAT to remit, plus the key Swedish momsdeklaration boxes —
  **05** sales ex-VAT, **10** output VAT, **21** EU-service purchases, **30** reverse-charge output, **48** reverse-charge deduction.
- Ad spend to foreign platforms is handled as **omvänd skattskyldighet** (reverse charge, nets to zero) — the roadmap's ad-spend reverse-charge ask.
- Verified: 18 750 kr output VAT; reverse charge 25 % of 15 000 kr = 3 750 kr in boxes 30 & 48.

## ✅ G5 · Multi-store
- New `profit_stores` table (seeded from your existing config) + Settings → Allmänt → **Butiker**:
  add stores, **switch active store** (all WooCommerce reads use it), delete. Secrets are stored server-side and never returned to the browser.
- Cache is **namespaced per store** so switching shows the right data.
- ⚠️ Cost/fee/fixed config is currently **shared** across stores (best for stores with distinct catalogs) — noted in the UI; per-store cost isolation is a future step.
- Verified: store list renders, activation switches and reloads.
- **Bug fixed:** store actions originally used `apiCall('stores?op=…')`, which URL-encoded into the `action` param and never routed — now passed via the query option.

## ✅ G1 · Google/TikTok ad-spend import
- Rather than fragile native OAuth clients (Google Ads / TikTok Marketing APIs — not buildable/testable here),
  a robust **import endpoint**: Settings → Annonsering → **Automatisk import**. Point a Make/Zapier/n8n flow at the
  secret-guarded URL and POST daily spend as JSON, or paste an export manually. De-duplicated per channel+date+campaign.
- Verified: pasting `[{date,channel:"Google Ads",amount:350},{…TikTok…}]` imported 2 rows.

## New endpoints
| Action | Auth | Purpose |
|---|---|---|
| `vat-report` `&period=` | user | VAT boxes + reverse charge (G3) |
| `stores` (GET / POST `op=add\|activate\|delete`) | user / admin | multi-store management (G5) |
| `adspend-import` | secret or user | upsert imported ad spend (G1) |

## New tables
- `profit_stores` — auto-created, seeded from `WC_BASE`/`WC_KEY`/`WC_SECRET`.

## G1 automation setup
1. Settings → Allmänt → save the scheduled summary once (generates the shared secret).
2. Settings → Annonsering → copy the **Import-URL**.
3. In Make/Zapier: daily, pull spend from Google Ads / TikTok → HTTP POST that URL with a JSON array of
   `{date, channel, campaign, amount, impressions, clicks}`.

## Verification
`php -l` clean · 26/26 unit tests pass · all inline JS parses · page boots with **zero console errors** ·
G3 boxes, G5 list+activate (post-fix), and G1 import all exercised in-browser.

**All feature milestones are now complete. The only remaining roadmap item is S1 — rotate your live secrets.**
