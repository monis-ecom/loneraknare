export interface CtaBlockProps {
  /** Small overline above the headline. */
  eyebrow?: string;
  /** Main headline. */
  headline?: string;
  /** Supporting description. */
  description?: string;
  /** Primary button label. */
  primaryText?: string;
  /** Primary button URL (button shows only when both text and URL are set). */
  primaryUrl?: string;
  /** Open the primary link in a new tab. */
  primaryExternal?: boolean;
  /** Mark the primary link rel=nofollow. */
  primaryNofollow?: boolean;
  /** Secondary link label. */
  secondaryText?: string;
  /** Secondary link URL (link shows only when both text and URL are set). */
  secondaryUrl?: string;
  /** Open the secondary link in a new tab. */
  secondaryExternal?: boolean;
  /** Mark the secondary link rel=nofollow. */
  secondaryNofollow?: boolean;
}

/**
 * NV: CTA Block — a centered conversion block with an eyebrow, headline,
 * description and a primary button plus an optional secondary link. Mirrors the
 * `nv-cta-block` Elementor widget.
 */
export function CtaBlock({
  eyebrow = 'Redo att komma igång?',
  headline = 'Välj paket och beställ idag',
  description = 'Få snabb leverans, trygg betalning och support från vårt svenska team.',
  primaryText = 'Köp nu',
  primaryUrl = '#',
  primaryExternal = false,
  primaryNofollow = false,
  secondaryText = 'Se alla paket',
  secondaryUrl = '#',
  secondaryExternal = false,
  secondaryNofollow = false,
}: CtaBlockProps) {
  const hasPrimary = primaryText.trim() !== '' && primaryUrl.trim() !== '';
  const hasSecondary = secondaryText.trim() !== '' && secondaryUrl.trim() !== '';

  return (
    <div className="nv-pw-cta-block">
      {eyebrow !== '' && <p className="nv-pw-cta-block__eyebrow">{eyebrow}</p>}
      {headline !== '' && <h3 className="nv-pw-cta-block__headline">{headline}</h3>}
      {description !== '' && <p className="nv-pw-cta-block__description">{description}</p>}

      {(hasPrimary || hasSecondary) && (
        <div className="nv-pw-cta-block__actions">
          {hasPrimary && (
            <a
              className="nv-pw-cta-block__button"
              href={primaryUrl}
              {...(primaryExternal ? { target: '_blank' } : {})}
              {...(primaryNofollow ? { rel: 'nofollow' } : {})}
            >
              {primaryText}
            </a>
          )}
          {hasSecondary && (
            <a
              className="nv-pw-cta-block__link"
              href={secondaryUrl}
              {...(secondaryExternal ? { target: '_blank' } : {})}
              {...(secondaryNofollow ? { rel: 'nofollow' } : {})}
            >
              {secondaryText}
            </a>
          )}
        </div>
      )}
    </div>
  );
}
