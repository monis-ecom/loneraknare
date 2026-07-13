import { createElement, Fragment, type CSSProperties, type ReactNode } from 'react';

/** Switch a section headline between the default sans face, a full editorial
 * serif-italic (Newsreader) look, and an editorial "mixed" look (upright serif
 * base with only the *asterisked* accent italicised) — mirrors the widgets'
 * "Headline style" control. */
export type HeadlineStyle = 'default' | 'editorial' | 'mixed';

/** Class modifier appended to a headline element for the chosen style. */
export const headlineMod = (style?: HeadlineStyle): string =>
  style === 'editorial' ? ' nv-hl--editorial' : style === 'mixed' ? ' nv-hl--mixed' : '';

/** Render a headline string, converting *asterisk-wrapped* runs into an italic
 * Newsreader accent (`<em class="nv-hl-em">`). Works in every headline style. */
export function renderHeadline(text?: string): ReactNode {
  if (!text) return text ?? null;
  const parts = text.split(/\*([^*]+)\*/g); // odd indices are the emphasized runs
  return parts.map((p, i) =>
    i % 2 === 1
      ? createElement('em', { key: i, className: 'nv-hl-em' }, p)
      : createElement(Fragment, { key: i }, p),
  );
}

/** Allow CSS custom properties (--nv-*) in an inline style object. */
export type CSSVars = CSSProperties & Record<`--${string}`, string | number>;
