import { useRef, useState, type PointerEvent as ReactPointerEvent, type KeyboardEvent as ReactKeyboardEvent } from 'react';
import { headlineMod, type HeadlineStyle, type CSSVars } from '../types';
import { PLACEHOLDER_IMAGE } from '../placeholder';

export interface BeforeAfterProps {
  /** Optional heading above the slider. */
  heading?: string;
  /** Optional sub-heading paragraph. */
  subheading?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** "Before" image URL. */
  beforeImage?: string;
  /** "After" image URL. */
  afterImage?: string;
  /** Alt text for the before image (defaults to the before label). */
  beforeImageAlt?: string;
  /** Alt text for the after image (defaults to the after label). */
  afterImageAlt?: string;
  /** Corner-badge label on the before layer. */
  beforeLabel?: string;
  /** Corner-badge label on the after layer. */
  afterLabel?: string;
  /** Whether the corner labels are shown. */
  showLabels?: boolean;
  /** Wipe direction. */
  orientation?: 'horizontal' | 'vertical';
  /** Initial split position (0–100). */
  start?: number;
  /** Max width of the slider in px. */
  maxWidth?: number;
  /** Corner radius in px. */
  radius?: number;
  /** Handle / divider color. */
  accent?: string;
  /** Label background (both labels). */
  labelBg?: string;
  /** Label text color (both labels). */
  labelColor?: string;
  /** Before label — text color override. */
  beforeLabelColor?: string;
  /** Before label — background override. */
  beforeLabelBg?: string;
  /** After label — text color override. */
  afterLabelColor?: string;
  /** After label — background override. */
  afterLabelBg?: string;
}

/**
 * NV: Before/After Slider — a draggable image comparison slider that wipes
 * between a "before" and an "after" image, with optional corner labels and a
 * heading. Mirrors the `nv-before-after` Elementor widget.
 */
export function BeforeAfter({
  heading = '',
  subheading = '',
  headlineStyle = 'default',
  beforeImage = PLACEHOLDER_IMAGE,
  afterImage = PLACEHOLDER_IMAGE,
  beforeImageAlt,
  afterImageAlt,
  beforeLabel = 'Före',
  afterLabel = 'Efter',
  showLabels = true,
  orientation = 'horizontal',
  start = 50,
  maxWidth = 720,
  radius = 16,
  accent = '#FFFFFF',
  labelBg = 'rgba(17,17,22,0.62)',
  labelColor = '#FFFFFF',
  beforeLabelColor,
  beforeLabelBg,
  afterLabelColor,
  afterLabelBg,
}: BeforeAfterProps) {
  const clampedStart = Math.max(0, Math.min(100, start));
  const [pos, setPos] = useState<number>(clampedStart);
  const [dragging, setDragging] = useState(false);
  const baRef = useRef<HTMLDivElement>(null);
  const isVertical = orientation === 'vertical';

  const updateFromEvent = (clientX: number, clientY: number) => {
    const el = baRef.current;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const raw = isVertical ? ((clientY - r.top) / r.height) * 100 : ((clientX - r.left) / r.width) * 100;
    setPos(Math.max(0, Math.min(100, raw)));
  };

  const onPointerDown = (e: ReactPointerEvent<HTMLDivElement>) => {
    setDragging(true);
    e.currentTarget.setPointerCapture(e.pointerId);
    updateFromEvent(e.clientX, e.clientY);
  };
  const onPointerMove = (e: ReactPointerEvent<HTMLDivElement>) => {
    if (!dragging) return;
    updateFromEvent(e.clientX, e.clientY);
  };
  const stop = () => setDragging(false);

  const onKeyDown = (e: ReactKeyboardEvent<HTMLDivElement>) => {
    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
      setPos((p) => Math.max(0, p - 2));
    } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
      setPos((p) => Math.min(100, p + 2));
    }
  };

  const baStyle: CSSVars = {
    '--nv-ba-pos': `${pos}%`,
    '--nv-ba-accent': accent,
    '--nv-ba-label-bg': labelBg,
    '--nv-ba-label-color': labelColor,
    maxWidth,
    borderRadius: radius,
  };
  const bAlt = beforeImageAlt ?? (beforeLabel !== '' ? beforeLabel : 'before');
  const aAlt = afterImageAlt ?? (afterLabel !== '' ? afterLabel : 'after');

  const beforeLabelStyle =
    beforeLabelColor || beforeLabelBg
      ? { color: beforeLabelColor, background: beforeLabelBg }
      : undefined;
  const afterLabelStyle =
    afterLabelColor || afterLabelBg
      ? { color: afterLabelColor, background: afterLabelBg }
      : undefined;

  return (
    <div className="nv-pw-ba-wrap">
      {heading !== '' && <h3 className={`nv-pw-ba-heading${headlineMod(headlineStyle)}`}>{heading}</h3>}
      {subheading !== '' && <p className="nv-pw-ba-sub">{subheading}</p>}
      <div
        className={`nv-pw-ba${dragging ? ' is-dragging' : ''}`}
        data-nv-ba
        data-orientation={orientation}
        style={baStyle}
        ref={baRef}
        onPointerDown={onPointerDown}
        onPointerMove={onPointerMove}
        onPointerUp={stop}
        onPointerCancel={stop}
      >
        <div className="nv-pw-ba__layer nv-pw-ba__after">
          <img src={afterImage} alt={aAlt} loading="lazy" draggable={false} />
          {showLabels && afterLabel !== '' && (
            <span className="nv-pw-ba__label nv-pw-ba__label--after" style={afterLabelStyle}>{afterLabel}</span>
          )}
        </div>
        <div className="nv-pw-ba__layer nv-pw-ba__before">
          <img src={beforeImage} alt={bAlt} loading="lazy" draggable={false} />
          {showLabels && beforeLabel !== '' && (
            <span className="nv-pw-ba__label nv-pw-ba__label--before" style={beforeLabelStyle}>{beforeLabel}</span>
          )}
        </div>
        <div
          className="nv-pw-ba__handle"
          role="slider"
          aria-label="Drag to compare"
          aria-valuemin={0}
          aria-valuemax={100}
          aria-valuenow={Math.round(pos)}
          tabIndex={0}
          onKeyDown={onKeyDown}
        >
          <span className="nv-pw-ba__line"></span>
          <span className="nv-pw-ba__knob" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round"><polyline points="9 6 3 12 9 18" /><polyline points="15 6 21 12 15 18" /></svg>
          </span>
        </div>
      </div>
    </div>
  );
}
