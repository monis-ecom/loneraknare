import type { ReactNode } from 'react';
import { headlineMod, type HeadlineStyle, type CSSVars } from '../types';

const Star = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>
);
const Truck = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M1 3h15v13H1z" /><path d="M16 8h4l3 3v5h-7z" /><circle cx="5.5" cy="18.5" r="2.5" /><circle cx="18.5" cy="18.5" r="2.5" /></svg>
);
const Shield = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
);
const Medal = () => (
  <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><circle cx="12" cy="15" r="6" /><path d="M9 9 6 2M15 9l3-7M9 2h6" /></svg>
);

/** One column: an editor-chosen icon (defaults to a star), a title, a short
 * supporting line, and an optional link that turns the column into an anchor. */
export interface IconColumn {
  /** Marker icon (defaults to a star). */
  icon?: ReactNode;
  /** Column title. */
  title?: string;
  /** Supporting sentence. */
  text?: string;
  /** Optional URL — renders the column as an `<a>` instead of a `<div>`. */
  link?: string;
  /** Open the link in a new tab (adds target=_blank rel=noopener). */
  linkExternal?: boolean;
}

export interface IconColumnsProps {
  /** Optional section heading. */
  heading?: string;
  /** Optional section subheading. */
  subheading?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** The columns. */
  items?: IconColumn[];
  /** Number of grid columns (1–6). */
  columns?: number;
  /** Icon above text (`stacked`) or beside text (`inline`). */
  layout?: 'stacked' | 'inline';
  /** Content alignment. */
  align?: 'left' | 'center';
  /** Draw each column as a boxed card. */
  boxed?: boolean;
  /** Icon size in px. */
  iconSize?: number;
  /** Icon color (defaults to the global brand accent). */
  iconColor?: string;
  /** Icon background — also enables the padded icon-badge style. */
  iconBg?: string;
  /** Title color. */
  titleColor?: string;
  /** Text color. */
  textColor?: string;
}

/**
 * NV: Icon Columns — a 2–6 column icon + heading + text grid; the flexible
 * multicolumn "why buy" row with an optional heading, boxed cards and per-column
 * links. Mirrors the `nv-icon-columns` Elementor widget.
 */
export function IconColumns({
  heading = '',
  subheading = '',
  headlineStyle = 'default',
  items = [
    { icon: <Truck />, title: 'Fri frakt', text: 'Fri, spårbar frakt i hela Norden.' },
    { icon: <Shield />, title: '60 dagars öppet köp', text: 'Nöjd eller pengarna tillbaka.' },
    { icon: <Medal />, title: '9 000+ nöjda kunder', text: 'Betyg 4,8 av 5 i snitt.' },
  ],
  columns = 3,
  layout = 'stacked',
  align = 'center',
  boxed = false,
  iconSize = 32,
  iconColor,
  iconBg,
  titleColor,
  textColor,
}: IconColumnsProps) {
  const hasBg = !!iconBg && iconBg !== '';
  const style: CSSVars = {
    '--nv-ic-cols': columns,
    '--nv-ic-align': align,
  };
  if (iconColor) style['--nv-ic-icon'] = iconColor;
  if (hasBg) style['--nv-ic-icon-bg'] = iconBg as string;
  if (titleColor) style['--nv-ic-title'] = titleColor;
  if (textColor) style['--nv-ic-text'] = textColor;

  const rootClass =
    `nv-pw-ic nv-pw-ic--${layout}` +
    (boxed ? ' nv-pw-ic--boxed' : '') +
    (hasBg ? ' nv-pw-ic--icon-bg' : '');

  return (
    <div className={rootClass} style={style}>
      {(heading !== '' || subheading !== '') && (
        <div className="nv-pw-ic__head">
          {heading !== '' && <h2 className={`nv-pw-ic__heading${headlineMod(headlineStyle)}`}>{heading}</h2>}
          {subheading !== '' && <p className="nv-pw-ic__sub">{subheading}</p>}
        </div>
      )}
      <div className="nv-pw-ic__grid">
        {items.map((it, i) => {
          const title = (it.title ?? '').trim();
          const text = (it.text ?? '').trim();
          const link = it.link ?? '';
          const icon = it.icon !== undefined ? it.icon : <Star />;
          const inner = (
            <>
              {icon && (
                <span className="nv-pw-ic__icon" style={{ fontSize: `${iconSize}px` }}>{icon}</span>
              )}
              <span className="nv-pw-ic__body">
                {title !== '' && <span className="nv-pw-ic__title">{title}</span>}
                {text !== '' && <span className="nv-pw-ic__text">{text}</span>}
              </span>
            </>
          );
          return link !== '' ? (
            <a
              className="nv-pw-ic__col"
              href={link}
              key={i}
              {...(it.linkExternal ? { target: '_blank', rel: 'noopener' } : {})}
            >
              {inner}
            </a>
          ) : (
            <div className="nv-pw-ic__col" key={i}>{inner}</div>
          );
        })}
      </div>
    </div>
  );
}
