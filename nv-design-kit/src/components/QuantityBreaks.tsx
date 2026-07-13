import { headlineMod, type HeadlineStyle, type CSSVars } from '../types';

/** One variation option shown in a per-unit size dropdown (variable products only). */
export interface QuantityVariation {
  /** WooCommerce variation id. */
  id: number;
  /** Human label (e.g. "Small / Blue"). */
  label: string;
}

/** One volume/bundle tier: a quantity, its labels, prices and optional badge + free gift. */
export interface QuantityTier {
  /** How many units this tier adds to the cart. */
  qty?: number;
  /** Tier label (e.g. "2 par"). */
  label?: string;
  /** Sub-label under the tier label (e.g. "Du sparar 30%"). */
  sublabel?: string;
  /** Displayed price (e.g. "499 kr"). */
  price?: string;
  /** Struck-through original price (e.g. "739 kr"). */
  originalPrice?: string;
  /** Optional corner/inline badge (e.g. "Populärast"). */
  badge?: string;
  /** WooCommerce coupon code (coupon discount mode). */
  coupon?: string;
  /** Discount percent (automatic discount mode). */
  discountPercent?: number;
  /** Whether this tier offers a free gift. */
  giftEnabled?: boolean;
  /** Gift bar label (e.g. "+ GRATIS guide 🎁"). */
  giftLabel?: string;
  /** Struck-through gift value (e.g. "100 kr"). */
  giftValue?: string;
  /** WooCommerce product id of the free gift (0 = display-only). */
  giftProductId?: number;
  /** Marks this tier selected by default. */
  highlighted?: boolean;
}

export interface QuantityBreaksProps {
  /** Small centered heading above the tiers. */
  heading?: string;
  /** Product id these quantities apply to (0 = current product). */
  productId?: number;
  /** Whether the product is variable (reveals per-unit size dropdowns on the selected tier). */
  isVariable?: boolean;
  /** Available variations for the size dropdowns (variable products only). */
  variations?: QuantityVariation[];
  /** Variation group label (e.g. "Storlek"). */
  sizeLabel?: string;
  /** Placeholder option in each variation dropdown. */
  variationPlaceholder?: string;
  /** Add-to-cart button text. */
  buttonText?: string;
  /** Optional guarantee line under the button. */
  guaranteeText?: string;
  /** Discount method. */
  discountMode?: 'coupon' | 'auto' | 'nvcc';
  /** After add-to-cart behaviour. */
  afterAdd?: 'side_cart' | 'stay' | 'redirect_cart';
  /** Optional CSS selector of a side-cart trigger. */
  cartSelector?: string;
  /** Badge placement relative to the card. */
  badgePosition?: 'top-right' | 'top-left' | 'top-center' | 'inline';
  /** The pricing tiers. */
  tiers?: QuantityTier[];
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Accent color (selected border / radio). */
  accent?: string;
  /** Selected card background. */
  selectedBg?: string;
  /** Badge background. */
  badgeBg?: string;
  /** Gift bar background. */
  giftBg?: string;
  /** Add-to-cart button background. */
  buttonBg?: string;
  /** Add-to-cart button text color. */
  buttonColor?: string;
}

const DEFAULT_TIERS: QuantityTier[] = [
  { qty: 1, label: '1 par', sublabel: 'Du sparar 20%', price: '299 kr', originalPrice: '369 kr', highlighted: false },
  { qty: 2, label: '2 par', sublabel: 'Du sparar 30%', price: '499 kr', originalPrice: '739 kr', badge: 'Populärast', highlighted: true, giftEnabled: true, giftLabel: '+ GRATIS guide 🎁', giftValue: '100 kr' },
  { qty: 3, label: '3 par', sublabel: 'Du sparar 45%', price: '599 kr', originalPrice: '1109 kr', highlighted: false },
];

/** Mirror the PHP price → numeric total parse for the exact-price (NV Commerce Core) mode. */
function cartTotalOf(price: string): string {
  const normalized = price.replace(/[  ]/g, '').replace(/,/g, '.').replace(/[^0-9.]/g, '');
  return String(Number(normalized) || 0);
}

/**
 * NV: Quantity Breaks — a CourtX-style volume/bundle discount selector: pick a
 * quantity tier (radio) and the selected tier reveals per-unit size dropdowns, a
 * free-gift bar and one add-to-cart button. Rendered here in its static default
 * visual state (the highlighted tier shown selected). Mirrors the
 * `nv-quantity-breaks` Elementor widget.
 */
export function QuantityBreaks({
  heading = 'Erbjudande',
  productId = 0,
  isVariable = false,
  variations = [],
  sizeLabel = 'Storlek',
  variationPlaceholder = 'välj storlek',
  buttonText = 'Lägg i varukorg',
  guaranteeText = '60 dagars öppet köp — nöjd eller pengarna tillbaka',
  discountMode = 'coupon',
  afterAdd = 'side_cart',
  cartSelector = '',
  badgePosition = 'top-right',
  tiers = DEFAULT_TIERS,
  headlineStyle = 'default',
  accent = '#3B37C4',
  selectedBg = '#E9E9FB',
  badgeBg = '#2A2550',
  giftBg = '#2A2550',
  buttonBg = '#6C63E0',
  buttonColor = '#FFFFFF',
}: QuantityBreaksProps) {
  const style: CSSVars = {
    '--nv-qb-accent': accent,
    '--nv-qb-sel-bg': selectedBg,
    '--nv-qb-badge-bg': badgeBg,
    '--nv-qb-gift-bg': giftBg,
  };
  const hasVariations = isVariable && variations.length > 0;

  let defaultIndex = 0;
  for (let i = 0; i < tiers.length; i++) {
    if (tiers[i].highlighted) { defaultIndex = i; break; }
  }

  return (
    <div
      className={`nv-pw-qb nv-pw-qb--badge-${badgePosition}`}
      data-nv-qb=""
      data-product-id={String(productId)}
      data-variable={isVariable ? '1' : '0'}
      data-discount-mode={discountMode}
      data-after-add={afterAdd}
      {...(cartSelector !== '' ? { 'data-cart-selector': cartSelector } : {})}
      style={style}
    >
      {heading !== '' && <div className={`nv-pw-qb__heading${headlineMod(headlineStyle)}`}>{heading}</div>}

      <div className="nv-pw-qb__tiers">
        {tiers.map((t, i) => {
          const qty = Math.max(1, t.qty ?? 1);
          const label = (t.label ?? '').trim();
          const sublabel = (t.sublabel ?? '').trim();
          const price = (t.price ?? '').trim();
          const orig = (t.originalPrice ?? '').trim();
          const badge = (t.badge ?? '').trim();
          const coupon = (t.coupon ?? '').trim();
          const discountPct = Math.max(0, Math.min(90, t.discountPercent ?? 0));
          const giftOn = !!t.giftEnabled;
          const giftLabel = (t.giftLabel ?? '').trim();
          const giftValue = (t.giftValue ?? '').trim();
          const giftPid = t.giftProductId ?? 0;
          const selected = i === defaultIndex;

          return (
            <div
              className={`nv-pw-qb__tier${selected ? ' is-selected' : ''}`}
              data-nv-qb-tier={i}
              data-qty={qty}
              data-coupon={coupon}
              data-discount={String(discountPct)}
              data-cart-total={cartTotalOf(price)}
              data-label={label}
              data-gift-id={String(giftOn ? giftPid : 0)}
              key={i}
            >
              {badge !== '' && <span className="nv-pw-qb__badge">{badge}</span>}
              <div className="nv-pw-qb__row">
                <span className="nv-pw-qb__radio" aria-hidden="true"></span>
                <span className="nv-pw-qb__info">
                  <span className="nv-pw-qb__label">{label}</span>
                  {sublabel !== '' && <span className="nv-pw-qb__sub">{sublabel}</span>}
                </span>
                <span className="nv-pw-qb__prices">
                  {price !== '' && <span className="nv-pw-qb__price">{price}</span>}
                  {orig !== '' && <span className="nv-pw-qb__orig">{orig}</span>}
                </span>
              </div>

              {hasVariations && (
                <div className="nv-pw-qb__variations" data-nv-qb-variations="">
                  {sizeLabel !== '' && <span className="nv-pw-qb__variations-label">{sizeLabel}</span>}
                  {Array.from({ length: qty }, (_, u) => (
                    <label className="nv-pw-qb__vrow" key={u}>
                      {qty > 1 && <span className="nv-pw-qb__vnum">#{u + 1}</span>}
                      <select className="nv-pw-qb__vselect" data-nv-qb-unit={u} defaultValue="">
                        <option value="">{variationPlaceholder}</option>
                        {variations.map((vr) => (
                          <option value={String(vr.id)} key={vr.id}>{vr.label}</option>
                        ))}
                      </select>
                    </label>
                  ))}
                </div>
              )}

              {giftOn && giftLabel !== '' && (
                <div className="nv-pw-qb__gift">
                  <span className="nv-pw-qb__gift-label">{giftLabel}</span>
                  {giftValue !== '' && <span className="nv-pw-qb__gift-value">{giftValue}</span>}
                </div>
              )}
            </div>
          );
        })}
      </div>

      <button type="button" className="nv-pw-qb__cta" data-nv-qb-add="" style={{ background: buttonBg, color: buttonColor }}>{buttonText}</button>
      <p className="nv-pw-qb__msg" data-nv-qb-msg="" role="status"></p>
      {guaranteeText !== '' && <p className="nv-pw-qb__guarantee">{guaranteeText}</p>}
    </div>
  );
}
