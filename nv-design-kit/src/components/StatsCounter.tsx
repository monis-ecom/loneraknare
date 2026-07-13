import type { CSSVars } from '../types';

/** One stat: a number (with optional decimals, prefix and suffix) plus a label. */
export interface Stat {
  /** The number to display. */
  value: number;
  /** Decimal places (0–3). */
  decimals?: number;
  /** Text before the number (e.g. "$"). */
  prefix?: string;
  /** Text after the number (e.g. "%"). */
  suffix?: string;
  /** Caption under the number. */
  label?: string;
}

export interface StatsCounterProps {
  /** Number of columns in the grid. */
  columns?: '2' | '3' | '4';
  /** Count-up duration in seconds (emitted as a data attribute). */
  duration?: number;
  /** Thousands separator. */
  thousandSep?: 'space' | 'comma' | 'dot' | 'none';
  /** Decimal separator. */
  decimalSep?: 'dot' | 'comma';
  /** The stats. */
  items?: Stat[];
  /** Optional subtext under the grid. */
  subtext?: string;
  /** Button label (button hidden unless both label and URL are set). */
  buttonText?: string;
  /** Button href (empty by default → button hidden). */
  buttonUrl?: string;
  /** Optional guarantee line. */
  guaranteeText?: string;
  /** Render as a boxed card. */
  boxed?: boolean;
  /** Card background (when boxed). */
  cardBg?: string;
}

function sepChar(key: string): string {
  switch (key) {
    case 'comma':
      return ',';
    case 'dot':
      return '.';
    case 'space':
      return ' ';
    default:
      return '';
  }
}

/** Mirror of PHP number_format($value, $decimals, $dsep, $tsep). */
function formatValue(value: number, decimals: number, tsep: string, dsep: string): string {
  const fixed = Math.abs(value).toFixed(decimals);
  const [intPart, decPart] = fixed.split('.');
  const grouped = tsep === '' ? intPart : intPart.replace(/\B(?=(\d{3})+(?!\d))/g, tsep);
  const sign = value < 0 ? '-' : '';
  return decPart !== undefined ? `${sign}${grouped}${dsep}${decPart}` : `${sign}${grouped}`;
}

/**
 * NV: Stats Counter — a grid of headline numbers (rendered at their final values)
 * with labels, an optional subtext, CTA and guarantee line, shown as a boxed card
 * by default. Mirrors the `nv-stats-counter` Elementor widget.
 */
export function StatsCounter({
  columns = '2',
  duration = 2,
  thousandSep = 'space',
  decimalSep = 'dot',
  items = [
    { value: 9000, decimals: 0, prefix: '', suffix: '', label: 'NÖJDA SPELARE' },
    { value: 98.3, decimals: 1, prefix: '', suffix: '%', label: 'NÖJDHET' },
  ],
  subtext = '+9 000 spelare. Nästan inga ångrar sig.',
  buttonText = 'Beställ nu',
  buttonUrl = '',
  guaranteeText = '✓ Nöjd eller pengarna tillbaka i 60 dagar',
  boxed = true,
  cardBg = '#F1F2F6',
}: StatsCounterProps) {
  const stats = items.filter((r) => r.value !== undefined || (r.label ?? '').trim() !== '');
  if (stats.length === 0) return null;

  const cols = (['2', '3', '4'] as const).includes(columns) ? columns : '2';
  const durationMs = Math.round(Math.max(0.5, duration) * 1000);
  const tsep = sepChar(thousandSep);
  let dsep = sepChar(decimalSep);
  if (dsep === '') dsep = '.';
  const hasButton = buttonText !== '' && buttonUrl !== '';

  const style: CSSVars = {
    '--nv-stc-cols': cols,
    '--nv-stc-card-bg': cardBg,
  };

  return (
    <div className={`nv-pw-stc${boxed ? ' nv-pw-stc--boxed' : ''}`} style={style} data-nv-stats>
      <div className="nv-pw-stc__grid">
        {stats.map((st, i) => {
          const value = st.value ?? 0;
          const decimals = Math.max(0, Math.min(3, st.decimals ?? 0));
          const prefix = st.prefix ?? '';
          const suffix = st.suffix ?? '';
          const label = (st.label ?? '').trim();
          const formatted = formatValue(value, decimals, tsep, dsep);
          return (
            <div className="nv-pw-stc__item" key={i}>
              <span
                className="nv-pw-stc__num"
                data-nv-count
                data-to={String(value)}
                data-decimals={String(decimals)}
                data-tsep={tsep}
                data-dsep={dsep}
                data-prefix={prefix}
                data-suffix={suffix}
                data-duration={String(durationMs)}
              >
                {prefix + formatted + suffix}
              </span>
              {label !== '' && <span className="nv-pw-stc__label">{label}</span>}
            </div>
          );
        })}
      </div>
      {subtext !== '' && <p className="nv-pw-stc__subtext">{subtext}</p>}
      {hasButton && (
        <a className="nv-pw-stc__btn" href={buttonUrl}>{buttonText}</a>
      )}
      {guaranteeText !== '' && <p className="nv-pw-stc__guarantee">{guaranteeText}</p>}
    </div>
  );
}
