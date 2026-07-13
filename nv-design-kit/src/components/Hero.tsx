import { headlineMod, type HeadlineStyle, type CSSVars } from '../types';
import { PLACEHOLDER_IMAGE } from '../placeholder';

const Star = () => (
  <svg viewBox="0 0 24 24" width="16" height="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>
);
const Check = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
);

export interface HeroProps {
  /** split = image beside text · centered = image below · overlay = text over image · minimal = no image. */
  layout?: 'split' | 'centered' | 'overlay' | 'minimal';
  /** Small uppercase overline. */
  eyebrow?: string;
  /** Main headline (rendered before the accent highlight). */
  headline?: string;
  /** Accent-colored tail of the headline. */
  highlight?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Supporting paragraph under the headline. */
  subheadline?: string;
  /** Star-rating line (e.g. "4.8/5 based on 2,400+ reviews"). */
  ratingText?: string;
  /** Up to 5 stacked customer avatar URLs shown by the rating. */
  ratingAvatars?: string[];
  /** Benefit checklist bullets. */
  bullets?: string[];
  /** Primary button label. */
  ctaText?: string;
  /** Primary button href. */
  ctaUrl?: string;
  /** Secondary (ghost) button label. */
  cta2Text?: string;
  /** Secondary button href. */
  cta2Url?: string;
  /** Reassurance line under the buttons (e.g. a guarantee). */
  guarantee?: string;
  /** Hero image URL. */
  image?: string;
  /** Optional device mockup wrapper around the image. */
  imageFrame?: 'none' | 'phone' | 'browser';
  /** Which side the image sits on. */
  imageSide?: 'left' | 'right';
  /** Section background color. */
  bg?: string;
  /** Accent color (highlight, primary button). */
  accent?: string;
  /** Headline color. */
  headingColor?: string;
  /** Body-text color. */
  textColor?: string;
}

/**
 * NV: Hero Banner — the top-of-page hero with overline, split headline + accent
 * highlight, star rating, benefit bullets, dual CTAs, a guarantee line and an
 * optional device-framed image. Mirrors the `nv-hero` Elementor widget.
 */
export function Hero({
  layout = 'split',
  eyebrow = 'NU PÅ REA',
  headline = 'Mindre stress.',
  highlight = 'Bättre sömn.',
  headlineStyle = 'default',
  subheadline = 'Den smarta lösningen som hjälper tusentals svenskar att varva ner och sova bättre – varje natt.',
  ratingText = '4.8/5 baserat på 2 400+ recensioner',
  ratingAvatars = [],
  bullets = ['Balanserar nervsystemet', 'Snabb, naturlig effekt', 'Kliniskt testad'],
  ctaText = 'Köp nu',
  ctaUrl = '#',
  cta2Text = 'Läs mer',
  cta2Url = '#',
  guarantee = '30 dagars nöjd-kund-garanti',
  image = PLACEHOLDER_IMAGE,
  imageFrame = 'none',
  imageSide = 'right',
  bg = '#F6F7FB',
  accent = '#312E81',
  headingColor = '#14161D',
  textColor = '#4B5563',
}: HeroProps) {
  const style: CSSVars = {
    '--nv-hero-bg': bg,
    '--nv-hero-accent': accent,
    '--nv-hero-heading': headingColor,
    '--nv-hero-text': textColor,
  };
  const avatars = ratingAvatars.slice(0, 5);
  const alt = `${headline} ${highlight}`.trim();

  return (
    <div className={`nv-pw-hero nv-pw-hero--L-${layout} nv-pw-hero--img-${imageSide}`} style={style}>
      <div className="nv-pw-hero__text">
        {eyebrow && <span className="nv-pw-hero__eyebrow">{eyebrow}</span>}
        <h2 className={`nv-pw-hero__headline${headlineMod(headlineStyle)}`}>
          {headline}{highlight && <> <span className="nv-pw-hero__hl">{highlight}</span></>}
        </h2>
        {(ratingText || avatars.length > 0) && (
          <div className="nv-pw-hero__rating">
            {avatars.length > 0 && (
              <span className="nv-pw-hero__avatars" aria-hidden="true">
                {avatars.map((av, i) => <img className="nv-pw-hero__avatar" src={av} alt="" loading="lazy" key={i} />)}
              </span>
            )}
            <span className="nv-pw-hero__stars" aria-hidden="true">
              {[0, 1, 2, 3, 4].map((i) => <Star key={i} />)}
            </span>
            {ratingText && <span className="nv-pw-hero__rating-text">{ratingText}</span>}
          </div>
        )}
        {subheadline && <p className="nv-pw-hero__sub">{subheadline}</p>}
        {bullets.length > 0 && (
          <ul className="nv-pw-hero__bullets">
            {bullets.map((b, i) => <li key={i}><Check />{b}</li>)}
          </ul>
        )}
        <div className="nv-pw-hero__actions">
          {ctaText && <a className="nv-pw-hero__btn nv-pw-hero__btn--primary" href={ctaUrl}>{ctaText}</a>}
          {cta2Text && <a className="nv-pw-hero__btn nv-pw-hero__btn--ghost" href={cta2Url}>{cta2Text}</a>}
        </div>
        {guarantee && (
          <p className="nv-pw-hero__guarantee">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
            {guarantee}
          </p>
        )}
      </div>
      {image && (
        <div className={`nv-pw-hero__media nv-pw-hero__media--frame-${imageFrame}`}>
          {imageFrame === 'phone' ? (
            <span className="nv-pw-hero__device nv-pw-hero__device--phone"><span className="nv-pw-hero__notch" aria-hidden="true" /><img src={image} alt={alt} loading="lazy" /></span>
          ) : imageFrame === 'browser' ? (
            <span className="nv-pw-hero__device nv-pw-hero__device--browser"><span className="nv-pw-hero__bar" aria-hidden="true"><i /><i /><i /></span><img src={image} alt={alt} loading="lazy" /></span>
          ) : (
            <img src={image} alt={alt} loading="lazy" />
          )}
        </div>
      )}
    </div>
  );
}
