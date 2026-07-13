import type { ReactNode } from 'react';
import { headlineMod, type HeadlineStyle } from '../types';

const Check = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
);

/** One benefit row: a marker (emoji/text, image, or icon-library node) plus a
 * bold title and a supporting line. */
export interface BenefitItem {
  /** Which marker to show. */
  markerType?: 'emoji' | 'image' | 'icon';
  /** Emoji / text marker (used when `markerType` is `emoji`). */
  icon?: string;
  /** Image marker URL (used when `markerType` is `image`). */
  image?: string;
  /** Alt text for the image marker. */
  imageAlt?: string;
  /** Icon-library marker node (used when `markerType` is `icon`; defaults to a checkmark). */
  libIcon?: ReactNode;
  /** Benefit title. */
  title?: string;
  /** Benefit description. */
  text?: string;
}

export interface BenefitsListProps {
  /** Section heading. */
  heading?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** The benefit rows. */
  items?: BenefitItem[];
}

/**
 * NV: Benefits List — a vertical checklist of selling points, each with a
 * marker (emoji, image or icon), a bold title and a supporting line. Mirrors the
 * `nv-benefits-list` Elementor widget.
 */
export function BenefitsList({
  heading = 'Därför väljer kunder oss',
  headlineStyle = 'default',
  items = [
    { icon: '✓', title: 'Snabb leverans', text: 'Skickas inom 24 timmar från vårt lager.' },
    { icon: '✓', title: 'Trygg betalning', text: 'Betala säkert med Klarna, kort eller Swish.' },
    { icon: '✓', title: 'Nöjd-kund-garanti', text: '30 dagars öppet köp på alla beställningar.' },
  ],
}: BenefitsListProps) {
  return (
    <div className="nv-pw-benefits">
      {heading !== '' && (
        <h3 className={`nv-pw-benefits__heading${headlineMod(headlineStyle)}`}>{heading}</h3>
      )}
      <ul className="nv-pw-benefits__list">
        {items.map((item, i) => {
          const mt = item.markerType ?? 'emoji';
          const image = item.image ?? '';
          const libIcon = item.libIcon;
          const title = (item.title ?? '').trim();
          const text = (item.text ?? '').trim();
          const iconText = (item.icon ?? '✓').trim();

          const isImage = mt === 'image' && image !== '';
          const isLib = mt === 'icon' && libIcon != null;
          const markerClass = isImage
            ? ' nv-pw-benefits__icon--image'
            : isLib
              ? ' nv-pw-benefits__icon--lib'
              : '';

          return (
            <li className="nv-pw-benefits__item" key={i}>
              <span className={`nv-pw-benefits__icon${markerClass}`} aria-hidden="true">
                {isImage ? (
                  <img src={image} alt={item.imageAlt ?? ''} loading="lazy" />
                ) : isLib ? (
                  (libIcon ?? <Check />)
                ) : (
                  iconText !== '' ? iconText : '✓'
                )}
              </span>
              <div className="nv-pw-benefits__content">
                {title !== '' && <strong className="nv-pw-benefits__title">{title}</strong>}
                {text !== '' && <p className="nv-pw-benefits__text">{text}</p>}
              </div>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
