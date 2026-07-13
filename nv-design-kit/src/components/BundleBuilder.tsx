import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';
import { PLACEHOLDER_IMAGE } from '../placeholder';

/** One bundle tier: how many items to pick and the discount that unlocks. */
export interface BundleTier {
  /** Tier label (e.g. "3-pack"). */
  label?: string;
  /** Number of items to pick for this tier. */
  qty?: number;
  /** Discount percent applied at this tier. */
  discount?: number;
  /** WooCommerce coupon code for the real discount. */
  coupon?: string;
}

/** One selectable product in the bundle grid. */
export interface BundleProduct {
  /** WooCommerce product id. */
  id?: number;
  /** Product name. */
  name?: string;
  /** Numeric unit price (used for the running total). */
  price?: number;
  /** Display price markup/text (e.g. "299 kr"). */
  priceHtml?: string;
  /** Product image URL. */
  img?: string;
}

export interface BundleBuilderProps {
  /** Small uppercase overline above the heading. */
  eyebrow?: string;
  /** Section heading. */
  heading?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** The bundle tiers (first is active by default). */
  tiers?: BundleTier[];
  /** The selectable products. */
  products?: BundleProduct[];
  /** Accent color. */
  accent?: string;
  /** Add-to-cart button label. */
  ctaLabel?: string;
  /** Product grid columns. */
  cols?: '2' | '3' | '4';
  /** Currency symbol (data attribute for the cart script). */
  symbol?: string;
  /** Price decimals (data attribute for the cart script). */
  decimals?: number;
}

const DEFAULT_TIERS: BundleTier[] = [
  { label: '2-pack', qty: 2, discount: 10, coupon: '' },
  { label: '3-pack', qty: 3, discount: 15, coupon: '' },
  { label: '4-pack', qty: 4, discount: 25, coupon: '' },
];

const DEFAULT_PRODUCTS: BundleProduct[] = [
  { id: 1, name: 'PadelFlex™ Grepp', price: 199, priceHtml: '199 kr', img: PLACEHOLDER_IMAGE },
  { id: 2, name: 'PadelFlex™ Skydd', price: 149, priceHtml: '149 kr', img: PLACEHOLDER_IMAGE },
  { id: 3, name: 'PadelFlex™ Boll', price: 99, priceHtml: '99 kr', img: PLACEHOLDER_IMAGE },
];

/** Mirror the PHP tier-save formatting: number_format(d,1) then trim trailing zeros/dot. */
function formatSave(discount: number): string {
  return discount.toFixed(1).replace(/0+$/, '').replace(/\.$/, '');
}

/**
 * NV: Bundle Builder — "build your own bundle" grid where shoppers pick a tier
 * (2/3/4-pack), add products with +/- steppers and see a running subtotal /
 * savings / total. Rendered here in its static default state (first tier active,
 * empty counts, disabled CTA — no cart JS). Mirrors the `nv-bundle-builder`
 * Elementor widget.
 */
export function BundleBuilder({
  eyebrow = 'BYGG DITT EGET PAKET',
  heading = 'Välj dina favoriter och spara mer',
  headlineStyle = 'default',
  tiers = DEFAULT_TIERS,
  products = DEFAULT_PRODUCTS,
  accent = '#312E81',
  ctaLabel = 'Lägg paket i varukorg',
  cols = '3',
  symbol = 'kr',
  decimals = 0,
}: BundleBuilderProps) {
  const style: CSSVars = {
    '--nv-bb-cols': cols,
    '--nv-bb-accent': accent,
  };

  return (
    <div className="nv-pw-bb" data-nv-bundle="" data-symbol={symbol} data-decimals={decimals} style={style}>
      {eyebrow !== '' && <span className="nv-pw-bb__eyebrow">{eyebrow}</span>}
      {heading !== '' && <h3 className={`nv-pw-bb__heading${headlineMod(headlineStyle)}`}>{renderHeadline(heading)}</h3>}

      <div className="nv-pw-bb__tiers" role="tablist">
        {tiers.map((t, i) => {
          const qty = Math.max(1, t.qty ?? 1);
          const discount = Math.max(0, Math.min(90, t.discount ?? 0));
          return (
            <button
              type="button"
              className={`nv-pw-bb__tier${i === 0 ? ' is-active' : ''}`}
              data-nv-tier={i}
              data-qty={qty}
              data-discount={discount}
              data-coupon={(t.coupon ?? '').trim()}
              key={i}
            >
              <span className="nv-pw-bb__tier-label">{(t.label ?? '').trim()}</span>
              {discount > 0 && <span className="nv-pw-bb__tier-save">−{formatSave(discount)}%</span>}
            </button>
          );
        })}
      </div>

      <p className="nv-pw-bb__hint" data-nv-hint=""></p>

      <div className="nv-pw-bb__grid">
        {products.map((p, i) => (
          <div className="nv-pw-bb__product" data-id={p.id ?? 0} data-price={p.price ?? 0} key={p.id ?? i}>
            <div className="nv-pw-bb__img"><img src={p.img ?? PLACEHOLDER_IMAGE} alt={p.name ?? ''} loading="lazy" /></div>
            <div className="nv-pw-bb__pinfo">
              <span className="nv-pw-bb__pname">{p.name}</span>
              <span className="nv-pw-bb__pprice">{p.priceHtml}</span>
            </div>
            <div className="nv-pw-bb__qty">
              <button type="button" className="nv-pw-bb__minus" data-nv-minus="" aria-label="Ta bort">−</button>
              <span className="nv-pw-bb__count" data-nv-count="">0</span>
              <button type="button" className="nv-pw-bb__plus" data-nv-plus="" aria-label="Lägg till">+</button>
            </div>
          </div>
        ))}
      </div>

      <div className="nv-pw-bb__summary">
        <div className="nv-pw-bb__totals">
          <div className="nv-pw-bb__row"><span>Delsumma</span><span data-nv-subtotal="">—</span></div>
          <div className="nv-pw-bb__row nv-pw-bb__row--save"><span>Du sparar</span><span data-nv-savings="">—</span></div>
          <div className="nv-pw-bb__row nv-pw-bb__row--total"><span>Totalt</span><span data-nv-total="">—</span></div>
        </div>
        <button type="button" className="nv-pw-bb__cta" data-nv-add="" disabled>{ctaLabel}</button>
        <p className="nv-pw-bb__msg" data-nv-msg="" role="status"></p>
      </div>
    </div>
  );
}
