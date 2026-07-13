/**
 * Self-contained, on-brand placeholder image (data-URI SVG). Used as the default
 * media for image-bearing components so they render complete without any network
 * request. Swap for a real product photo in production.
 */
export const PLACEHOLDER_IMAGE =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" width="640" height="640" viewBox="0 0 640 640">
      <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="#EEF0FA"/><stop offset="1" stop-color="#DfE3F5"/>
        </linearGradient>
      </defs>
      <rect width="640" height="640" fill="url(#g)"/>
      <g transform="translate(320 330)">
        <ellipse cx="0" cy="0" rx="120" ry="230" fill="#C7CCE4"/>
        <ellipse cx="0" cy="-140" rx="118" ry="120" fill="#B4BADA"/>
        <path d="M-70 -170 q70 -60 140 0 q30 130 0 300 q-70 60 -140 0 q-30 -170 0 -300z" fill="#9AA2CE" opacity="0.55"/>
        <circle cx="0" cy="120" r="20" fill="#C6FF3D"/>
        <circle cx="-40" cy="60" r="14" fill="#C6FF3D"/>
        <circle cx="42" cy="70" r="14" fill="#C6FF3D"/>
      </g>
      <text x="320" y="600" text-anchor="middle" font-family="Sora, sans-serif" font-size="26" font-weight="700" fill="#312E81">PadelFlex™</text>
    </svg>`,
  );
