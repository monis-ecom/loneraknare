import type { ReactNode } from 'react';
import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';

/** A single rich review card: avatar (or initial), author name + role, a star
 * rating, an optional bold card headline and the review text (with an optional
 * highlighted phrase). */
export interface Review {
  /** Optional author image URL — an initial is shown when absent. */
  avatar?: string;
  /** Author name. */
  name: string;
  /** Role / location line. */
  role: string;
  /** Star rating 1–5. */
  stars: number;
  /** Optional bold headline shown above the review text. */
  title?: string;
  /** The review text. */
  text: string;
  /** If this exact phrase appears in `text`, it is wrapped in a highlight mark. */
  highlight?: string;
}

export interface ReviewWallProps {
  /** Eyebrow badge above the headline. */
  eyebrow?: string;
  /** Headline text before the accent word. */
  headline1?: string;
  /** Accent (highlighted) word in the headline. */
  headlineAccent?: string;
  /** Headline text after the accent word. */
  headline2?: string;
  /** Subheading below the headline. */
  subheading?: string;
  /** Optional CTA button label (hidden when empty). */
  ctaText?: string;
  /** CTA button href. */
  ctaUrl?: string;
  /** Open the CTA in a new tab. */
  ctaExternal?: boolean;
  /** Show the aggregate rating bar. */
  showRating?: boolean;
  /** Aggregate rating value 0–5. */
  ratingValue?: number;
  /** Aggregate rating label. */
  ratingText?: string;
  /** Where the rating bar sits relative to the headline. */
  ratingPosition?: 'top' | 'bottom';
  /** Header text alignment. */
  align?: 'left' | 'center';
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** The review cards. */
  items?: Review[];
  /** Continuous auto-scroll (marquee) instead of a static grid. */
  autoScroll?: boolean;
  /** Marquee scroll direction. */
  direction?: 'left' | 'right';
  /** Marquee scroll duration in seconds. */
  speed?: number;
  /** Grid column count (used when auto-scroll is off). */
  columns?: '2' | '3' | '4';
  /** Card width in px. */
  cardWidth?: number;
  /** Visual theme. */
  theme?: 'light' | 'dark' | 'soft';
  /** Accent color (badge / accent word / button). */
  accent?: string;
  /** Star color. */
  starColor?: string;
  /** Highlight background color. */
  highlightColor?: string;
  /** Section background color. */
  bg?: string;
  /** Card background color. */
  cardBg?: string;
  /** Review text color. */
  textColor?: string;
  /** Author name color. */
  nameColor?: string;
  /** Headline color. */
  headingColor?: string;
}

function Stars({ count }: { count: number }) {
  const n = Math.max(0, Math.min(5, count));
  return (
    <span className="nv-pw-rw__stars" aria-label={`${n}/5`}>
      {[1, 2, 3, 4, 5].map((i) => (
        <svg key={i} className={`nv-pw-rw__star${i <= n ? ' is-on' : ''}`} viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>
      ))}
    </span>
  );
}

/** Mirror of the PHP `text_html()` — case-insensitively wrap the highlight phrase. */
function highlightText(text: string, highlight?: string): ReactNode {
  const h = (highlight ?? '').trim();
  if (h === '') return text;
  const parts: ReactNode[] = [];
  const lower = text.toLowerCase();
  const lh = h.toLowerCase();
  let from = 0;
  let idx = lower.indexOf(lh, from);
  let key = 0;
  while (idx !== -1) {
    if (idx > from) parts.push(text.slice(from, idx));
    parts.push(<mark className="nv-pw-rw__hl" key={key++}>{h}</mark>);
    from = idx + lh.length;
    idx = lower.indexOf(lh, from);
  }
  if (from < text.length) parts.push(text.slice(from));
  return parts;
}

function RatingBar({ rating, text }: { rating: number; text: string }) {
  const r = Math.max(0, Math.min(5, rating));
  const pct = (r / 5) * 100;
  const style: CSSVars = { '--nv-rw-fill': `${pct}%` };
  return (
    <div className="nv-pw-rw__rating">
      <span className="nv-pw-rw__rating-stars" role="img" aria-label={text !== '' ? text : `${r} of 5`} style={style}>
        <span className="nv-pw-rw__rating-base">★★★★★</span><span className="nv-pw-rw__rating-fill" aria-hidden="true">★★★★★</span>
      </span>
      {text !== '' && <span className="nv-pw-rw__rating-text">{text}</span>}
    </div>
  );
}

function Card({ it }: { it: Review }) {
  const name = it.name.trim();
  const role = it.role.trim();
  const avatar = it.avatar ?? '';
  const title = (it.title ?? '').trim();
  const initial = name !== '' ? name.charAt(0).toUpperCase() : '★';
  return (
    <figure className="nv-pw-rw__card">
      <div className="nv-pw-rw__author">
        {avatar !== '' ? (
          <img className="nv-pw-rw__avatar" src={avatar} alt={name} loading="lazy" />
        ) : (
          <span className="nv-pw-rw__avatar nv-pw-rw__avatar--initial" aria-hidden="true">{initial}</span>
        )}
        <span className="nv-pw-rw__who">
          {name !== '' && <strong className="nv-pw-rw__name">{name}</strong>}
          {role !== '' && <span className="nv-pw-rw__role">{role}</span>}
        </span>
      </div>
      <Stars count={it.stars} />
      {title !== '' && <strong className="nv-pw-rw__title">{title}</strong>}
      <blockquote className="nv-pw-rw__text">{highlightText(it.text, it.highlight)}</blockquote>
    </figure>
  );
}

/**
 * NV: Review Wall — a full testimonials section with an eyebrow badge, an
 * accent-word headline, subheading, aggregate rating bar, CTA and a
 * continuously auto-scrolling wall of rich review cards. Mirrors the
 * `nv-review-wall` Elementor widget.
 */
export function ReviewWall({
  eyebrow = 'KUNDOMDÖMEN',
  headline1 = 'Därför älskar spelare',
  headlineAccent = 'PadelFlex™',
  headline2 = '',
  subheading = 'Här är vad de säger.',
  ctaText = '',
  ctaUrl = '',
  ctaExternal = false,
  showRating = true,
  ratingValue = 4.9,
  ratingText = '4,9/5 · baserat på 9 000+ recensioner',
  ratingPosition = 'top',
  align = 'center',
  headlineStyle = 'default',
  items = [
    { name: 'Sarah Jenkins', role: 'Verifierad köpare', stars: 5, text: 'Jag var skeptisk först, men efter 14 dagar kan jag inte tänka mig att spela utan dem.', highlight: 'kan jag inte tänka mig' },
    { name: 'Marcus Thorne', role: 'Padelspelare, Stockholm', stars: 5, text: 'Perfekt balans mellan stöd och komfort. Rekommenderas verkligen.', highlight: 'Perfekt balans' },
    { name: 'David Chen', role: 'Verifierad köpare', stars: 5, text: 'Sällan lämnar jag recensioner, men de här förtjänar det. Fantastisk produkt.', highlight: 'förtjänar det' },
    { name: 'Elena Rodriguez', role: 'Klubbspelare', stars: 5, text: 'Skillnaden märktes redan första matchen. Total game changer.', highlight: 'första matchen' },
  ],
  autoScroll = true,
  direction = 'left',
  speed = 60,
  columns = '4',
  cardWidth = 320,
  theme = 'light',
  accent,
  starColor,
  highlightColor,
  bg,
  cardBg,
  textColor,
  nameColor,
  headingColor,
}: ReviewWallProps) {
  const eb = eyebrow.trim();
  const h1 = headline1.trim();
  const accentWord = headlineAccent.trim();
  const h2 = headline2.trim();
  const sub = subheading.trim();
  const cta = ctaText.trim();

  const renderItems = autoScroll ? [...items, ...items] : items;
  const hasRating = showRating;
  const hasHead = eb !== '' || h1 !== '' || accentWord !== '' || sub !== '' || cta !== '' || hasRating;

  const style: CSSVars = {
    '--nv-rw-cols': columns,
    '--nv-rw-card-w': `${cardWidth}px`,
  };
  if (accent !== undefined) style['--nv-rw-accent'] = accent;
  if (starColor !== undefined) style['--nv-rw-star'] = starColor;
  if (highlightColor !== undefined) style['--nv-rw-hl'] = highlightColor;
  if (bg !== undefined) style['--nv-rw-bg'] = bg;
  if (cardBg !== undefined) style['--nv-rw-card'] = cardBg;
  if (textColor !== undefined) style['--nv-rw-text'] = textColor;
  if (nameColor !== undefined) style['--nv-rw-name'] = nameColor;
  if (headingColor !== undefined) style['--nv-rw-heading'] = headingColor;

  const trackStyle: CSSVars = { '--nv-rw-dur': `${speed}s` };
  const ratingBar = hasRating ? <RatingBar rating={ratingValue} text={ratingText.trim()} /> : null;

  return (
    <section className={`nv-pw-rw nv-pw-rw--${theme}`} style={style}>
      {hasHead && (
        <div className="nv-pw-rw__head" style={{ textAlign: align }}>
          {eb !== '' && <span className="nv-pw-rw__eyebrow">{eb}</span>}
          {ratingBar && ratingPosition === 'top' && ratingBar}
          {(h1 !== '' || accentWord !== '') && (
            <h2 className={`nv-pw-rw__headline${headlineMod(headlineStyle)}`}>
              {renderHeadline(h1)}
              {accentWord !== '' && <>{h1 !== '' ? ' ' : ''}<span className="nv-pw-rw__accent">{accentWord}</span></>}
              {h2 !== '' && <>{' '}{renderHeadline(h2)}</>}
            </h2>
          )}
          {sub !== '' && <p className="nv-pw-rw__sub">{sub}</p>}
          {ratingBar && ratingPosition === 'bottom' && ratingBar}
          {cta !== '' && <a className="nv-pw-rw__cta" href={ctaUrl !== '' ? ctaUrl : '#'} {...(ctaExternal ? { target: '_blank', rel: 'noopener' } : {})}>{cta}</a>}
        </div>
      )}

      <div className={`nv-pw-rw__wall${autoScroll ? ` nv-pw-rw__wall--marquee nv-pw-rw__wall--${direction}` : ' nv-pw-rw__wall--grid'}`}>
        <div className="nv-pw-rw__track" style={trackStyle}>
          {renderItems.map((it, i) => <Card it={it} key={i} />)}
        </div>
      </div>
    </section>
  );
}
