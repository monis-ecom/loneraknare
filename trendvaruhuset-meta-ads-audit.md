# Meta Ads Audit — Trendvaruhuset (trendvaruhuset.se)

**Audit date:** 2026-07-29
**Facebook Page:** Trendvaruhuset — page ID `104865018468944`
**Market:** Sweden (SE), currency SEK
**Active ads in Meta Ad Library:** **165**
**Distinct products / offers being advertised:** **16 clusters (18 individual products, counting the 3-product bundle carousel)**

Source: Meta Ad Library (public, `ad_active_status=ACTIVE`, country SE).

> **Note on account ownership:** this Page is *not* under either of the ad accounts
> available in this session (`SM 1` / `SM 2`, business "Sofia's Market"). Trendvaruhuset is
> therefore audited as a third party from public Ad Library data — no spend, ROAS, CPA or
> audience data is available, only creative-level metadata.

---

## Products currently being advertised

Sorted newest launch first. "Active ads" is the number of live ads whose text matches that
product; the authoritative page total is 165.

| # | Product / offer (ad headline) | Active ads | First launched | Landing page |
|---|---|---|---|---|
| 1 | ✨ Mysljus för höst & vinter – utan brandrisk! ✨ (rechargeable flameless LED candles) | 6 | 2026-07-28 | `/produkt/uppladdningsbara-vaxljus-skapa-host-vintermys-varje-kvall/` — probable, unconfirmed |
| 2 | **ShadeGuard** | 3 | 2026-07-28 | unresolved |
| 3 | **GlowShelf** (lit storage / shelf) | 6 | 2026-07-22 | unresolved |
| 4 | **EasyReach** | 2 | 2026-07-14 | unresolved |
| 5 | 🥵 Trött på varma rum? Upptäck framtidens takfläkt! (2-i-1 Fläkt & Taklampa med Fjärrkontroll) | 9 | 2026-07-07 | **https://trendvaruhuset.se/produkt/takflakt/** ✔ |
| 6 | **BohoBelle** | 3 | 2026-07-06 | unresolved |
| 7 | **Tankini** (swimwear) | 30 | 2026-06-01 | unresolved |
| 8 | **Memoria** | 17 | 2026-07-03 | unresolved |
| 9 | 🌿 Ordning har aldrig varit så snygg! (storage / bathroom organisation) | 5 | 2026-07-03 | unresolved |
| 10 | Mysigare kvällar, oavsett var du är (portable light / lantern) | 12 | 2026-07-01 | unresolved |
| 11 | Ett eget litet hus – där fantasin får flytta in! (kids' play house) | 9 | 2026-07-01 | unresolved |
| 12 | Bundle carousel — **Trådlösa sovhörlurar** / **Smart tandborst & tandkrämshållare** / **Sällskapsspelet som förenar familjen** | 6 | 2026-07-01 | unresolved (3 separate products) |
| 13 | **OceanDream Havsprojektor** (ocean-wave projector) | 5 | 2026-06-24 | unresolved |
| 14 | 8 VIRALA SQUISHIES – NU ENDAST 499 KR / ALLA VIRALA FAVORITER I EN ENDA BOX! | 16 | 2026-06-22 | unresolved |
| 15 | **Hope & Grace Armband** | 16 | 2026-06-22 | **https://trendvaruhuset.se/produkt/hope-grace-armband/** ✔ |
| 16 | **Elektrisk Sekatör 20v** (also runs as "20v Sekatör") | 21 | 2026-06-02 | **https://trendvaruhuset.se/produkt/elektrisk-sekator/** ✔ |

### Why most landing pages are unresolved

Three independent blockers, all outside the ad data itself:

1. **The Meta Ad Library API does not return the destination URL.** It exposes the ad's
   headline, page, dates and a snapshot link — not `link_url`. Landing URLs are only
   available through the *owning* ad account, and this Page is not in this session's accounts.
2. **`trendvaruhuset.se` is blocked by this environment's egress policy** (the proxy answers
   `403` to `CONNECT trendvaruhuset.se:443`), so the store, its sitemap and its REST API
   cannot be read to match products to slugs.
3. **`facebook.com` is blocked by the same policy**, so the ad snapshot pages — which *do*
   show the destination URL — cannot be opened server-side either.

The three confirmed URLs above, plus the probable one for the candles, were recovered via a
search index. The 2026 products (ShadeGuard, GlowShelf, Tankini, Memoria, OceanDream, etc.)
are too recently launched to be indexed.

**To complete the URL column,** either allow `trendvaruhuset.se` (or `facebook.com`) in the
environment's network policy and re-run, or open the snapshot links below in a browser — each
shows its destination URL directly.

The store's URL pattern is confirmed as `https://trendvaruhuset.se/produkt/<slug>/`
(WooCommerce), with categories at `/produkt-kategori/<slug>/`.

---

## Ad Library snapshot links (one live ad per product)

| Product | Ad ID | Snapshot |
|---|---|---|
| Mysljus höst & vinter | 1572191777582796 | https://www.facebook.com/ads/library/?id=1572191777582796 |
| ShadeGuard | 881266911369866 | https://www.facebook.com/ads/library/?id=881266911369866 |
| GlowShelf | 1005714365609688 | https://www.facebook.com/ads/library/?id=1005714365609688 |
| EasyReach | 1560280289031822 | https://www.facebook.com/ads/library/?id=1560280289031822 |
| Takfläkt 2-i-1 | 1628833832181427 | https://www.facebook.com/ads/library/?id=1628833832181427 |
| BohoBelle | 1024048476994419 | https://www.facebook.com/ads/library/?id=1024048476994419 |
| Tankini | 1540553861040091 | https://www.facebook.com/ads/library/?id=1540553861040091 |
| Memoria | 1639660451500425 | https://www.facebook.com/ads/library/?id=1639660451500425 |
| Ordning har aldrig varit så snygg | 1065703055834774 | https://www.facebook.com/ads/library/?id=1065703055834774 |
| Mysigare kvällar | 1032252222497683 | https://www.facebook.com/ads/library/?id=1032252222497683 |
| Ett eget litet hus | 1749215669416925 | https://www.facebook.com/ads/library/?id=1749215669416925 |
| Bundle (hörlurar / tandborst / spel) | 1570704687778609 | https://www.facebook.com/ads/library/?id=1570704687778609 |
| OceanDream Havsprojektor | 1036415235464668 | https://www.facebook.com/ads/library/?id=1036415235464668 |
| 8 Virala Squishies | 1819430662405257 | https://www.facebook.com/ads/library/?id=1819430662405257 |
| Alla virala favoriter i en box | 1520470832891895 | https://www.facebook.com/ads/library/?id=1520470832891895 |
| Hope & Grace Armband | 1308034368206606 | https://www.facebook.com/ads/library/?id=1308034368206606 |
| Elektrisk Sekatör | 1522464759262796 | https://www.facebook.com/ads/library/?id=1522464759262796 |
| 20v Sekatör | 2078603179743159 | https://www.facebook.com/ads/library/?id=2078603179743159 |

Full page view: https://www.facebook.com/ads/library/?view_all_page_id=104865018468944

---

## Observations

**Creative strategy.** Every cluster is video-first, with carousel/DCO variants alongside
(headlines repeated with `|` separators are multi-card carousels). Nothing is static-image only.

**Duplication at ad level.** 165 ads across 16 offers ≈ 10 ads per offer, and the duplicates
are near-identical creatives launched within seconds of each other (e.g. six Mysljus ads all
created 2026-07-28, six ShadeGuard/GlowShelf ads within the same minute). This is the classic
one-creative-per-ad-set duplication pattern used to force Meta to spread budget, not genuine
creative variety.

**Where the volume actually sits.** Three offers carry over 40% of the active ads:
Tankini (30), Sekatör (21), Hope & Grace Armband (16) and Memoria (17). Sekatör has been
running since 2026-06-02 and Hope & Grace since 2026-06-22 — nearly two months live, which
for a dropship-style store is a strong signal both are profitable winners.

**Testing cadence.** New offers launched roughly weekly through July: 07-01 (three at once),
07-03, 07-06, 07-07, 07-14, 07-22, 07-28 (two). The newest launches (ShadeGuard, Mysljus)
are still at 3–6 ads each — early tests, not yet scaled.

**Seasonal pivot in progress.** The 2026-07-28 launches are explicitly autumn/winter
("Mysljus för höst & vinter"), while the summer offers (Tankini, Sekatör, poolside/garden)
are still live. They are pre-loading Q4 while summer winners keep spending.

**Category mix.** Home & lighting dominates (candles, GlowShelf, ceiling fan, lantern,
OceanDream projector), followed by garden/tools (Sekatör, EasyReach), then jewellery/gifting
(Hope & Grace, Memoria), kids/toys (play house, squishies) and a single apparel line (Tankini,
BohoBelle).

**Data caveat on third-party tooling.** WinningHunter's index for this brand is stale — its
latest record is 2025-07-16 and it reports `active_ad_count: 0` for trendvaruhuset.se. Its
historical set (235 ads, 2024–2025) is intact and does carry landing URLs, but none of the
16 offers above appear in it. Do not use WinningHunter to judge what this store is running now.

---

## Appendix — historical landing pages (2024–2025, from WinningHunter)

Confirms the URL pattern and shows the older catalogue. None of these are in the current
active set.

- https://trendvaruhuset.se/produkt/portabel-tvattmaskin/
- https://trendvaruhuset.se/produkt/multislice-pro-5-i-1-roterande-mandolin/
- https://trendvaruhuset.se/produkt/slider/
- https://trendvaruhuset.se/produkt/lo-toe/
- https://trendvaruhuset.se/produkt/ecostyle-bin-modern-atervinning-som-passar-ditt-hem/
- https://trendvaruhuset.se/produkt/clara-stilren-ribbad-glas-tumbler-med-handtag-lock-for-kalla-varma-drycker/
- https://trendvaruhuset.se/produkt/laddermate-stegtillbehor/
- https://trendvaruhuset.se/produkt/lazy-susan/
- https://trendvaruhuset.se/produkt/maxireach-teleskopstege/
- https://trendvaruhuset.se/produkt/resekudde-for-barn/
- https://trendvaruhuset.se/produkt/babybubbla-uppblasbar-babysits-for-sommarens-aventyr-mysiga-stunder/
- https://trendvaruhuset.se/produkt/kit-vattenpump/
- https://trendvaruhuset.se/produkt/sleepyrider-roadtripkudde/

---

## Method & limitations

- Enumerated via Meta Ad Library, filtered to `ACTIVE` + country `SE` + page
  `104865018468944`.
- The Ad Library API returns at most 50 ads per query with no pagination cursor, so the
  remaining ~115 active ads were reached with keyword-segmented queries; per-product counts
  are whole-word matches against ad text and can overlap slightly (the cluster counts sum to
  166 vs. the reported 165 total). The 165 figure and the product list are reliable; treat
  individual counts as ±1.
- No performance data (spend, impressions, ROAS, CPA, audiences) is obtainable for a Page
  outside your own ad accounts.
