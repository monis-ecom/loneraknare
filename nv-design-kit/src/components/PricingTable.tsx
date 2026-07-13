import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';

/** One pricing column: name, price, features and a CTA. */
export interface PricingPlan {
  /** Plan name (e.g. "Populär"). */
  name?: string;
  /** Price (e.g. "499 kr"). */
  price?: string;
  /** Optional period suffix (e.g. "/ mån"). */
  period?: string;
  /** Optional short description under the price. */
  description?: string;
  /** Features, one per line. Prefix a line with "-" to show it as not included. */
  features?: string;
  /** Optional badge (e.g. "Populärast"). */
  badge?: string;
  /** Highlights this plan as the best value. */
  highlighted?: boolean;
  /** CTA button label. */
  buttonText?: string;
  /** CTA button href. */
  buttonUrl?: string;
}

export interface PricingTableProps {
  /** Optional centered heading above the grid. */
  heading?: string;
  /** Optional subheading under the heading. */
  subheading?: string;
  /** The plan columns. */
  plans?: PricingPlan[];
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Accent color (highlight / button). Blank uses the global brand accent. */
  accent?: string;
  /** Card background. */
  cardBg?: string;
  /** Check-mark color. */
  checkColor?: string;
  /** Card corner radius in px. */
  radius?: number;
}

const DEFAULT_PLANS: PricingPlan[] = [
  { name: 'Bas', price: '299 kr', period: '/ mån', features: '1 par\nFri frakt\n- Prioriterad support', buttonText: 'Välj Bas' },
  { name: 'Populär', price: '499 kr', period: '/ mån', features: '2 par\nFri frakt\nPrioriterad support', badge: 'Populärast', highlighted: true, buttonText: 'Välj Populär' },
  { name: 'Pro', price: '599 kr', period: '/ mån', features: '3 par\nFri frakt\nPrioriterad support', buttonText: 'Välj Pro' },
];

const CheckIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
);
const CrossIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth={2.5} strokeLinecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
);

/**
 * NV: Pricing Table — side-by-side plan columns with feature ticks, a highlighted
 * "best value" column and a per-column CTA. For subscriptions, service tiers or
 * good/better/best offers. Mirrors the `nv-pricing-table` Elementor widget.
 */
export function PricingTable({
  heading = '',
  subheading = '',
  plans = DEFAULT_PLANS,
  headlineStyle = 'default',
  accent = '',
  cardBg = '#FFFFFF',
  checkColor = '#1D9E75',
  radius = 16,
}: PricingTableProps) {
  const validPlans = plans.filter((p) => (p.name ?? '').trim() !== '');

  const style: CSSVars = {
    '--nv-pt-count': validPlans.length,
    '--nv-pt-card': cardBg,
    '--nv-pt-check': checkColor,
    '--nv-pt-radius': `${radius}px`,
  };
  if (accent !== '') style['--nv-pt-accent'] = accent;

  const hasHead = heading !== '' || subheading !== '';

  return (
    <div className="nv-pw-pt" style={style}>
      {hasHead && (
        <div className="nv-pw-pt__head">
          {heading !== '' && <h2 className={`nv-pw-pt__heading${headlineMod(headlineStyle)}`}>{renderHeadline(heading)}</h2>}
          {subheading !== '' && <p className="nv-pw-pt__sub">{subheading}</p>}
        </div>
      )}
      <div className="nv-pw-pt__grid">
        {validPlans.map((p, idx) => {
          const hl = !!p.highlighted;
          const badge = (p.badge ?? '').trim();
          const btn = (p.buttonText ?? '').trim();
          const url = (p.buttonUrl ?? '').trim();
          const price = (p.price ?? '').trim();
          const period = (p.period ?? '').trim();
          const desc = (p.description ?? '').trim();
          const features = (p.features ?? '')
            .split(/\r\n|\r|\n/)
            .map((l) => l.trim())
            .filter((l) => l !== '');

          return (
            <div className={`nv-pw-pt__card${hl ? ' is-highlighted' : ''}`} key={idx}>
              {badge !== '' && <span className="nv-pw-pt__badge">{badge}</span>}
              <div className="nv-pw-pt__name">{p.name}</div>
              <div className="nv-pw-pt__price">{price}{period !== '' && <span className="nv-pw-pt__period">{period}</span>}</div>
              {desc !== '' && <div className="nv-pw-pt__desc">{desc}</div>}
              {features.length > 0 && (
                <ul className="nv-pw-pt__features">
                  {features.map((f, fi) => {
                    const off = f.startsWith('-');
                    const label = off ? f.replace(/^[-\s]+/, '') : f;
                    return (
                      <li className={`nv-pw-pt__feature${off ? ' is-off' : ''}`} key={fi}>
                        {off ? <CrossIcon /> : <CheckIcon />}
                        <span>{label}</span>
                      </li>
                    );
                  })}
                </ul>
              )}
              {btn !== '' && <a className="nv-pw-pt__btn" href={url !== '' ? url : '#'}>{btn}</a>}
            </div>
          );
        })}
      </div>
    </div>
  );
}
