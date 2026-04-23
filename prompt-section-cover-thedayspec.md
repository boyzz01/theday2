# Prompt — TheDay Section Cover Spec

You are implementing the **Cover** section for TheDay invitation editor.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Invitations are usually opened from WhatsApp on mobile devices.
- The Cover section is the very first screen seen by guests before they enter the main invitation content.
- This section was previously called "buka undangan" and is now renamed to `cover`.
- The old `cover` concept has been moved into another section called `konten_utama`.
- The cover must feel elegant, warm, premium, and focused on first impression.
- The design should align with TheDay brand qualities: elegant, warm, modern, approachable.
- The cover must work especially well on mobile portrait view.

Your task is to design and implement the **Cover section editor + render behavior + data contract**.

---

## Main Goal
Build a section named `cover` that controls the opening screen of the invitation.

This section must allow users to edit:
- opening pre-title
- couple names
- short event date text
- guest recipient personalization
- short intro text
- cover background image
- CTA button text
- basic visual style settings
- music behavior on open

This section must be editor-friendly, mobile-friendly, and safe against incomplete data.

---

## Product Meaning of Cover
The `cover` section is the opening gate before the invitation content is shown.
It is not the same as the full invitation body.

Expected guest experience:
1. Guest opens invitation link.
2. Guest sees a beautiful opening screen.
3. Guest sees who the invitation is for and who the couple is.
4. Guest optionally sees date and a short welcome line.
5. Guest taps the main CTA button, for example `Buka Undangan`.
6. Invitation proceeds into the main content area.

The cover is therefore both:
- a visual hero section
- an interaction gate into the full invitation

---

## Required Editable Fields
Implement the following editable fields for section `cover`.

### A. Content Fields
- `pretitle`
  - type: string
  - example: `The Wedding Of`
  - optional
  - max recommended length: 50 chars

- `couple_names`
  - type: string
  - example: `Ahmad & Siti`
  - required
  - max recommended length: 80 chars

- `event_date_text`
  - type: string
  - example: `12 September 2026`
  - optional
  - max recommended length: 60 chars

- `intro_text`
  - type: string
  - example: `Kepada Bapak/Ibu/Saudara/i`
  - optional
  - max recommended length: 100 chars

- `button_text`
  - type: string
  - default: `Buka Undangan`
  - required
  - max recommended length: 30 chars

### B. Guest Personalization Fields
- `guest_name_mode`
  - type: enum
  - allowed: `manual`, `query_param`, `none`
  - default: `query_param`

- `guest_name`
  - type: string nullable
  - used when mode is `manual`
  - example: `Bapak Andi & Keluarga`
  - max recommended length: 120 chars

- `guest_query_key`
  - type: string
  - default: `to`
  - used when mode is `query_param`

- `fallback_guest_text`
  - type: string
  - default: `Tamu Undangan`
  - shown if guest name is missing or disabled
  - max recommended length: 80 chars

- `show_guest_name`
  - type: boolean
  - default: true

### C. Background Fields
- `background_image`
  - type: object nullable
  - shape:
    - `asset_id`: uuid nullable
    - `url`: string nullable
  - primary hero image for cover

- `background_mobile_image`
  - type: object nullable
  - optional image optimized for mobile
  - same shape as `background_image`

- `background_position`
  - type: enum
  - allowed: `center`, `top`, `bottom`
  - default: `center`

- `background_size`
  - type: enum
  - allowed: `cover`, `contain`
  - default: `cover`

### D. Visual Style Fields
- `text_align`
  - type: enum
  - allowed: `left`, `center`, `right`
  - default: `center`

- `content_position`
  - type: enum
  - allowed: `top`, `center`, `bottom`
  - default: `center`

- `overlay_opacity`
  - type: number
  - range: 0 to 1
  - default: 0.35

- `show_ornament`
  - type: boolean
  - default: true

- `show_date`
  - type: boolean
  - default: true

- `show_pretitle`
  - type: boolean
  - default: true

### E. Music / Open Behavior Fields
- `music_on_open`
  - type: boolean
  - default: true
  - means the player may start or prepare to start after user enters invitation content

- `show_music_button`
  - type: boolean
  - default: false
  - only if template supports music control on cover

- `open_action`
  - type: enum
  - allowed: `enter_content`, `scroll_to_next`
  - default: `enter_content`

---

## Recommended JSON Shape
Store this section in `invitation_sections.data_json` with a shape similar to:

```json
{
  "pretitle": "The Wedding Of",
  "couple_names": "Ahmad & Siti",
  "event_date_text": "12 September 2026",
  "intro_text": "Kepada Bapak/Ibu/Saudara/i",
  "button_text": "Buka Undangan",
  "guest_name_mode": "query_param",
  "guest_name": null,
  "guest_query_key": "to",
  "fallback_guest_text": "Tamu Undangan",
  "show_guest_name": true,
  "background_image": {
    "asset_id": "uuid",
    "url": "https://..."
  },
  "background_mobile_image": null,
  "background_position": "center",
  "background_size": "cover",
  "text_align": "center",
  "content_position": "center",
  "overlay_opacity": 0.35,
  "show_ornament": true,
  "show_date": true,
  "show_pretitle": true,
  "music_on_open": true,
  "show_music_button": false,
  "open_action": "enter_content"
}
```

---

## Validation Rules
Implement validation at section level.

### Required Rules
- `couple_names` is required.
- `button_text` is required.
- `guest_name_mode` must be one of the allowed enum values.
- `background_position` must be valid.
- `background_size` must be valid.
- `text_align` must be valid.
- `content_position` must be valid.
- `overlay_opacity` must be between 0 and 1.
- `open_action` must be valid.

### Conditional Rules
- If `guest_name_mode = manual`, then `guest_name` may be nullable during draft but should produce warning if `show_guest_name = true` and value is empty.
- If `guest_name_mode = query_param`, then `guest_query_key` must not be empty.
- If `show_date = true` and `event_date_text` is empty, mark section as `warning`, not `error`.
- If `show_pretitle = true` and `pretitle` is empty, mark section as `warning`, not `error`.
- If `background_image` is empty, section may still be renderable using template fallback background.

### Completion Logic
Suggested completion logic:
- `empty`: couple_names empty and button_text empty and no other meaningful data.
- `incomplete`: couple_names exists but button_text missing, or invalid enum values exist.
- `warning`: required core fields valid, but optional visible fields like date or pretitle are toggled on and still empty.
- `complete`: at least `couple_names` and `button_text` are valid, and all configured fields are structurally valid.
- `error`: corrupted payload, invalid enum, impossible value, or save/deserialize failure.

---

## Render Rules
The frontend renderer for `cover` must:
- render full-screen or near full-screen opening view
- prioritize mobile portrait layout
- display background image with overlay when provided
- show text content in readable contrast
- render the CTA button prominently
- handle guest name personalization safely
- support template ornament if enabled
- not break when optional fields are empty

### Display Priority
Preferred visual hierarchy:
1. pretitle
2. couple names
3. event date text
4. intro text
5. guest name
6. CTA button

This hierarchy may vary per template, but couple names and CTA must remain visually strong.

### Guest Name Rendering Logic
Implement this logic:
- if `show_guest_name = false`, do not render guest name block
- else if `guest_name_mode = manual` and `guest_name` exists, show `guest_name`
- else if `guest_name_mode = query_param`, read query param from URL using `guest_query_key`
- if query param missing or blank, use `fallback_guest_text`
- else if `guest_name_mode = none`, show `fallback_guest_text` only if template requires recipient block, otherwise hide

Trim and sanitize rendered guest name.
Do not show raw unsafe HTML.

### CTA Behavior
When user clicks main button:
- if `open_action = enter_content`, unlock and reveal main invitation content
- if `open_action = scroll_to_next`, smoothly scroll to next section
- trigger or prepare music according to browser autoplay limitations
- do not require full page reload

### Music Behavior
- `music_on_open = true` means attempt to start or arm music playback only after user interaction
- never assume autoplay without interaction will succeed on mobile browsers
- if no music asset exists globally, ignore music trigger gracefully
- if `show_music_button = true`, only render control if template supports it

---

## Editor UI Requirements
The editor form for this section should group fields into clear panels.

### Group 1: Main Content
- pretitle
- couple_names
- event_date_text
- intro_text
- button_text

### Group 2: Guest Personalization
- show_guest_name
- guest_name_mode
- guest_name
- guest_query_key
- fallback_guest_text

### Group 3: Background
- background_image
- background_mobile_image
- background_position
- background_size
- overlay_opacity

### Group 4: Appearance
- text_align
- content_position
- show_ornament
- show_date
- show_pretitle

### Group 5: Interaction
- open_action
- music_on_open
- show_music_button

Editor UX rules:
- hide irrelevant fields conditionally, example hide `guest_name` unless mode is `manual`
- show helper text for `guest_query_key`, example `Use URL param like ?to=Bapak+Andi`
- show live preview updates instantly
- keep labels in Indonesian if the product UI is Indonesian

---

## Responsive Rules
### Mobile
- optimize text wrapping for long couple names
- CTA button must remain visible without awkward overflow
- guest block should not dominate vertical space
- overlay should maintain readability even on bright photos

### Tablet/Desktop
- preserve elegance and negative space
- allow background image composition to breathe
- support left/center/right align where template allows it

---

## Edge Cases
Handle these carefully:

1. `couple_names` too long
- allow wrap to 2 lines
- avoid layout break
- truncate only as last resort in preview thumbnails, not main render

2. guest name too long
- wrap gracefully
- prevent overlap with button

3. no background image uploaded
- use template fallback background or solid/gradient background

4. mobile-specific image missing
- fallback to desktop background image

5. query param contains URL encoding or plus signs
- decode safely before rendering

6. query param exists but contains only spaces
- treat as empty and use fallback

7. user disables `show_date`
- do not reserve blank space for date block

8. user disables `show_pretitle`
- do not reserve blank space for pretitle block

9. `overlay_opacity` is 0 and background image is bright
- allow it, but preview should still reflect readability risk naturally

10. `button_text` too long
- support max width and line wrap or enforce validation warning

11. no guest name mode selected due to stale data
- fallback to `query_param`

12. unsupported `open_action`
- fallback to `enter_content`

13. corrupted image object
- ignore image and use fallback background

14. music enabled but no music configured globally
- no runtime error, just no playback

15. old invitations still use legacy field names from "buka undangan"
- provide mapping fallback if migration layer exists

---

## Suggested Default Values
Use these defaults for new invitations:

```json
{
  "pretitle": "The Wedding Of",
  "couple_names": "",
  "event_date_text": "",
  "intro_text": "Kepada Bapak/Ibu/Saudara/i",
  "button_text": "Buka Undangan",
  "guest_name_mode": "query_param",
  "guest_name": null,
  "guest_query_key": "to",
  "fallback_guest_text": "Tamu Undangan",
  "show_guest_name": true,
  "background_image": null,
  "background_mobile_image": null,
  "background_position": "center",
  "background_size": "cover",
  "text_align": "center",
  "content_position": "center",
  "overlay_opacity": 0.35,
  "show_ornament": true,
  "show_date": true,
  "show_pretitle": true,
  "music_on_open": true,
  "show_music_button": false,
  "open_action": "enter_content"
}
```

---

## Engineering Requirements
Implement the feature in a way that includes:
- backend validation rules for the cover payload
- editor form schema
- frontend renderer logic
- fallback handling for missing optional values
- safe query param personalization logic
- section completion status computation
- migration compatibility if old `buka_undangan` payload exists

Use clean separation between:
- stored section data
- derived render data
- editor-only UI state

Do not store temporary editor UI state inside the persisted payload.

---

## Deliverables
Produce:
1. section field schema
2. validation rules
3. default payload
4. completion status logic
5. Vue editor form structure
6. frontend render rules
7. fallback logic for guest personalization and missing background
8. migration mapping from legacy `buka_undangan` naming if needed

The implementation should be robust, mobile-first, and aligned with TheDay’s premium invitation experience.
