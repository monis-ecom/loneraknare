# Handoff: Dictly — voice dictation for iOS, macOS and watchOS

## Overview

Dictly turns messy speech into clean text and puts it where the user is already
typing. It is deliberately small in scope and strong in stance:

- **No accounts, no server, no paywall.** The user brings their own API keys.
- **Provider-agnostic.** Any OpenAI-compatible endpoint works — Groq, OpenAI,
  Cerebras, Deepgram, OpenRouter, a local Ollama box. Two roles are configured
  separately: a *speech* provider (audio → raw transcript) and a *text* provider
  (raw transcript → cleaned text).
- **Two-stage pipeline.** Transcribe, then clean up. The cleanup step is what
  makes output feel written rather than transcribed: filler removed, punctuation
  added, shaped to the host app (a Slack line stays lowercase; an email gets a
  greeting and a sign-off).
- **Dark only.** One accent colour. No marketing surfaces inside the app.

Three platforms, three different answers to the same question — "how do you
start dictating without leaving what you're doing?"

| Platform | Trigger | Where text lands |
|---|---|---|
| iOS | Custom keyboard, globe key → Dictly | At the cursor in the host app |
| macOS | Hold `fn` (menu bar app, no Dock icon) | At the cursor via Accessibility |
| watchOS | Action button | Handed off to the Mac clipboard |

## About the design files

The `.dc.html` files in this bundle are **design references**. They are static
HTML/CSS prototypes that show intended look, layout, copy and motion — they are
not production code and contain no real audio capture, networking or state.

The task is to **recreate these designs in the target codebase's environment**.
For this product that almost certainly means native Apple platforms:

- iOS: Swift + SwiftUI app, plus a Keyboard Extension target
- macOS: SwiftUI menu bar app (`MenuBarExtra`, `LSUIElement`)
- watchOS: SwiftUI watch app

If you are instead targeting an existing cross-platform codebase, use its
established patterns and component library and treat these files purely as the
visual and behavioural spec. Do not port the HTML.

## Fidelity

**High-fidelity.** Colours, type sizes, weights, spacing, radii, copy and motion
timings are all final and intentional. Recreate them faithfully. Where a value
is not listed in this document, read it off the corresponding HTML file — every
value in those files is literal and inline.

Two deliberate exceptions:

- Avatars, message bubbles and host-app chrome (Slack, Mail, Messages, Xcode,
  Terminal, Safari) are **stand-ins for context only**. They exist so each board
  shows Dictly inside a real situation. Do not build them.
- The device frames (iPhone bezel, macOS window chrome, watch case) are
  presentation scaffolding, not app UI.

## Design tokens

### Colour

| Token | Value | Use |
|---|---|---|
| `bg` | `#000000` | Screen background (all platforms) |
| `surface` | `#0C0C0C` | Cards, lists, panels, HUD |
| `raised` | `#161616` | Chips, toggles-off, secondary buttons, search fields |
| `keyboardBg` | `#0C0C0C` | iOS keyboard tray background |
| `hostBg` | `#050505` | Host-app background behind the keyboard (mock only) |
| `accent` | `#BFF54A` | Lime. Mic, primary action, waveform, active state, selected row |
| `accentSoft` | `rgba(191,245,74,0.12)` | Accent-tinted row/pill backgrounds, success banner |
| `accentQuiet` | `rgba(191,245,74,0.28)` | Waveform at rest (idle) |
| `onAccent` | `#0A0B0A` | Text/glyphs on top of lime |
| `text` | `#F1F3EC` | Primary text |
| `textDim` | `rgba(241,243,236,0.60)` | Secondary text, sublabels |
| `textFaint` | `rgba(241,243,236,0.36)` | Meta, hints, disabled, mono URLs |
| `textGhost` | `rgba(241,243,236,0.30)` | Placeholder text |
| `hairline` | `rgba(255,255,255,0.08)` | Dividers, keyboard top border |
| `border` | `rgba(255,255,255,0.09)` | Card borders (macOS/watch) |
| `borderStrong` | `rgba(255,255,255,0.12)` – `0.14` | Input borders, secondary button outline |
| `warn` | `#FF9F45` | Provider unreachable, permission missing. **Orange, never red** |
| `info` | `#6AA8FF` | On-device / offline processing; iOS system dialog buttons |
| `danger` | `#FF5C5C` | Destructive only (Delete all) |
| `canvas` | `#141414` | Design-sheet background — not an app colour |

Rules:

- **One accent.** Lime marks the mic, the single primary action per screen, live
  audio, and the selected item. Nothing else.
- **Never red for provider failure.** Red next to lime vibrates badly; orange
  reads as "needs attention" without alarm.
- **Blue means local.** On-device processing and offline capture use `info`, so
  the user can tell at a glance that audio is not leaving the device.
- Text on lime is always `onAccent`, never pure black or white.

### Typography

Family: **Albert Sans** (400 / 500 / 600 / 700). Monospace for anything
machine-owned — model IDs, base URLs, code — using the system mono stack
(`ui-monospace, Menlo, monospace`).

Two rules that carry the whole type system: numbers that change in place get
`font-variant-numeric: tabular-nums` (timers, latency, prices, token counts);
long-form body copy gets `text-wrap: pretty`.

**iOS** (points; 402 × 874 @1x layout)

| Role | Size / line-height / weight |
|---|---|
| Screen title | 28 / 1.15 / 600, `letter-spacing: -.02em` |
| Section title (keyboard failure) | 22 / 1.25 / 600 |
| Body, list row | 16 / 1.4 / 400 |
| Message bubble | 15 / 1.4 / 400 |
| Secondary body | 14 / 1.45 / 400 |
| Sublabel, mono model ID | 12 / 1.3 / 400 |
| Caption / hint | 12 / 1.4 / 400 |
| Group header | 13 / 1.3 / 600, `.08em`, uppercase |
| Accent eyebrow | 11 / 1.2 / 600, `.14em`, uppercase |
| Pill / chip | 12 / 1 / 600 |

**macOS** (points; boards drawn at 1280 × 800 desktop, 980-wide settings window)

| Role | Size / line-height / weight |
|---|---|
| Pane title | 28 / 1.15 / 600, `-.02em` |
| Sheet title | 20 / 1.2 / 600 |
| Body, list row | 15 / 1.4 / 400 |
| Secondary body, HUD result | 14.5 / 1.45 / 400 |
| Sidebar item, menu item | 13.5 / 1 / 400 |
| Meta, caption | 12 / 1.4 / 400 |
| Mono (URL, model, code) | 12–13.5 / 1.3–1.9 / 400 |
| Group header | 13 / 1.3 / 600, `.08em`, uppercase |
| Menu bar text | 13 / 1 / 400–600 |

**watchOS** (Ultra 2 49 mm — 410 × 502 device pixels, ≈ 2× so 24px ≈ 12 pt)

Absolute floor: **24 px**. Nothing smaller ships.

| Role | Size / line-height / weight |
|---|---|
| Clock (face) | 118 / 1 / 600, `-.04em` |
| Headline | 30–32 / 1.25–1.3 / 600 |
| Body | 26–30 / 1.35–1.4 / 400 |
| List row label | 24–26 / 1.2–1.35 / 400 |
| Primary button label | 26 / 1 / 600 |
| Meta, hint, mono | 24 / 1.2–1.35 / 400 |
| Eyebrow / status | 24 / 1 / 600, `.06em`–`.1em`, uppercase |
| Timer | 30 / 1 / 600, tabular |

### Spacing, radius, hit targets

- Spacing: 3 / 4 / 6 / 8 / 9 / 12 / 14 / 16 / 18 / 20 / 22 / 26 / 34 / 40 px.
  Always `gap` on a flex/grid parent, never per-child margins.
- Radius — iOS: card 14, button 12, pill 22, keyboard key 10, mic circle 50%.
  macOS: window 12, card 14, button/field 10, menu item 8, HUD 22, pill 22.
  watchOS: card 18–26, button 16, result card 22, screen 84.
- Hit targets: iOS ≥ 44 pt (list rows 64, buttons 52). macOS ≥ 28 pt (rows 52–64,
  buttons 38–40, menu items 36). watchOS ≥ 44 pt (rows 64–76, buttons 56–60).
- Toggles: iOS 50 × 30 with 24 knob; macOS 44 × 26 with 20 knob; watch 56 × 32
  with 24 knob. On = `accent` track, `onAccent` knob. Off = `raised` track,
  `textFaint` knob, `border` outline.

### Motion

| Name | Spec | Where |
|---|---|---|
| Mic glow | `scale(1) opacity .18` → `scale(1.9) opacity 0`, 2.4 s, `ease-out`, infinite; second ring delayed 1.2 s | Recording, all platforms |
| Glow (half) | Same, peak opacity `.09` | Alternate treatment — see Open decisions |
| Waveform bar | `scaleY(.28–.3)` ↔ `scaleY(1)`, 0.9 s, `ease-in-out`, infinite, staggered 60–90 ms per bar | Recording |
| Progress shimmer | 70–90 px lime block translating `-70%` → `240–280%`, 1.1 s, linear, infinite | Processing |
| Dot pulse | opacity `.25` ↔ `1`, 1.2 s, staggered 200 ms | Connection test |
| HUD present (macOS) | fade + 4 px rise, 120 ms, ease-out. **Must not take key focus** | Hold `fn` |
| Result dwell | Result/confirmation bar holds 4 s, then fades 200 ms | After insert |
| Haptics | Single tick on insert (iOS/macOS). Double tick on watch hand-off | — |

Respect Reduce Motion: replace glow and waveform animation with a static lime
ring and static bars; keep the timer as the live signal.

---

# iOS

`Dictly MVP.dc.html` · 24 artboards · iPhone 16 Pro, 402 × 874 pt, iOS 26

## Architecture

Two targets sharing a keychain access group and an app group:

1. **Container app** — setup, providers, models, dictionary, history, usage,
   privacy. Never needed during normal use.
2. **Keyboard extension** — the actual product surface. Requires
   `RequestsOpenAccess = true` (Full Access) to reach the network and the shared
   keychain.

API keys live in the Keychain with an access group shared by both targets. The
keyboard reads them; it never writes them.

## Screens

### 1.1 App lock — waiting / not recognised

Full-screen centred column, 58 pt top / 44 pt bottom padding, 20 pt sides.

- 88 × 88 rounded-square wordless logo, `border-radius: 24`, 3 pt border. Two
  7 pt dots (eyes), a 3 × 15 vertical bar and a 26 × 3 horizontal bar. Border and
  marks are `accent` when authenticating, `textFaint` when refused.
- Title "Dictly" 28/600. Body 16/1.5 `textDim`.
- Waiting copy: "Unlocked with Face ID."
- Refused copy: "Didn't recognise you. Try again or use your passcode." Adds a
  52 pt outlined `accent` button "Try Face ID again".
- Footer text button "Use passcode" 15/400 `textDim`.

### 1.2 Providers list

Title "Providers". Two single-row groups then a list.

- Group "Transcribe with" (eyebrow, `accent`) → card 64 pt: name 16/400, mono
  model 12 `textFaint`, latency pill, chevron. The *active* latency pill is
  `accent`/`onAccent`; the cleanup one is `raised`/`textDim`.
- Group "Clean up with" — same shape.
- Group "My providers" (header `textFaint`) → one card, rows divided by
  `hairline`. Each row: 7 pt status dot, name 16/400, mono base URL 11.5
  truncating with ellipsis, capability pill (`both` / `text` / `speech`).
  Dot is `accent` when the last check succeeded, `warn` when it failed.
  Sample rows: Groq (both), OpenAI (both), Cerebras (text), Deepgram (speech),
  Ollama — local (text, `warn`).
- Caption: "Tap a row to edit. Swipe to delete. Orange means it didn't answer
  the last check."
- Footer: 52 pt outlined `accent` "Add provider".

### 1.3 Add provider — pick a preset

Back chevron + "Step 1 of 2". Title "Pick a provider". Search field 42 pt
`raised`. Two-column grid, 9 pt gap, 60 pt tiles: name 15/600 + capability
11/400 `textFaint`. Presets: Groq, OpenAI, Cerebras, OpenRouter, Anthropic,
Google Gemini, Mistral, Together, Fireworks, DeepInfra, Deepgram, ElevenLabs,
Ollama — local, LM Studio — local, then a full-width dashed **Custom** tile
("any OpenAI-compatible endpoint"). Caption: "Picking one prefills its base URL
on the next screen."

### 1.4 Add provider — testing / connected / failed

"Step 2 of 2" + provider name as title. Fields, each label 13/600 uppercase
`textFaint` above a 50 pt `surface` field:

- Display name (text)
- Base URL (mono 13.5)
- API key (masked bullets + "Paste" affordance). Caption: "Stored in the
  Keychain, shared with the keyboard."

Then a card with two toggle rows: "Use for transcription", "Use for cleanup".

Bottom status region, three states:

| State | Banner | Button |
|---|---|---|
| Testing | `raised`, three pulsing dots, "Testing the connection…" | `accent` "Test connection" |
| Connected | `accentSoft`, lime check, "Connected · 8 models found · 190 ms" | outlined "Save provider" |
| Failed | `rgba(255,159,69,0.12)`, `warn` dot, "Couldn't reach that URL. Check the address and your key." Base URL field border turns `warn` | `accent` "Test connection" |

### 1.5 Model picker

Title "Transcription model", search "Search 8 models". One card, rows 64 pt:
mono model ID + measured latency ("210 ms measured" / "not measured yet"),
lime check on the selected row. Then a "Fallback" group — one row, value "None",
sublabel "Used if the first one fails or times out". Footer field: "Type a model
ID manually", placeholder `provider/model-name`.

### 1.6 Enable keyboard

Title "Turn on the keyboard", body "Two switches in iOS Settings. You'll only do
this once."

- Card "Step one · add it" — breadcrumb of `raised` chips separated by `›`:
  Settings › General › Keyboard › Keyboards › Add New Keyboard › Dictly.
- Card "Step two · allow full access" — a mock row with the toggle on, then a
  two-column split divided by a 1 px vertical rule:
  - "What this lets Dictly do" — "Send your voice to the provider you chose.
    Remember the words in your dictionary."
  - "What it never does" — "Read what you type on the normal keyboard. See
    passwords. Track you."
- Caption "Come back here when both switches are on. Dictly checks by itself."
  + `accent` "Open Settings".

### 1.7a Mic check (+ permission variant)

Centred: body "Say something — anything.", a full-width 14-bar animated meter
(`accentQuiet`→ live lime), then a lime check + "That's perfect." 20/600.
Caption: "Nothing is recorded here. This only checks the level."

Permission variant dims the screen to 40 % under `rgba(0,0,0,0.68)` and shows a
272 pt iOS alert: title "“Dictly” Would Like to Access the Microphone", body
"Your voice goes to the provider you picked, and nowhere else.", buttons
"Don't Allow" / "Allow" in `info`.

### 1.7b Privacy & lock

Card with rows: Require Face ID to open Dictly (on), Lock after → "Immediately",
Hide contents in app switcher (on), Require Face ID to show API keys (on).
Caption: "The keyboard never asks for Face ID — that would slow down every
dictation." Below, a "Lock after" option list: Immediately (checked), After 1
minute, After 5 minutes, After 15 minutes.

### 2.1–2.7 The keyboard

The keyboard tray is **392 pt tall** (including a 40 pt bottom safe area), sits
on a `hairline` top border, background `keyboardBg`, padding 12 / 20 / 40.

Top bar, 36 pt, three items:

- 44 × 36 `raised` key with a globe glyph (returns to the previous keyboard)
- centre pill showing the active language, e.g. "English (US)"
- 36 pt round `raised` key with a 5 pt dot — opens the expanded panel

Centre stack by state:

| State | Contents |
|---|---|
| **Idle** (2.1) | Hint "tap to talk" 12 `textFaint`; 88 pt lime mic circle; 7 bars at `accentQuiet` |
| **Recording** (2.2) | Timer `0:07` 12/400 tabular; 40 pt `raised` cancel (✕) pinned left; 88 pt lime circle with a 26 pt `onAccent` rounded square (stop); glow rings; 13 live lime bars |
| **Processing** (2.3) | 88 pt lime mic; 190 × 2 track with lime shimmer; "polishing…" |
| **Inserted** (2.4–2.6) | Above the keys, a 38 pt `raised` result bar: "↩ undo · see what you said". Host field now holds the cleaned text |
| **Panel** (2.7) | A 326 pt sheet rises over the tray — see below |

Expanded panel (2.7), `keyboardBg`, radius 14 top corners, shadow
`0 -18px 40px rgba(0,0,0,.5)`:

1. "LANGUAGE" + three pills (English selected, Svenska, Auto)
2. "TONE" + four equal 38 pt cells (Message selected, Work, Email, Note)
3. Row "Add “Kubernetes” to dictionary" / "From your last dictation" + `accent`
   "Add" pill
4. Row "Groq · Cerebras" / mono "whisper-large-v3-turbo → gpt-oss-120b" +
   outlined "Switch"

**Host-app tone shaping** — the same spoken input, three outputs:

- Messages: "Running about ten minutes late — start without me and I'll catch up."
- Slack: "yeah I'll take the API tickets — can we push standup to 10:30"
  (lowercase start, no trailing period)
- Mail: greeting line, paragraph break, body, "Thanks," + name

### 3.1 Provider failed mid-dictation

Replaces the keyboard tray, same 392 pt height.

- Eyebrow: `warn` dot + "NEEDS ATTENTION" 11/600 `.14em`
- Headline 22/1.25/600: "Groq didn't answer. Your audio is saved."
- Meta 14 `textDim` tabular: "0:09 of speech · 1.4 MB held on device"
- `accent` "Retry" (52 pt), outlined "Use fallback (OpenAI)" (52 pt), then a
  centred text button "Discard the recording" 12 `textFaint`

### 3.2 Settings root

Rows: Providers & models (mono sublabel "groq → cerebras"), Keyboard (lime dot,
"Enabled · Full Access on"), Dictation → "Tap", Languages → "English, Svenska",
Dictionary → "34 words", History, Privacy & lock, Usage → "$1.39".
Footer: "Dictly 1.0 · built for one phone. No account, nothing to cancel."

### 3.3 Usage

Title + "August 1–25 · estimated from published prices". Per-provider rows with
audio/token volume and cost. A "By week" card, four bars (w31–w34), current week
`accent`, rest `raised`. Total row: label 16 `textDim` + "$1.39" 28/600 tabular.
Caption: "Estimates, not invoices. Your provider's bill is the truth."

---

# macOS

`Dictly for macOS.dc.html` · 21 artboards · menu bar app, `LSUIElement`, no Dock icon

## Architecture

- `MenuBarExtra` owns the icon, the dropdown and app lifecycle.
- A borderless, non-activating floating panel (`NSPanel`,
  `.nonactivatingPanel`, `canBecomeKey = false`, level `.floating`) is the HUD.
  **It must never take key focus** — the caret has to stay exactly where it was.
- Hotkey via a `CGEvent` tap for the `fn` flag (needs Input Monitoring).
- Insertion via the Accessibility API (`AXUIElement`), one undoable edit so ⌘Z
  removes the whole insertion.
- Settings is a normal `Settings` scene with a sidebar.

Three permissions, requested in this order: Microphone, Accessibility, Input
Monitoring.

## The HUD

540 pt wide, `surface`, radius 22, 1 px `borderStrong`, shadow
`0 28px 80px rgba(0,0,0,.72)`, padding 14 / 18. Default position: bottom centre,
44 pt from the bottom edge. A single 56 pt row, contents by state:

| State | Row |
|---|---|
| Idle | 52 pt lime mic; "Just start talking." 15/400; hint "Hold fn · release when you're done" 12 `textFaint`; 5 resting bars |
| Recording | 52 pt lime stop circle + glow; 20 live lime bars filling the width; timer 14 tabular; 32 pt `raised` cancel |
| Processing | 52 pt lime mic; full-width 2 px track with lime shimmer; "polishing…" |
| Inserted | 52 pt lime mic; "Inserted at the cursor."; meta row "⌘Z undo · see what you said · 1.1 s" |
| Failed | 52 pt `rgba(255,159,69,.12)` circle with `warn` dot; "Groq didn't answer. Your audio is saved."; meta "0:09 of speech · held on this Mac"; `accent` "Retry" + outlined "Use fallback" |

Expanded HUD (1.7) adds a divider and, below it, Language pills, a five-cell
Tone row (Message / Work / Email / Note / **Code** — Code is macOS-only),
the dictionary-add row, and the provider/switch row.

Alternate positions offered in General: at the cursor, near the notch.

## Menu bar

28 pt translucent strip (`rgba(255,255,255,0.06)` over a blur). Status item:

- Idle — outline mic glyph, `rgba(241,243,236,0.75)`
- Recording — four live lime bars + lime timer, tabular
- Needs attention — 8 pt `warn` dot

Dropdown: 328 pt, `surface`, radius 14, 8 pt padding, items 36 pt, radius 8.
The primary item sits on `accentSoft` with `accent` text.

| Panel | Contents |
|---|---|
| Idle | "READY" + mono pipeline; **Start dictation** `fn`; Dictate to clipboard `⌥fn`; History…; Providers & models ›; Settings… `⌘,`; Quit Dictly `⌘Q` |
| Recording | 40 pt lime stop, 12 live bars, timer; "Listening. Take your time — release fn when you're done."; Stop and insert `fn`; Cancel `esc` |
| Result | "LAST DICTATION" + the cleaned text + "0:09 spoken · 1.1 s to clean · Groq → Cerebras"; **Insert at cursor** `⏎`; Copy `⌘C`; See what you said; Redo the cleanup |
| Failed | `warn` eyebrow + "Groq didn't answer. Your audio is saved."; **Retry with Groq**; Use fallback (OpenAI); Discard the recording |

## Boards 1.1–1.7 — dictation in place

Each board is a 1280 × 800 desktop (radial `#17181B` → `#0B0B0C`) with the menu
bar, a host window, and the HUD. Host apps: code editor (1.1), Slack (1.2),
Mail (1.3), Messages (1.4), Notes (1.5, centred HUD via double-tap ⌘),
Terminal (1.6, **quiet mode — no HUD at all**, menu bar only), Safari (1.7,
expanded HUD).

Quiet mode matters for screen sharing and demos: the only signal is the lime
waveform in the menu bar; text still lands at the cursor.

## Boards 2.5–2.6 — first run

**First launch** (760 × 520): 88 pt lime mic, "Just start talking." 28/600,
"I'll turn it into clean text, wherever your cursor is. Messy is fine.",
outlined "Set it up — about a minute", caption "Dictly has no Dock icon and no
windows of its own. It waits in the menu bar."

**Permissions** (760 × 660): title "Three permissions", body "macOS asks once
each. You'll never see this screen again." One card, three 78 pt rows — each a
status dot, name, one-line reason, and either "Granted" or an `accent` "Open
Settings" button:

- Microphone — "To hear you. Audio goes to the provider you chose, nowhere else."
- Accessibility — "To type the finished text where your cursor is."
- Input Monitoring — "To notice the fn key being held. Nothing else is read."

Then the same two-column does / never-does split as iOS 1.6, with the macOS
line: "Send anything to a Dictly server — there isn't one."

## Settings window (Row 3)

980 pt wide; height per pane (Providers 800, Models 660, General 800, Privacy
740, Usage 580, History 800, Dictionary 500). 226 pt sidebar on `#0A0A0A` with a
`border` divider; selected item `accentSoft` + `accent` text. Sidebar footer:
"Dictly 1.0 · runs on this Mac only. No account, nothing to cancel."

Panes: **Providers & models** (two role cards side by side + a five-row provider
table with URL and capability, "Add provider" / "Test all"); **Add provider
sheet** (560 pt, preset chips, name + URL + key, two role toggles, lime
connected banner, Cancel / Save provider); **Models** (latency-annotated list,
fallback selector, manual model ID); **General** (hotkey recorder showing
"hold fn" + Change, four radio triggers, panel position segmented control,
launch at login, sound on insert off by default); **Privacy & lock** (Touch ID
rows, keep-audio-after-failure, history retention, permission status list);
**Usage** (per-provider table + by-week chart + $3.18 total); **History**
(search + five rows with source app, duration and provider, Export as text /
Delete all in `danger`); **Dictionary** (add field + word chips + "34 words ·
shared with every provider you use").

Platform translations from iOS: Face ID → Touch ID, "↩ undo" → "⌘Z undo", a
**Code** tone added, and history/usage numbers roughly doubled — a Mac gets
dictated to far more than a phone.

---

# watchOS

`Dictly for watchOS.dc.html` · 13 artboards · Apple Watch Ultra 2, 49 mm

Screen: **410 × 502 device pixels**. All watch values in the HTML are device
pixels — divide by 2 for points (24 px ≈ 12 pt). Screen corner radius 84 px.

## Premise

There is no cursor on a wrist, so the watch **captures and hands off**. The
Action button starts and stops; the cleaned text goes to the paired Mac's
clipboard (or is copied locally), confirmed by a double haptic tick. No provider
editing, no API keys, no URLs — those live on the phone.

## Screens

| Board | Screen | Notes |
|---|---|---|
| 1.1 | Idle | 140 px lime mic, "Just start talking." 30/400, "Or press the Action button." |
| 1.2 | Recording | Timer 30/600 tabular, 132 px lime stop + glow, 10 live bars, "Take your time." |
| 1.3 | Processing | 132 px lime mic, 180 × 3 shimmer track, "polishing…" |
| 1.4 | Result | Cleaned text in a `surface` card 28/1.4; `accent` "Send to Mac" (60 px); "Copy" / "Again" (56 px) |
| 1.5 | Sent | 120 px `accentSoft` circle + lime check, "On your Mac." 32/600, "Paste it wherever you like.", "⌁ double haptic tick" |
| 1.6 | Provider failed | `warn` eyebrow, headline 30/1.3, "0:09 · waiting on your wrist", Retry / Use fallback |
| 1.7 | Off-grid | `info` eyebrow "ON DEVICE", "No phone, no Wi-Fi. Dictating on the watch.", "Rougher text, no cleanup step. I'll polish it when you're back in range.", `accent` "Record anyway" |
| 2.1 | Watch face | 118 px clock, Dictly complication as a 64 px lime-ringed circle |
| 2.2 | Action button | Explains press-once/press-again; card shows the orange hardware button swatch and "Assigned to Dictly" |
| 2.3 | Notification | Dictly card, "Sent to your Mac. It's on the clipboard.", mono "groq → cerebras", Dismiss / Again |
| 2.4 | Recent dictations | Two full cards + a "3 earlier" row |
| 2.5 | Tone | Crown-scrolled list: Message (selected, `accentSoft`), Note, Work, Email + "Turn the crown to scroll." |
| 2.6 | Settings | Provider row (read-only, mono model truncates), Tone, Send to Mac (on), Haptics (on), "Providers and keys are set up on your iPhone." |

Status header on most screens: eyebrow 24/600 uppercase `textFaint` on the left,
lime `9:41` 24/600 tabular on the right. The watch face and notification boards
have no header.

The Ultra's orange Action button is **hardware, not our accent** — the app never
borrows that orange for UI.

---

## Interactions & behaviour

### Dictation state machine

`idle → recording → processing → inserted → idle`, with
`processing → failed → (retry → processing | fallback → processing | discard → idle)`.

Triggers per platform: iOS mic tap (or hold, user preference); macOS `fn` hold,
menu bar click, double-tap ⌘, or quiet mode; watchOS Action button press /
press again.

### Pipeline

1. Capture audio locally while recording. Show a live level meter — never a fake
   animation; the bars must respond to real input.
2. On stop, POST the audio to the speech provider's transcription endpoint.
3. Send the raw transcript to the text provider with the tone instruction, the
   host-app hint, and the user's dictionary terms as do-not-change tokens.
4. Insert the cleaned text at the cursor as one undoable edit. Haptic tick.
5. Hold the result affordance 4 s, then fade.

### Failure

Any non-2xx, timeout (default 10 s) or network error moves to `failed`. **Never
discard the audio** — keep it until the user retries, falls back, or explicitly
discards. Show what was kept ("0:09 of speech · 1.4 MB held on device"). If a
fallback provider is configured, offer it by name.

Background health checks mark a provider `warn` when the last check failed; the
status dot and the menu bar icon reflect it.

### Copy voice

Short, plain, second person. Never apologise, never blame the user, never use
exclamation marks or emoji. State what happened and what to do next: "Groq
didn't answer. Your audio is saved." Lowercase for in-progress machine states
("polishing…", "tap to talk"). Sentence case everywhere else. Say what a
permission is *for*, in the user's terms, and always pair it with what the app
will never do.

## State

Per-device settings (shared iOS app ↔ keyboard via app group; macOS in
`UserDefaults`; watch synced from phone):

`providers[]` (id, name, baseURL, roles, lastCheckStatus, lastLatencyMs),
`speechProviderId` + `speechModelId` + `speechFallbackId`,
`textProviderId` + `textModelId`, `language`, `tone`, `dictionary[]`,
`history[]` (text, timestamp, sourceApp, durationMs, providerIds, retentionDays),
`usage[]` (providerId, audioSeconds, tokens, estimatedCost),
`lockRequired`, `lockAfter`, `hideInSwitcher`, `requireAuthForKeys`,
`keepAudioOnFailure`, `trigger`, `hudPosition`, `launchAtLogin`, `soundOnInsert`,
`hapticsEnabled`, `sendToMac`.

Keys are **never** in defaults — Keychain only, with a shared access group.

Transient: `dictationState`, `elapsedSeconds`, `audioLevel[]`, `pendingAudio`,
`lastResult`.

## Assets

None to hand off. Every glyph in the prototypes is inline SVG (mic, check, ✕,
chevron, globe, search) — replace with SF Symbols: `mic`, `mic.fill`,
`checkmark`, `xmark`, `chevron.right`, `globe`, `magnifyingglass`,
`waveform`, `stop.fill`.

The app icon is not designed. The lock screen uses a placeholder wordless mark
(88 pt rounded square, 3 pt lime border, two dots, two bars) — treat it as a
stand-in.

Fonts: Albert Sans (Google Fonts, SIL OFL). If you'd rather ship system fonts,
SF Pro is an acceptable substitute — keep the sizes and weights.

## Open decisions

1. **Glow intensity.** Recording glow is specced at 18 % peak. A 9 % variant was
   explored and is arguably better — the timer already carries the state.
   The tokens and keyframes support both; pick one and use it on all platforms.
2. **iOS trigger.** Tap-to-start vs hold-to-talk is a user preference in
   Settings → Dictation (default Tap). Hint copy switches with it.
3. **Watch ↔ Mac hand-off** assumes both are signed into the same iCloud
   account and reachable. Universal Clipboard is the simple path; a direct
   connection is more reliable but more work. Not decided.

## Screenshots

`screenshots/` holds one wide image per row, matching the sections below. They
are a quick reference only — the HTML sheets are the source of truth, and the
recording/processing boards are animated there.

| File | Contents |
|---|---|
| `ios-row1-setup-providers-lock.png` | iOS 1.1–1.7b, 12 boards |
| `ios-row2-keyboard.png` | iOS 2.1–2.7, 9 boards |
| `ios-row3-edge-cases-settings.png` | iOS 3.1–3.3, 3 boards |
| `macos-row1-dictation-in-place.png` | macOS 1.1–1.7, 7 boards (0.5×) |
| `macos-row2-menu-bar-first-run.png` | macOS 2.1–2.6, 6 boards (0.5×) |
| `macos-row3-settings-window.png` | macOS 3.1–3.8, 8 panes (0.5×) |
| `watchos-row1-dictate-from-wrist.png` | watchOS 1.1–1.7, 7 boards |
| `watchos-row2-getting-in-glances.png` | watchOS 2.1–2.6, 6 boards |

## Files in this bundle

Sheets (open any of these directly in a browser):

- `Dictly MVP.dc.html` — iOS, 24 artboards
- `Dictly for macOS.dc.html` — macOS, 21 artboards
- `Dictly for watchOS.dc.html` — watchOS, 13 artboards

Shared pieces used by the sheets:

- `DictlyKeyboard.dc.html` — iOS keyboard tray, all states
- `DictlyHost.dc.html` — iOS host apps (Messages / Slack / Gmail)
- `MacHud.dc.html` — macOS floating panel, all states
- `MacMenuBar.dc.html` — macOS menu bar + dropdown, all states
- `MacHostWindow.dc.html` — macOS host apps
- `MacSettingsWindow.dc.html` — macOS settings, all panes
- `WatchScreen.dc.html` — all 13 watch screens
- `ios-frame.jsx`, `macos-window.jsx` — device/window chrome (presentation only)
- `support.js` — runtime for the prototype format; not part of the design

Every colour, size and spacing value in those files is a literal inline value —
if this document doesn't cover something, read it there.
