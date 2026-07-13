import type { ReactNode } from 'react';
import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';

/** One feature row of the comparison matrix. Each cell accepts `yes`/`ja` (→ a
 * check), `no`/`nej`/`-` (→ a cross), or any other text (rendered as a value). */
export interface ComparisonRow {
  /** Feature name shown in the left label column. */
  label: string;
  /** Your column cell value. */
  us?: string;
  /** Competitor 1 cell value. */
  c1?: string;
  /** Competitor 2 cell value. */
  c2?: string;
  /** Competitor 3 cell value. */
  c3?: string;
}

export interface ComparisonGridProps {
  /** Uppercase overline above the headline. */
  eyebrow?: string;
  /** Headline. */
  headline?: string;
  /** Intro paragraph. */
  intro?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Put the heading, checklist and button in a column beside the matrix. */
  splitLayout?: boolean;
  /** Benefit checklist (shown only in split layout). */
  bullets?: string[];
  /** Your column header. */
  usHeader?: string;
  /** Your column badge (optional). */
  usBadge?: string;
  /** Your column image URL (optional). */
  usImage?: string;
  /** Competitor 1 header (column shown only when set). */
  c1Header?: string;
  /** Competitor 2 header (column shown only when set). */
  c2Header?: string;
  /** Competitor 3 header (column shown only when set). */
  c3Header?: string;
  /** Feature rows. */
  rows?: ComparisonRow[];
  /** Accent color (your column, eyebrow, badge, CTA). */
  accent?: string;
  /** Check (✓) color. */
  checkColor?: string;
  /** Cross (✕) color. */
  crossColor?: string;
  /** Column header text color. */
  headerColor?: string;
  /** Card background. */
  cardBg?: string;
  /** Highlight the feature (label) column. */
  highlightLabels?: boolean;
  /** Feature column background (when highlighted). */
  labelColBg?: string;
  /** Feature column text color (when highlighted). */
  labelColColor?: string;
  /** Card corner radius in px. */
  radius?: number;
  /** Relative width (in fr) of the feature column vs the brand columns. */
  labelColWidth?: number;
  /** Button label (optional). */
  ctaText?: string;
  /** Button href. */
  ctaUrl?: string;
}

const YES = ['yes', 'ja', 'y', 'true', '✓', 'check', 'v'];
const NO = ['no', 'nej', 'n', 'false', '✗', 'x', '-', '–', '—', ''];

/** Render a single matrix cell — check, cross, or raw value. Mirrors the PHP
 * NV_PW_Comparison_Grid::cell(). */
function cell(value: string, isUs: boolean): ReactNode {
  const v = value.trim();
  const lc = v.toLowerCase();
  if (YES.includes(lc)) {
    return (
      <span className={`nv-pw-cg__mark nv-pw-cg__mark--yes${isUs ? ' is-us' : ''}`} aria-label="Ja">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
      </span>
    );
  }
  if (NO.includes(lc)) {
    return (
      <span className="nv-pw-cg__mark nv-pw-cg__mark--no" aria-label="Nej">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
      </span>
    );
  }
  return <span className="nv-pw-cg__val">{v}</span>;
}

/**
 * NV: Comparison Grid — a "us vs. the others" feature matrix with a highlighted
 * brand column, check/cross marks and an optional marketing column beside the
 * table. Mirrors the `nv-comparison-grid` Elementor widget.
 */
export function ComparisonGrid({
  eyebrow = 'JÄMFÖRELSE',
  headline = 'Varför vi vinner',
  intro = 'Se hur vi står oss mot andra märken.',
  headlineStyle = 'default',
  splitLayout = false,
  bullets = ['Fall asleep quicker', 'Relax & restorative sleep', 'Wake up refreshed', 'Plant based'],
  usHeader = 'Vi',
  usBadge = 'BÄST',
  usImage,
  c1Header = 'Andra märken',
  c2Header = '',
  c3Header = '',
  rows = [
    { label: 'Snabb effekt', us: 'yes', c1: 'no', c2: 'no', c3: 'no' },
    { label: 'Kliniskt testad', us: 'yes', c1: 'no', c2: 'yes', c3: 'no' },
    { label: 'Fri frakt', us: 'yes', c1: 'yes', c2: 'no', c3: 'no' },
    { label: 'Nöjd-kund-garanti', us: 'yes', c1: 'no', c2: 'no', c3: 'no' },
  ],
  accent = '#3B37C4',
  checkColor = '#1D9E75',
  crossColor = '#D14B41',
  headerColor = '#14161D',
  cardBg = '#FFFFFF',
  highlightLabels = true,
  labelColBg = '#3B37C4',
  labelColColor = '#FFFFFF',
  radius = 18,
  labelColWidth = 1.5,
  ctaText = '',
  ctaUrl = '#',
}: ComparisonGridProps) {
  const validRows = rows.filter((r) => (r.label ?? '').trim() !== '');
  if (validRows.length === 0) return null;

  type CompKey = 'c1' | 'c2' | 'c3';
  const compSource: Array<{ key: CompKey; header: string }> = [
    { key: 'c1', header: c1Header },
    { key: 'c2', header: c2Header },
    { key: 'c3', header: c3Header },
  ];
  const comp = compSource.filter((c) => (c.header ?? '').trim() !== '');

  const usHeaderText = (usHeader || '').trim() || 'Vi';
  const totalCols = 1 + comp.length;
  const validBullets = bullets.filter((b) => b.trim() !== '');

  const style: CSSVars = {
    '--nv-cg-cols': totalCols,
    '--nv-cg-accent': accent,
    '--nv-cg-check': checkColor,
    '--nv-cg-cross': crossColor,
    '--nv-cg-header': headerColor,
    '--nv-cg-card-bg': cardBg,
    '--nv-cg-label-bg': labelColBg,
    '--nv-cg-label-color': labelColColor,
    '--nv-cg-label-w': `${labelColWidth}fr`,
  };

  const hasHead =
    eyebrow !== '' || headline !== '' || intro !== '' || (splitLayout && (validBullets.length > 0 || ctaText !== ''));

  const head = hasHead ? (
    <div className="nv-pw-cg__head">
      {eyebrow !== '' && <span className="nv-pw-cg__eyebrow">{eyebrow}</span>}
      {headline !== '' && <h3 className={`nv-pw-cg__headline${headlineMod(headlineStyle)}`}>{renderHeadline(headline)}</h3>}
      {intro !== '' && <p className="nv-pw-cg__intro">{intro}</p>}
      {splitLayout && validBullets.length > 0 && (
        <ul className="nv-pw-cg__bullets">
          {validBullets.map((bt, i) => (
            <li key={i}>
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
              {bt}
            </li>
          ))}
        </ul>
      )}
      {splitLayout && ctaText !== '' && (
        <a className="nv-pw-cg__cta" href={ctaUrl !== '' ? ctaUrl : '#'}>{ctaText}</a>
      )}
    </div>
  ) : null;

  const table = (
    <div className="nv-pw-cg__table" role="table" style={{ borderRadius: radius }}>
      <div className="nv-pw-cg__row nv-pw-cg__row--head" role="row">
        <span className="nv-pw-cg__cell nv-pw-cg__cell--label" role="columnheader"></span>
        <span className="nv-pw-cg__cell nv-pw-cg__cell--us" role="columnheader">
          {usBadge !== '' && <span className="nv-pw-cg__badge">{usBadge}</span>}
          {usImage && <img className="nv-pw-cg__colimg" src={usImage} alt={usHeaderText} loading="lazy" />}
          <span className="nv-pw-cg__colname">{usHeaderText}</span>
        </span>
        {comp.map((c) => (
          <span className="nv-pw-cg__cell" role="columnheader" key={c.key}>
            <span className="nv-pw-cg__colname">{c.header}</span>
          </span>
        ))}
      </div>
      {validRows.map((row, i) => (
        <div className="nv-pw-cg__row" role="row" key={i}>
          <span className="nv-pw-cg__cell nv-pw-cg__cell--label" role="cell">{row.label ?? ''}</span>
          <span className="nv-pw-cg__cell nv-pw-cg__cell--us" role="cell">{cell(row.us ?? '', true)}</span>
          {comp.map((c) => (
            <span className="nv-pw-cg__cell" role="cell" key={c.key}>{cell(row[c.key] ?? '', false)}</span>
          ))}
        </div>
      ))}
    </div>
  );

  const rootClass = `nv-pw-cg${highlightLabels ? ' nv-pw-cg--hl' : ''}${splitLayout ? ' nv-pw-cg--split' : ''}`;

  return (
    <div className={rootClass} style={style}>
      {!splitLayout && head}
      {splitLayout ? (
        <div className="nv-pw-cg__split">
          {head}
          <div className="nv-pw-cg__tablewrap">{table}</div>
        </div>
      ) : (
        table
      )}
      {!splitLayout && ctaText !== '' && (
        <div className="nv-pw-cg__foot">
          <a className="nv-pw-cg__cta" href={ctaUrl !== '' ? ctaUrl : '#'}>{ctaText}</a>
        </div>
      )}
    </div>
  );
}
