import type { CSSProperties } from 'react';

/** Switch a section headline between the default sans face and the editorial
 * serif-italic (Newsreader) look — mirrors the widgets' "Headline style" control. */
export type HeadlineStyle = 'default' | 'editorial';

/** Class modifier appended to a headline element for the editorial style. */
export const headlineMod = (style?: HeadlineStyle): string =>
  style === 'editorial' ? ' nv-hl--editorial' : '';

/** Allow CSS custom properties (--nv-*) in an inline style object. */
export type CSSVars = CSSProperties & Record<`--${string}`, string | number>;
