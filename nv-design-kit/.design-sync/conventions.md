# NV Design Kit — how to build with it

This kit is the React mirror of **AS Product Widgets**, the Elementor section
library for Nordiska Varuhuset (store: nordiskavaruhuset.se, product PadelFlex™,
Swedish copy). Every component here emits the same markup and uses the same
stylesheet as a shipping WordPress widget, so a layout you compose maps 1:1 onto
what the store can actually publish.

## These are whole SECTIONS, not primitives

Each export is a complete, pre-composed landing-page section (a hero, a feature
row, a pricing table, a reviews wall). You build a page by **stacking sections
and setting their props** — you do not assemble them from smaller parts and you
do not write CSS classes. There is no `Button`/`Card`/`Stack`; there is `Hero`,
`Feature`, `PricingTable`, and so on. Compose top to bottom.

The 18 sections: `Hero`, `Feature`, `MediaHeadlineText`, `IconColumns`,
`BenefitsList`, `Steps`, `Guarantee`, `CtaBlock`, `Testimonials`, `ReviewWall`,
`TrustpilotWall`, `QuantityBreaks`, `PricingTable`, `BundleBuilder`,
`ComparisonGrid`, `BeforeAfter`, `Faq`, `StatsCounter`.

## Setup: none

No provider or theme wrapper. The kit stylesheet (with fonts Sora / Manrope /
Space Grotesk / Newsreader and the brand tokens) loads automatically — just
render the components. Every section ships realistic Swedish default content, so
`<Hero />` alone renders a full, on-brand hero. Override with props as needed.

## Style via PROPS, never classes

Two kinds of props:

- **Content**: `headline`, `subheadline`, `eyebrow`, `text`, `bullets`,
  `image`, `ctaText`, `ctaUrl`, repeater arrays (`items`, `plans`, `tiers`,
  `reviews`), etc. Read each component's `.d.ts` for its exact set.
- **Brand knobs** (common across sections): `accent` (highlights & buttons),
  `headingColor`, `textColor`, `bg`, `radius`, `imageSide` (`'left'|'right'`),
  and **`headlineStyle`** — `'default'` (Sora sans) or `'editorial'`
  (Newsreader serif-italic, the design-system display look).

Brand color/radius flow through CSS custom properties defined at `:root`:
`--nv-brand-accent` (#3B37C4), `--nv-brand-heading` (#14161D),
`--nv-brand-text` (#5B6070), `--nv-brand-radius` (14px), `--nv-brand-font`.
Change the palette store-wide by overriding those on `:root`; change one section
by passing its `accent` / `headingColor` / etc. prop. For a still image OR a
self-hosted video in `Feature` / `MediaHeadlineText`, pass `image` or `video`.

## Where the truth is

Read the bound `<Name>.d.ts` for the full prop contract and `<Name>.prompt.md`
for usage before styling a section. The stylesheet (`styles.css` and the
component CSS it imports) is the source of the look — the class names are the
widgets' own BEM (`nv-pw-hero__*`, `nv-pw-feat__*`) and are emitted by the
components; you never write them yourself.

## Idiomatic build

```tsx
import {
  Hero, IconColumns, Feature, QuantityBreaks, Testimonials, Guarantee, Faq,
} from 'nv-design-kit';

export default function ProductPage() {
  return (
    <main>
      <Hero headlineStyle="editorial" />
      <IconColumns />
      <Feature layout="split" imageSide="right" ctaText="Läs mer" />
      <QuantityBreaks />
      <Testimonials layout="marquee" />
      <Guarantee />
      <Faq />
    </main>
  );
}
```

Every section is full-bleed and self-contained — wrap them in your own page
container for max-width and vertical rhythm. Keep copy Swedish to match the brand.
