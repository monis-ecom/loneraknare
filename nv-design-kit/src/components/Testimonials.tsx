import { headlineMod, renderHeadline, type HeadlineStyle, type CSSVars } from '../types';

/** A single testimonial card: a star rating, quote, author name, meta line and
 * optional avatar (falls back to an initial) plus a verified badge. */
export interface Testimonial {
  /** Star rating 1–5. */
  stars: number;
  /** The review quote. */
  quote: string;
  /** Author name. */
  name: string;
  /** Meta / role line (e.g. "Verifierad köpare"). */
  meta: string;
  /** Optional avatar image URL — an initial is shown when absent. */
  avatar?: string;
  /** Show the small verified checkmark before the meta line. */
  verified: boolean;
}

export interface TestimonialsProps {
  /** Section heading. */
  heading?: string;
  /** Optional subheading below the heading. */
  subheading?: string;
  /** Column count for the grid layouts. */
  columns?: '1' | '2' | '3';
  /** Overall layout mode. */
  layout?: 'grid' | 'carousel' | 'marquee' | 'header' | 'single' | 'compact';
  /** Aggregate score shown in the `header` layout. */
  aggScore?: string;
  /** Aggregate review-count text shown in the `header` layout. */
  aggCount?: string;
  /** Marquee auto-scroll duration in seconds. */
  marqueeSpeed?: number;
  /** Marquee card width in px. */
  marqueeWidth?: number;
  /** Carousel card width in px. */
  cardWidth?: number;
  /** Show carousel arrows. */
  showArrows?: boolean;
  /** Show carousel dots. */
  showDots?: boolean;
  /** Carousel autoplay. */
  autoplay?: boolean;
  /** Carousel autoplay delay in seconds. */
  autoplaySpeed?: number;
  /** The testimonial cards. */
  items?: Testimonial[];
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Card background color. */
  cardBg?: string;
  /** Text color. */
  textColor?: string;
  /** Star color. */
  starColor?: string;
  /** Carousel accent (arrows / active dot). */
  carouselAccent?: string;
}

function Stars({ count }: { count: number }) {
  const n = Math.max(0, Math.min(5, count));
  return (
    <div className="nv-pw-tm__stars" aria-label={`${n} av 5 stjärnor`}>
      {[1, 2, 3, 4, 5].map((i) => (
        <svg key={i} className={`nv-pw-tm__star${i <= n ? ' is-on' : ''}`} viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>
      ))}
    </div>
  );
}

function Card({ it }: { it: Testimonial }) {
  const name = it.name.trim();
  const meta = it.meta.trim();
  const avatar = it.avatar ?? '';
  const initial = name !== '' ? name.charAt(0).toUpperCase() : '★';
  return (
    <figure className="nv-pw-tm__card">
      <Stars count={it.stars} />
      <blockquote className="nv-pw-tm__quote">{it.quote}</blockquote>
      <figcaption className="nv-pw-tm__foot">
        {avatar !== '' ? (
          <img className="nv-pw-tm__avatar" src={avatar} alt={name} loading="lazy" />
        ) : (
          <span className="nv-pw-tm__avatar nv-pw-tm__avatar--initial" aria-hidden="true">{initial}</span>
        )}
        <span className="nv-pw-tm__who">
          {name !== '' && <strong className="nv-pw-tm__name">{name}</strong>}
          {(meta !== '' || it.verified) && (
            <span className="nv-pw-tm__meta">
              {it.verified && <svg className="nv-pw-tm__check" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>}
              {meta}
            </span>
          )}
        </span>
      </figcaption>
    </figure>
  );
}

/**
 * NV: Testimonials — a social-proof block of star-rated review cards with several
 * layouts (grid, carousel, continuous marquee, rating header, single quote and
 * compact). Mirrors the `nv-testimonials` Elementor widget.
 */
export function Testimonials({
  heading = 'Vad våra kunder säger',
  subheading = '',
  columns = '3',
  layout = 'grid',
  aggScore = '4.8',
  aggCount = 'baserat på 2 400+ recensioner',
  marqueeSpeed = 45,
  marqueeWidth = 330,
  cardWidth = 320,
  showArrows = true,
  showDots = true,
  autoplay = false,
  autoplaySpeed = 5,
  items = [
    { stars: 5, quote: 'Bästa köpet jag gjort i år – fungerade direkt och känns gediget.', name: 'Anna L.', meta: 'Verifierad köpare', verified: true },
    { stars: 5, quote: 'Snabb leverans och precis som beskrivet. Rekommenderas verkligen!', name: 'Johan M.', meta: 'Verifierad köpare', verified: true },
    { stars: 5, quote: 'Kvaliteten överträffade förväntningarna. Köper igen.', name: 'Sara K.', meta: 'Verifierad köpare', verified: true },
  ],
  headlineStyle = 'default',
  cardBg = '#FFFFFF',
  textColor = '#1F2430',
  starColor = '#F5A623',
  carouselAccent = '#312E81',
}: TestimonialsProps) {
  const isCarousel = layout === 'carousel';
  const isMarquee = layout === 'marquee';
  const renderItems = isMarquee ? [...items, ...items] : items;
  const score = aggScore.trim();
  const count = aggCount.trim();
  const head = heading.trim();
  const sub = subheading.trim();

  const style: CSSVars = {
    '--nv-tm-cols': columns,
    '--nv-tm-card-bg': cardBg,
    '--nv-tm-text': textColor,
    '--nv-tm-star': starColor,
    '--nv-tm-accent': carouselAccent,
    '--nv-tm-card-w': `${cardWidth}px`,
    '--nv-tm-marq-w': `${marqueeWidth}px`,
  };
  const trackStyle: CSSVars = { '--nv-tm-marq-dur': `${marqueeSpeed}s` };

  const autoplayMs = Math.max(2, autoplaySpeed) * 1000;
  const carouselAttrs = isCarousel
    ? { 'data-nv-tm-carousel': true, ...(autoplay ? { 'data-nv-tm-autoplay': '1', 'data-nv-tm-interval': String(autoplayMs) } : {}) }
    : {};

  const cards = renderItems.map((it, i) => <Card it={it} key={i} />);

  return (
    <div className={`nv-pw-tm nv-pw-tm--L-${layout}`} style={style} {...carouselAttrs}>
      {layout === 'header' && (score !== '' || count !== '') && (
        <div className="nv-pw-tm__agg">
          <span className="nv-pw-tm__agg-score">{score}</span>
          <span className="nv-pw-tm__stars nv-pw-tm__agg-stars" aria-hidden="true">
            {[0, 1, 2, 3, 4].map((z) => (
              <svg key={z} className="nv-pw-tm__star is-on" viewBox="0 0 24 24" width="18" height="18"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>
            ))}
          </span>
          {count !== '' && <span className="nv-pw-tm__agg-count">{count}</span>}
        </div>
      )}
      {(head !== '' || sub !== '') && (
        <div className="nv-pw-tm__head">
          {head !== '' && <h3 className={`nv-pw-tm__heading${headlineMod(headlineStyle)}`}>{renderHeadline(head)}</h3>}
          {sub !== '' && <p className="nv-pw-tm__sub">{sub}</p>}
        </div>
      )}
      {isMarquee ? (
        <div className="nv-pw-tm__marquee">
          <div className="nv-pw-tm__marquee-track" style={trackStyle}>{cards}</div>
        </div>
      ) : isCarousel ? (
        <>
          <div className="nv-pw-tm__carousel">
            {showArrows && (
              <button type="button" className="nv-pw-tm__arrow nv-pw-tm__arrow--prev" data-nv-tm-prev aria-label="Previous reviews">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
              </button>
            )}
            <div className="nv-pw-tm__grid nv-pw-tm__grid--carousel" data-nv-tm-track tabIndex={0}>{cards}</div>
            {showArrows && (
              <button type="button" className="nv-pw-tm__arrow nv-pw-tm__arrow--next" data-nv-tm-next aria-label="Next reviews">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
              </button>
            )}
          </div>
          {showDots && (
            <div className="nv-pw-tm__dots" data-nv-tm-dots role="tablist" aria-label="Reviews pagination">
              {items.map((_, di) => (
                <button type="button" key={di} className={`nv-pw-tm__dot${di === 0 ? ' is-active' : ''}`} data-nv-tm-dot={di} aria-label={`Go to review ${di + 1}`} />
              ))}
            </div>
          )}
        </>
      ) : (
        <div className="nv-pw-tm__grid">{cards}</div>
      )}
    </div>
  );
}
