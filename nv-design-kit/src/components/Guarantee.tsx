import type { ReactNode } from 'react';
import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';

const Shield = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
);
const Check = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
);
const Truck = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M1 3h15v13H1z" /><path d="M16 8h4l3 3v5h-7z" /><circle cx="5.5" cy="18.5" r="2.5" /><circle cx="18.5" cy="18.5" r="2.5" /></svg>
);
const Lock = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
);
const Undo = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M3 7v6h6" /><path d="M3 13a9 9 0 1 0 3-7.7L3 8" /></svg>
);

/** One trust badge: a small icon (or image) marker plus a short label. */
export interface GuaranteeBadge {
  /** Which marker to show. */
  badgeMarkerType?: 'icon' | 'image';
  /** Icon marker node (used when `badgeMarkerType` is `icon`; defaults to a checkmark). */
  badgeIcon?: ReactNode;
  /** Image marker URL (used when `badgeMarkerType` is `image`). */
  badgeImage?: string;
  /** Alt text for the image marker. */
  badgeImageAlt?: string;
  /** Badge label. */
  label?: string;
}

export interface GuaranteeProps {
  /** Which main marker to show. */
  iconType?: 'icon' | 'image';
  /** Main icon node (used when `iconType` is `icon`; defaults to a shield). */
  icon?: ReactNode;
  /** Main image URL (used when `iconType` is `image`). */
  mainImage?: string;
  /** Alt text for the main image. */
  mainImageAlt?: string;
  /** Heading. */
  heading?: string;
  /** Supporting text. */
  text?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** `boxed` (icon left) or `center`. */
  layout?: 'boxed' | 'center';
  /** Trust badges. */
  badges?: GuaranteeBadge[];
  /** Background color. */
  bg?: string;
  /** Accent color (icon, badges). */
  accent?: string;
  /** Heading color. */
  headingColor?: string;
  /** Text color. */
  textColor?: string;
}

/**
 * NV: Guarantee Block — a risk-reversal block with a main icon, heading and
 * reassurance text plus a row of small trust badges. Mirrors the `nv-guarantee`
 * Elementor widget.
 */
export function Guarantee({
  iconType = 'icon',
  icon = <Shield />,
  mainImage,
  mainImageAlt = '',
  heading = '30 dagars nöjd-kund-garanti',
  text = 'Inte helt nöjd? Skicka tillbaka produkten inom 30 dagar så får du pengarna tillbaka – inga frågor.',
  headlineStyle = 'default',
  layout = 'boxed',
  badges = [
    { badgeIcon: <Truck />, label: 'Fri frakt' },
    { badgeIcon: <Lock />, label: 'Säker betalning' },
    { badgeIcon: <Undo />, label: 'Enkel retur' },
  ],
  bg = '#F6F7FB',
  accent = '#312E81',
  headingColor = '#14161D',
  textColor = '#4B5563',
}: GuaranteeProps) {
  const style: CSSVars = {
    '--nv-gr-bg': bg,
    '--nv-gr-accent': accent,
    '--nv-gr-heading': headingColor,
    '--nv-gr-text': textColor,
  };
  const mainImg = iconType === 'image' && mainImage ? mainImage : '';

  return (
    <div className={`nv-pw-guarantee nv-pw-guarantee--${layout}`} style={style}>
      <div className="nv-pw-guarantee__main">
        <span className={`nv-pw-guarantee__icon${mainImg !== '' ? ' nv-pw-guarantee__icon--image' : ''}`} aria-hidden="true">
          {mainImg !== '' ? (
            <img src={mainImg} alt={mainImageAlt} loading="lazy" />
          ) : iconType !== 'image' ? (
            icon
          ) : null}
        </span>
        <div className="nv-pw-guarantee__body">
          {heading !== '' && <h3 className={`nv-pw-guarantee__heading${headlineMod(headlineStyle)}`}>{renderHeadline(heading)}</h3>}
          {text !== '' && <p className="nv-pw-guarantee__text">{text}</p>}
        </div>
      </div>
      {badges.length > 0 && (
        <ul className="nv-pw-guarantee__badges">
          {badges.map((b, i) => {
            const label = (b.label ?? '').trim();
            if (label === '') return null;
            const badgeImg = b.badgeMarkerType === 'image' && b.badgeImage ? b.badgeImage : '';
            return (
              <li className="nv-pw-guarantee__badge" key={i}>
                <span className={`nv-pw-guarantee__badge-icon${badgeImg !== '' ? ' nv-pw-guarantee__badge-icon--image' : ''}`} aria-hidden="true">
                  {badgeImg !== '' ? (
                    <img src={badgeImg} alt={b.badgeImageAlt ?? ''} loading="lazy" />
                  ) : (b.badgeMarkerType ?? 'icon') !== 'image' ? (
                    (b.badgeIcon ?? <Check />)
                  ) : null}
                </span>
                <span className="nv-pw-guarantee__badge-label">{label}</span>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
