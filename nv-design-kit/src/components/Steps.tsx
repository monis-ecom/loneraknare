import type { ReactNode } from 'react';
import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';

/** One step: an optional marker (icon or image), a title and a description. When
 * no marker is supplied the step shows its number (or a dot). */
export interface Step {
  /** Which marker to show. */
  markerType?: 'icon' | 'image';
  /** Icon marker node (used when `markerType` is `icon`). */
  icon?: ReactNode;
  /** Image marker URL (used when `markerType` is `image`). */
  image?: string;
  /** Alt text for the image marker. */
  imageAlt?: string;
  /** Step title. */
  title?: string;
  /** Step description. */
  text?: string;
}

export interface StepsProps {
  /** Section heading. */
  heading?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Number of grid columns. */
  columns?: '2' | '3' | '4';
  /** `connected` draws a horizontal line between markers. */
  layout?: 'default' | 'connected';
  /** Show the auto-incrementing step number when a step has no icon/image. */
  showNumbers?: boolean;
  /** The steps. */
  items?: Step[];
  /** Accent color (markers, numbers, connector line). */
  accent?: string;
  /** Heading color. */
  headingColor?: string;
  /** Text color. */
  textColor?: string;
}

/**
 * NV: Steps / How it works — a numbered 2–4 column process grid with optional
 * icon/image markers and a connected-line layout. Mirrors the `nv-steps`
 * Elementor widget.
 */
export function Steps({
  heading = 'Så här fungerar det',
  headlineStyle = 'default',
  columns = '3',
  layout = 'default',
  showNumbers = true,
  items = [
    { title: 'Beställ enkelt', text: 'Lägg din order på under en minut – tryggt och säkert.' },
    { title: 'Snabb leverans', text: 'Vi skickar inom 24 timmar direkt hem till dörren.' },
    { title: 'Börja njut', text: 'Packa upp och upplev skillnaden från dag ett.' },
  ],
  accent = '#312E81',
  headingColor = '#14161D',
  textColor = '#4B5563',
}: StepsProps) {
  const style: CSSVars = {
    '--nv-steps-accent': accent,
    '--nv-steps-heading': headingColor,
    '--nv-steps-text': textColor,
    '--nv-steps-cols': columns,
  };
  const rootClass = `nv-pw-steps${layout === 'connected' ? ' nv-pw-steps--connected' : ''}`;

  return (
    <div className={rootClass} style={style}>
      {heading !== '' && (
        <h3 className={`nv-pw-steps__heading${headlineMod(headlineStyle)}`}>{renderHeadline(heading)}</h3>
      )}
      <ol className="nv-pw-steps__grid">
        {items.map((it, i) => {
          const title = (it.title ?? '').trim();
          const text = (it.text ?? '').trim();
          const markerType = it.markerType ?? 'icon';
          const markerImg = markerType === 'image' && it.image ? it.image : '';
          const hasIcon = markerType === 'icon' && it.icon != null;

          return (
            <li className="nv-pw-steps__item" key={i}>
              <span className={`nv-pw-steps__marker${markerImg !== '' ? ' nv-pw-steps__marker--image' : ''}`}>
                {markerImg !== '' ? (
                  <img src={markerImg} alt={it.imageAlt ?? ''} loading="lazy" />
                ) : hasIcon ? (
                  it.icon
                ) : showNumbers ? (
                  <span className="nv-pw-steps__num">{i + 1}</span>
                ) : (
                  <span className="nv-pw-steps__dot"></span>
                )}
              </span>
              {title !== '' && <strong className="nv-pw-steps__title">{title}</strong>}
              {text !== '' && <p className="nv-pw-steps__text">{text}</p>}
            </li>
          );
        })}
      </ol>
    </div>
  );
}
