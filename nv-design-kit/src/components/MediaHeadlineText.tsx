import { headlineMod, type HeadlineStyle } from '../types';

/** Best-effort MIME type from a self-hosted video's file extension — mirrors
 * NV_PW_Media::mime(). */
function videoMime(url: string): string {
  const clean = url.split('?')[0].split('#')[0];
  const ext = clean.slice(clean.lastIndexOf('.') + 1).toLowerCase();
  const map: Record<string, string> = {
    mp4: 'video/mp4',
    m4v: 'video/mp4',
    webm: 'video/webm',
    ogv: 'video/ogg',
    ogg: 'video/ogg',
    mov: 'video/quicktime',
  };
  return map[ext] ?? 'video/mp4';
}

export interface MediaHeadlineTextProps {
  /** Which side the media sits on. */
  mediaPosition?: 'left' | 'right';
  /** Image URL (rendered when set and no video is chosen). */
  image?: string;
  /** Self-hosted video URL (MP4 / WebM) — takes over the media slot when set. */
  video?: string;
  /** Poster image shown before the video plays. */
  videoPoster?: string;
  /** Autoplay the video (forces muted, as browsers require). */
  videoAutoplay?: boolean;
  /** Loop the video. */
  videoLoop?: boolean;
  /** Mute the video. */
  videoMuted?: boolean;
  /** Show native player controls. */
  videoControls?: boolean;
  /** Small uppercase overline above the headline. */
  eyebrow?: string;
  /** The headline. */
  headline?: string;
  /** Default sans or editorial serif-italic headline face. */
  headlineStyle?: HeadlineStyle;
  /** Body paragraph. */
  body?: string;
  /** Button label (button hidden unless both label and URL are set). */
  buttonText?: string;
  /** Button href (empty by default → button hidden). */
  buttonUrl?: string;
}

/**
 * NV: Media + Headline + Text — a two-column story block pairing an image (or a
 * self-hosted video) with an overline, headline, body paragraph and an optional
 * button. Mirrors the `nv-media-headline-text` Elementor widget.
 */
export function MediaHeadlineText({
  mediaPosition = 'left',
  image,
  video,
  videoPoster,
  videoAutoplay = true,
  videoLoop = true,
  videoMuted = true,
  videoControls = false,
  eyebrow = 'Varför det fungerar',
  headline = 'Effektiv lösning för din vardag',
  headlineStyle = 'default',
  body = 'Beskriv huvudfördelen med 2-3 meningar. Håll texten enkel, konkret och lätt att skanna.',
  buttonText = 'Läs mer',
  buttonUrl = '',
}: MediaHeadlineTextProps) {
  const position = mediaPosition === 'right' ? 'right' : 'left';
  const isVideo = !!video;
  const hasMedia = isVideo || !!image;
  const hasButton = buttonText !== '' && buttonUrl !== '';
  const muted = videoAutoplay ? true : videoMuted;

  return (
    <div className={`nv-pw-media-text nv-pw-media-text--${position}`}>
      {hasMedia && (
        <div className="nv-pw-media-text__media">
          {isVideo ? (
            <video
              className="nv-pw-media__video"
              playsInline
              preload="metadata"
              autoPlay={videoAutoplay}
              loop={videoLoop}
              muted={muted}
              controls={videoControls}
              poster={videoPoster || undefined}
            >
              <source src={video} type={videoMime(video as string)} />
            </video>
          ) : (
            <img src={image} alt={headline} loading="lazy" />
          )}
        </div>
      )}

      <div className="nv-pw-media-text__content">
        {eyebrow && <p className="nv-pw-media-text__eyebrow">{eyebrow}</p>}
        {headline && <h3 className={`nv-pw-media-text__headline${headlineMod(headlineStyle)}`}>{headline}</h3>}
        {body && (
          <div className="nv-pw-media-text__body">
            <p>{body}</p>
          </div>
        )}
        {hasButton && (
          <a className="nv-pw-media-text__button" href={buttonUrl}>
            {buttonText}
          </a>
        )}
      </div>
    </div>
  );
}
