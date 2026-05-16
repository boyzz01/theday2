# Netflix Template Design

**Date:** 2026-05-15
**Branch:** `netflix_template`
**Template key:** `netflix`

## Overview

A wedding invitation template themed after Netflix's streaming interface. The couple's wedding is presented as a Netflix Original documentary — dark cinematic aesthetic, bold red accents, and a multi-phase interaction flow that mimics the Netflix browsing and playback experience.

## Design References

- `design/netflix.md` — Netflix design system (colors, typography, components)
- Reference screenshots: "FIXNIKAH" concept (adapted — FIXNIKAH → THEDAY)

## User Flow

```
Who's Watching? → Netflix Intro (N anim + ta-dum) → Cover/Hero → [click ▶] → Content
```

Four distinct phases managed by a `phase` ref in `NetflixTemplate.vue`:
- `who-watching` → `intro` → `cover` → `content`

## File Structure

```
resources/js/Components/invitation/templates/
├── NetflixTemplate.vue          ← main orchestrator
└── netflix/
    ├── NetflixWhoWatching.vue   ← phase 0
    ├── NetflixIntro.vue         ← phase 1
    ├── NetflixCover.vue         ← phase 2
    └── NetflixHero.vue          ← phase 3 (first content section)
```

Registry entry in `registry.js`:
```js
'netflix': NetflixTemplate
```

## Design Tokens

Sourced from `design/netflix.md`:

| Token | Value |
|-------|-------|
| Background | `#141414` |
| Surface | `#1F1F1F` |
| Surface Elevated | `#2D2D2D` |
| Brand Red | `#E50914` |
| Red Dark (hover) | `#831010` |
| Text Primary | `#FFFFFF` |
| Text Secondary | `#BCBCBC` |
| Match Score Green | `#46D369` |
| Font | Netflix Sans, Arial, Helvetica, sans-serif |

## Phase Details

### Phase 0 — `NetflixWhoWatching.vue`

- Full-screen black background (`#141414`)
- **THEDAY** logo: Netflix-style bold red wordmark, centered top area
- "Who's watching?" — white text, centered
- Red avatar square (rounded corners) with smiley icon
- Guest name: read from URL param `?to=`, fallback "Tamu Undangan" — displayed in red below avatar
- "OPEN INVITATION" button: white border, white text, uppercase tracking
- Click → transition to phase `intro`

### Phase 1 — `NetflixIntro.vue`

- Full-screen black
- CSS animation: red horizontal bar expands → morphs into **N** letterform (~2s total)
- Sound: ta-dum effect synthesized via Web Audio API (no external file, no copyright issue)
- On animation end → auto-advance to phase `cover`
- No user interaction required

### Phase 2 — `NetflixCover.vue`

- Full-bleed cover photo (`cover_photo_url`)
- Gradient overlay: `linear-gradient(to top, #141414, transparent)`
- Top-left: **THEDAY** red wordmark (small)
- Top-right: floating music toggle button + QR code button (both red circle)
- Bottom content block:
  - Series title: `"GroomNick & BrideNick: [subtitle]"` — subtitle from `config.netflix_subtitle` or default "Sebuah Kisah Cinta"
  - Badge: red pill "Coming Soon" + event date (first event)
  - Genre hashtags: from `config.netflix_tags` array (e.g. `#romantic #lovestory`)
- CTA: `▶ Buka Undangan` — white text, no background, large — click → phase `content`

### Phase 3 — `NetflixHero.vue` (first content section)

- Top half: cinematic photo (cover photo, darkened + slight blur)
- Subtitle-style text overlay: from `config.netflix_hero_quote`, fallback to `quote.text`, fallback hidden
- Below fold:
  - Label: **N DOCUMENTER** (N in red, DOCUMENTER in white spaced caps)
  - Title: `"GroomName & BrideName: [subtitle]"`
  - Metadata row: `100% match` (green) · `SU` badge · year · `4K` `HD` badges
  - Red pill button: "Coming soon on [day], [full date]"
  - Synopsis: `opening` section text
  - Quote/verse: `quote` section text (italic, muted)

## Content Sections (inline in `NetflixTemplate.vue`)

All sections: bg `#141414`, text white, red `#E50914` accents. Section headers: bold white uppercase, large.

### Breaking News — Opening/Sambutan
- Header: "BREAKING NEWS"
- Full-width couple photo (rounded `6px`)
- Opening letter text (`opening.text`)

### Bride & Groom — Couple
- Header: "BRIDE & GROOM"
- Two portrait photos side-by-side (rounded)
- Full name + parent names below each photo
- Combined couple photo full-width below

### Timeline & Location — Events
- Header: "TIMELINE & LOCATION"
- Per event card:
  - Thumbnail: cover photo (reused — events have no individual photo in data model)
  - Red badge pill: event name (Akad Nikah / Resepsi)
  - Date bold white, time chip (gray pill) + timezone chip
  - Address text (muted)
  - "Buka Google Maps >>" link in red
- Full-width red CTA: **KONFIRMASI KEHADIRAN** → smooth-scroll to RSVP section

### Countdown
- Large monospace numbers: DD · HH · MM · SS
- Labels below each: Hari / Jam / Menit / Detik (muted)
- Hidden when `targetDate` is past

### Our Love Story — Love Story
- Header: "OUR LOVE STORY"
- Per story item:
  - Thumbnail left (rounded), metadata right
  - "Episode N: [title]" bold white
  - Year — muted
  - Description text below (full width)
- Data from `love_story.stories` array

### Gallery
- 2-column grid, equal-width, gap `8px`
- Images rounded `4px`, `object-fit: cover`
- Tap → fullscreen lightbox (simple overlay)

### RSVP
- Header: "KONFIRMASI KEHADIRAN"
- Form fields: nama lengkap, hadir/tidak (select), jumlah tamu (number), catatan (textarea)
- All inputs: bg `#333333`, border `#8C8C8C`, white text, focus border white
- Submit: full-width red button "KIRIM KONFIRMASI"
- Success/error states inline

### Gift / Rekening
- Header: "AMPLOP DIGITAL"
- Per account: bank name, account holder, account number
- "SALIN NOMOR" button → copy to clipboard → toast confirm

### Wishes / Buku Tamu
- Header: "UCAPAN & DOA"
- Form: nama, pesan (textarea), submit red button
- Wishes list below: name bold + message text + timestamp muted

### Closing
- Header: couple names `"GroomName & BrideName"`
- Closing text (`closing.text`)
- **THEDAY** wordmark small, centered, muted — bottom of page

## Music & Floating Buttons

- Music toggle: fixed `bottom: 16px, right: 16px`, red circle, ♪ icon
- QR code: fixed `bottom: 72px, right: 16px` (above music button), opens modal with invitation QR
- Both visible throughout `content` phase

## Branding

- **THEDAY** replaces "FIXNIKAH" from reference design
- Wordmark: bold, red (`#E50914`), uppercase, Netflix-style wide letterform
- Appears in: WhoWatching (large), Cover (small top-left), Closing (small muted)

## Config Fields (new/netflix-specific)

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `config.netflix_subtitle` | string | "Sebuah Kisah Cinta" | Series subtitle after couple names |
| `config.netflix_tags` | string[] | `["#lovestory","#romantic"]` | Genre hashtags on cover (max 4) |
| `config.netflix_hero_quote` | string | — | Subtitle overlay text on NetflixHero photo |

All other data uses existing `useInvitationTemplate` composable fields.

## Implementation Notes

- Uses `useInvitationTemplate` composable (same as all templates)
- `galleryLayout: 'grid'`, `openingStyle: 'fade'`, `revealClass: 'nf-visible'`
- Font loaded via Google Fonts is not needed — Netflix Sans fallback stack (Arial) is sufficient
- Web Audio API for ta-dum: synthesize two oscillator notes (D3 → B2, ~80ms each with reverb)
- No external audio file required
- All 4 sub-components receive only the props they need — no prop drilling of full invitation object except where necessary
