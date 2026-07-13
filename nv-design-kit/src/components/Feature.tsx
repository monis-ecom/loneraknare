import type { ReactNode } from 'react';
import { headlineMod, type HeadlineStyle, type CSSVars } from '../types';
import { PLACEHOLDER_IMAGE } from '../placeholder';

/** One feature bullet: a marker (image URL, custom icon node, or the default
 * checkmark) plus a bold title and optional description line. */
export interface FeatureBullet {
  /** Bold title of the bullet. */
  text: string;
  /** Optional supporting line under the title. */
  desc?: string;
  /** Custom marker icon (defaults to a checkmark). Ignored when `image` is set. */
  icon?: ReactNode;
  /** Image marker URL (e.g. a small badge). Overrides `icon`. */
  image?: string;
}

export interface FeatureProps {
  /** split = image beside text · icon-list = image + checklist · centered = no image. */
  layout?: 'split' | 'icon-list' | 'centered';
  /** Media image URL (hidden when layout is `centered`). */
  image?: string;
  /** Alt text for the image. */
  imageAlt?: string;
  /** Self-hosted video URL — renders a looping muted `<video>` instead of the image. */
  video?: string;
  /** Poster image shown before the video plays. */
  videoPoster?: string;
  /** Which side the media sits on. */
  imageSide?: 'left' | 'right';
  /** Small uppercase overline above the headline. */
  eyebrow?: string;
  /** The section headline. */
  headline?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Body paragraph. */
  text?: string;
  /** Optional bullet / checklist rows. */
  bullets?: FeatureBullet[];
  /** CTA button label (button hidden when empty). */
  ctaText?: string;
  /** CTA button href. */
  ctaUrl?: string;
  /** Accent color (highlights, marker fills). */
  accent?: string;
  /** Headline color. */
  headingColor?: string;
  /** Body-text color. */
  textColor?: string;
  /** Media corner radius in px. */
  radius?: number;
}

const Check = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
);

/**
 * NV: Feature / Image + Text — a split image-and-text section with an optional
 * overline, checklist bullets and a CTA. The workhorse "why it works" block of a
 * product landing page. Mirrors the `nv-feature` Elementor widget.
 */
export function Feature({
  layout = 'split',
  image = PLACEHOLDER_IMAGE,
  imageAlt = '',
  video,
  videoPoster,
  imageSide = 'left',
  eyebrow = 'FUNKTION',
  headline = 'Byggd för vardagen.',
  headlineStyle = 'default',
  text = 'Slitstarka material och genomtänkt design som håller år efter år – utan att tumma på komforten.',
  bullets = [
    { text: 'Hållbart material' },
    { text: 'Lätt att rengöra' },
  ],
  ctaText,
  ctaUrl = '#',
  accent = '#312E81',
  headingColor = '#14161D',
  textColor = '#4B5563',
  radius = 16,
}: FeatureProps) {
  const style: CSSVars = {
    '--nv-feat-accent': accent,
    '--nv-feat-heading': headingColor,
    '--nv-feat-text': textColor,
  };
  const isVideo = !!video;
  const hasMedia = isVideo || !!image;
  const mediaStyle = { borderRadius: radius } as const;

  return (
    <div className={`nv-pw-feat nv-pw-feat--L-${layout} nv-pw-feat--img-${imageSide}`} style={style}>
      {hasMedia && layout !== 'centered' && (
        <div className="nv-pw-feat__media">
          {isVideo ? (
            <video src={video} poster={videoPoster} autoPlay loop muted playsInline style={mediaStyle} />
          ) : (
            <img src={image} alt={imageAlt || headline} loading="lazy" style={mediaStyle} />
          )}
        </div>
      )}
      <div className="nv-pw-feat__body">
        {eyebrow && <span className="nv-pw-feat__eyebrow">{eyebrow}</span>}
        {headline && <h3 className={`nv-pw-feat__headline${headlineMod(headlineStyle)}`}>{headline}</h3>}
        {text && <p className="nv-pw-feat__text">{text}</p>}
        {bullets.length > 0 && (
          <ul className="nv-pw-feat__bullets">
            {bullets.map((b, i) => (
              <li className="nv-pw-feat__bullet" key={i}>
                <span className={`nv-pw-feat__bicon${b.image ? ' nv-pw-feat__bicon--image' : ''}`} aria-hidden="true">
                  {b.image ? <img src={b.image} alt="" loading="lazy" /> : (b.icon ?? <Check />)}
                </span>
                <span className="nv-pw-feat__btext">
                  <strong className="nv-pw-feat__btitle">{b.text}</strong>
                  {b.desc && <span className="nv-pw-feat__bdesc">{b.desc}</span>}
                </span>
              </li>
            ))}
          </ul>
        )}
        {ctaText && (
          <a className="nv-pw-feat__btn" href={ctaUrl}>{ctaText}</a>
        )}
      </div>
    </div>
  );
}
