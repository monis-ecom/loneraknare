import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';

/** A single Trustpilot-style review card: green star blocks, a bold headline,
 * review text and an author + date line (with an optional avatar). */
export interface TrustpilotReview {
  /** Star rating 1–5. */
  stars: number;
  /** Bold card headline. */
  title: string;
  /** Review text. */
  text: string;
  /** Author name. */
  name: string;
  /** Date / location line. */
  date: string;
  /** Optional author image URL. */
  avatar?: string;
}

export interface TrustpilotWallProps {
  /** Section headline. */
  headline?: string;
  /** Show the aggregate rating header. */
  showRating?: boolean;
  /** Aggregate rating label (e.g. "Excellent"). */
  ratingLabel?: string;
  /** Aggregate rating value 0–5. */
  ratingValue?: number;
  /** Count prefix (e.g. "based on"). */
  reviewsPrefix?: string;
  /** Reviews count text. */
  reviewsCount?: string;
  /** Count suffix (e.g. "reviews"). */
  reviewsSuffix?: string;
  /** Optional CTA button label (hidden when empty). */
  ctaText?: string;
  /** CTA button href. */
  ctaUrl?: string;
  /** Open the CTA in a new tab. */
  ctaExternal?: boolean;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** The review cards. */
  items?: TrustpilotReview[];
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
  theme?: 'light' | 'dark' | 'cream' | 'blue' | 'yellow';
  /** Trustpilot star color. */
  green?: string;
  /** Section background color. */
  bg?: string;
  /** Card background color. */
  cardBg?: string;
  /** Headline color. */
  headingColor?: string;
  /** Card headline color. */
  titleColor?: string;
  /** Review text color. */
  textColor?: string;
  /** Button background color. */
  ctaBg?: string;
  /** Button text color. */
  ctaColor?: string;
}

/** Green Trustpilot-style star blocks with fractional fill — mirror of `blocks_html()`. */
function Blocks({ rating, size }: { rating: number; size: 'lg' | 'sm' }) {
  const r = Math.max(0, Math.min(5, rating));
  const pct = (r / 5) * 100;
  const style: CSSVars = { '--nv-tp-fill': `${pct}%` };
  const tiles = [0, 1, 2, 3, 4].map((i) => (
    <span className="nv-pw-tp__tile" key={i}><svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg></span>
  ));
  return (
    <span className={`nv-pw-tp__blocks nv-pw-tp__blocks--${size}`} style={style}>
      <span className="nv-pw-tp__blocks-base" aria-hidden="true">{tiles}</span>
      <span className="nv-pw-tp__blocks-fill" aria-hidden="true">{tiles}</span>
    </span>
  );
}

function Card({ it }: { it: TrustpilotReview }) {
  const title = it.title.trim();
  const text = it.text.trim();
  const name = it.name.trim();
  const date = it.date.trim();
  const avatar = it.avatar ?? '';
  return (
    <figure className="nv-pw-tp__card">
      <Blocks rating={it.stars} size="sm" />
      {title !== '' && <strong className="nv-pw-tp__title">{title}</strong>}
      {text !== '' && <blockquote className="nv-pw-tp__text">{text}</blockquote>}
      {(name !== '' || avatar !== '') && (
        <figcaption className="nv-pw-tp__author">
          {avatar !== '' && <img className="nv-pw-tp__avatar" src={avatar} alt={name} loading="lazy" />}
          <span className="nv-pw-tp__who">
            {name}{name !== '' && date !== '' ? ' – ' : ''}{date}
          </span>
        </figcaption>
      )}
    </figure>
  );
}

/** Trim a fractional score for display (4.7 not 4.7000, 5 not 5.0). */
function displayRating(value: number): string {
  return value.toFixed(1).replace(/0+$/, '').replace(/\.$/, '');
}

/**
 * NV: Trustpilot Wall — a Trustpilot-style social-proof section with an aggregate
 * rating header (label, score, green star blocks, review count) above a
 * continuously scrolling wall of review cards. Mirrors the `nv-trustpilot-wall`
 * Elementor widget.
 */
export function TrustpilotWall({
  headline = 'Our customers tell it better than we do!',
  showRating = true,
  ratingLabel = 'Excellent',
  ratingValue = 4.7,
  reviewsPrefix = 'based on',
  reviewsCount = '1 067',
  reviewsSuffix = 'reviews',
  ctaText = 'View All',
  ctaUrl = '',
  ctaExternal = false,
  headlineStyle = 'default',
  items = [
    { stars: 5, title: 'Very effective', text: 'Very effective and very convenient — no more cluttered bathroom counter. Cleans gently but thoroughly.', name: 'Miguel', date: '27 August' },
    { stars: 5, title: 'I have very sensitive gums', text: 'I have very sensitive gums that bleed with every brushing. I was skeptical about switching. The result? Thrilled!', name: 'Barbara', date: '30 September' },
    { stars: 5, title: 'I adopted it!', text: 'I have used another brand for years, but I am thrilled to have switched: softer yet more effective, quieter, and cleaner. Do not hesitate!', name: 'Cathrine', date: '29 July' },
    { stars: 5, title: 'Very good product', text: 'Very good product. I was skeptical at first because of the price, but it is indeed superb. Nice design, easy to use, very clever.', name: 'Franck', date: '30 June' },
    { stars: 5, title: 'I love it! 😍', text: 'At first I thought I would return it, but after a few days I got used to it and I am really glad I persisted. Best out there.', name: 'Gerard', date: '30 June' },
  ],
  autoScroll = true,
  direction = 'left',
  speed = 55,
  columns = '4',
  cardWidth = 320,
  theme = 'light',
  green,
  bg,
  cardBg,
  headingColor,
  titleColor,
  textColor,
  ctaBg,
  ctaColor,
}: TrustpilotWallProps) {
  const head = headline.trim();
  const label = ratingLabel.trim();
  const prefix = reviewsPrefix.trim();
  const count = reviewsCount.trim();
  const suffix = reviewsSuffix.trim();
  const cta = ctaText.trim();

  const renderItems = autoScroll ? [...items, ...items] : items;
  const ratingDisp = displayRating(ratingValue);

  const style: CSSVars = {
    '--nv-tp-cols': columns,
    '--nv-tp-card-w': `${cardWidth}px`,
  };
  if (green !== undefined) style['--nv-tp-green'] = green;
  if (bg !== undefined) style['--nv-tp-bg'] = bg;
  if (cardBg !== undefined) style['--nv-tp-card'] = cardBg;
  if (headingColor !== undefined) style['--nv-tp-heading'] = headingColor;
  if (titleColor !== undefined) style['--nv-tp-title'] = titleColor;
  if (textColor !== undefined) style['--nv-tp-text'] = textColor;
  if (ctaBg !== undefined) style['--nv-tp-cta-bg'] = ctaBg;
  if (ctaColor !== undefined) style['--nv-tp-cta-color'] = ctaColor;

  const trackStyle: CSSVars = { '--nv-tp-dur': `${speed}s` };

  return (
    <section className={`nv-pw-tp nv-pw-tp--${theme}`} style={style}>
      {(head !== '' || showRating) && (
        <div className="nv-pw-tp__head">
          {head !== '' && <h2 className={`nv-pw-tp__headline${headlineMod(headlineStyle)}`}>{renderHeadline(head)}</h2>}
          {showRating && (
            <>
              <div className="nv-pw-tp__rating">
                {label !== '' && <span className="nv-pw-tp__rating-label">{label}</span>}
                <span className="nv-pw-tp__rating-score">{ratingDisp} / 5</span>
                <Blocks rating={ratingValue} size="lg" />
              </div>
              {(count !== '' || prefix !== '' || suffix !== '') && (
                <p className="nv-pw-tp__count">
                  {prefix !== '' && `${prefix} `}
                  {count !== '' && <><strong>{count}</strong>{' '}</>}
                  {suffix !== '' && suffix}
                </p>
              )}
            </>
          )}
        </div>
      )}

      <div className={`nv-pw-tp__wall${autoScroll ? ` nv-pw-tp__wall--marquee nv-pw-tp__wall--${direction}` : ' nv-pw-tp__wall--grid'}`}>
        <div className="nv-pw-tp__track" style={trackStyle}>
          {renderItems.map((it, i) => <Card it={it} key={i} />)}
        </div>
      </div>

      {cta !== '' && (
        <div className="nv-pw-tp__foot"><a className="nv-pw-tp__cta" href={ctaUrl !== '' ? ctaUrl : '#'} {...(ctaExternal ? { target: '_blank', rel: 'noopener' } : {})}>{cta}</a></div>
      )}
    </section>
  );
}
