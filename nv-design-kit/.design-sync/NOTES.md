# NV Design Kit — sync notes

## What this is
A net-new React library that mirrors the **AS Product Widgets** Elementor plugin
(`../nv-product-widgets/`). Each `src/components/<Name>.tsx` reproduces the exact
BEM markup of the matching PHP widget's `render()`, and `src/styles/nv-kit.css`
is a verbatim copy of `nv-product-widgets/assets/css/nv-product-widgets.css` plus
a `:root` block of brand-token defaults. Fidelity source of truth = the PHP.

## Toolchain
- Build: `npm run build` = esbuild bundle (`dist/index.es.js`, React external) + `tsc --emitDeclarationOnly` (`.d.ts`).
- Node 22, npm 10. React 18 (peer).
- Render checks (validate/capture) need chromium. This env has it cached at
  `/opt/pw-browsers/chromium_headless_shell-1194/chrome-linux/headless_shell`;
  pass it via `DS_CHROMIUM_PATH=<that path>`. Use the **headless_shell** binary,
  not `chromium-1194/chrome-linux/chrome` (full chrome errors: "Old Headless
  mode has been removed"). `playwright@1.47.0` matches the cached build 1194.
- validate and capture take >2min (cold browser + 18 previews) — run them
  backgrounded, not in a 2-min foreground shell.

## Decisions / gotchas
- **FAQ → `Faq`**: the converter's component heuristic (`dts.mjs isComponentName`)
  rejects all-caps names as constants, so the export is `Faq`, file `Faq.tsx`.
- **Images**: never remote URLs (egress blocked here). Image-bearing components
  default to `PLACEHOLDER_IMAGE` (`src/placeholder.ts`, a branded data-URI SVG,
  re-exported from the package for previews).
- **Fonts** load via a remote Google Fonts `@import` in the CSS → validate prints
  `[FONT_REMOTE]` (informational, not missing).
- Interactive widgets are rendered as their static default visual state
  (QuantityBreaks selected tier, BundleBuilder pre-add zeros); BeforeAfter and
  Faq use small SSR-safe `useState`/native `<details>`. No cart/side-cart JS.

## Known render warns
- None outstanding. All 18 previews render clean; 4 list components
  (PricingTable, ReviewWall, Testimonials, TrustpilotWall) only render via their
  authored previews (their floor cards came up empty because the floor card
  injects synthetic empty array props) — expected, not a warn.

## Re-sync risks
- **StatsCounter default copy is mixed French/Swedish** (`COMMANDES`,
  `DE SATISFACTION`) — faithfully copied from the PHP widget default. If the PHP
  default is ever changed to Swedish, update `StatsCounter.tsx` to match.
- The kit is a **hand-maintained mirror**: if a PHP widget's markup or style-var
  defaults change, the corresponding `.tsx` must be updated by hand — there is no
  automatic link. Diff `../nv-product-widgets/` on re-sync.
- `nv-kit.css` is a copy — re-copy it from the plugin if the plugin CSS changes.
- Preview variant props (e.g. `Testimonials layout="marquee"`, `Hero
  layout="minimal"`) assume those enum values still exist in the component props.
