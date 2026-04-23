# Section Customization System — Design Spec

> **For agentic workers:** This spec covers two sequential implementation plans. Implement Plan 1 (Kustomisasi Page) first, then Plan 2 (Wizard Simplification) after Plan 1 is complete and tested.

**Goal:** Allow users (premium) to customize each invitation section's background and media independently, via a dedicated Kustomisasi page — while simplifying the wizard to data-only entry.

**Architecture:** Two-phase. Phase 1 builds the Kustomisasi page as an additive feature (wizard unchanged). Phase 2 removes media/visual steps from the wizard and redirects users to the Kustomisasi page.

**Tech Stack:** Laravel 10, Vue 3 (Inertia.js), Tailwind CSS, existing file storage (Laravel Storage)

---

## Background & Context

TheDay is a wedding invitation SaaS (Laravel + Vue 3 + Inertia). Invitations are rendered by template Vue components (e.g. `NusantaraTemplate.vue`, `PearlTemplate.vue`) that read data from an `invitation` object with this shape:

```json
{
  "config":    { "primary_color": "#...", "font_title": "..." },
  "details":   { "groom_name": "...", "bride_name": "...", "cover_photo_url": "..." },
  "events":    [ { "event_name": "...", "event_date": "...", "location": "..." } ],
  "galleries": [ { "photo_url": "..." } ],
  "music":     { "file_url": "..." },
  "sections":  { "cover": { "enabled": true, "data": {} } }
}
```

Templates use the `useInvitationTemplate(props, defaults)` composable which exposes `sectionData(key)` — returns `invitation.sections[key].data ?? {}`.

---

## Data Model

### Section Background Structure

Stored in `invitation.sections[key].data.background`:

```json
{
  "type":    "image | video | color",
  "value":   "/storage/sections/cover/abc123.jpg | https://youtube.com/... | #1a1a2e",
  "opacity": 0.7
}
```

- `type: "image"` — `value` is a storage path served via `/storage/...`
- `type: "video"` — `value` is a YouTube URL; template converts to embed URL
- `type: "color"` — `value` is a hex color string; `opacity` unused
- `opacity` — float 0–1, default `0.7`, only applies to image/video

### Per-Section Extra Data

```json
"sections": {
  "cover":   { "enabled": true, "data": { "background": { "type": "image", "value": "...", "opacity": 0.7 } } },
  "opening": { "enabled": true, "data": { "background": { "type": "color", "value": "#0f0f0f" }, "opening_text": "..." } },
  "events":  { "enabled": true, "data": { "background": { "type": "color", "value": "#fff" }, "maps_url": "https://maps.google.com/..." } },
  "gallery": { "enabled": true, "data": { "background": { "type": "color", "value": "#f5f0e8" } } },
  "music":   { "enabled": true, "data": {} },
  "closing": { "enabled": true, "data": { "background": { "type": "image", "value": "...", "opacity": 0.6 }, "closing_text": "..." } }
}
```

Gallery photos remain in `invitation.galleries` (existing array). Music file remains in `invitation.music.file_url` (existing). The `sections` column already exists as JSONB — no migration needed.

### Template Defaults

Each template component defines its own section background defaults as a constant:

```js
// In PearlTemplate.vue, NusantaraTemplate.vue, etc.
const SECTION_BG_DEFAULTS = {
  cover:   { type: 'image',  value: '/image/demo-image/bride-groom.png', opacity: 0.75 },
  opening: { type: 'color',  value: '#0f0f0f' },
  events:  { type: 'color',  value: '#ffffff' },
  gallery: { type: 'color',  value: '#f5f0e8' },
  closing: { type: 'image',  value: '/image/demo-image/bride-groom.png', opacity: 0.6 },
}
```

### Composable Extension

Add `sectionBg(key)` to `useInvitationTemplate`:

```js
// Resolves: user override → template default → null
function sectionBg(key) {
  const userBg = sectionData(key).background
  if (userBg?.type && userBg?.value) return userBg
  return defaults.sectionBgDefaults?.[key] ?? null
}
```

Templates use `sectionBg('cover')` to render backgrounds. Returns `null` if no background set — template renders without background.

---

## Plan 1: Kustomisasi Page

### Route & Controller

```
GET  /dashboard/invitations/{invitation}/customize   → show Kustomisasi page
POST /dashboard/invitations/{invitation}/customize   → save section data
POST /dashboard/invitations/{invitation}/sections/{key}/background  → upload background image
```

Premium middleware on all routes: check `$user->isPremium()` (existing method on User model). Free users get redirect to `/dashboard/paket` with flash message.

Controller: `InvitationCustomizeController`
- `show()` — return Inertia page with invitation + template sections list
- `update()` — validate + merge section data into `invitation.sections` JSONB, save
- `uploadBackground()` — store file to `sections/{invitation_id}/{key}/`, update section data

### Frontend: `Pages/Dashboard/Invitations/Customize.vue`

Layout: same dashboard layout as other pages.

Structure:
- Header: "Kustomisasi Undangan — {groom} & {bride}" + "Lihat Preview" button (opens `/undangan/{slug}` in new tab)
- Section panels: one expandable card per section, ordered as they appear in the invitation
- Footer: "Simpan Semua" button + auto-save indicator

**Section panels:**

| Section | Controls |
|---------|----------|
| Cover | Background type selector + upload/URL/color input + opacity slider |
| Opening | Background type selector + teks pembuka textarea |
| Events | Background type selector + Google Maps URL input per event |
| Gallery | Background type selector + multi-photo upload (drag & drop) |
| Music | Audio file upload (MP3/OGG, max 10MB) |
| Closing | Background type selector + teks penutup textarea |

Background type selector = 3 radio buttons: Foto | Video | Warna

Photo upload: direct upload → `POST .../background`, replace existing, show thumbnail preview.

Video: input URL field (YouTube only), show embed preview.

Color: color picker input (`<input type="color">` + hex text field).

Opacity: range slider `0.1–1.0`, shown only for image/video type.

Sections that are disabled (`enabled: false`) are shown grayed out with a toggle to re-enable.

### Premium Gate

Check `auth()->user()->subscription->is_active && auth()->user()->subscription->plan === 'premium'` (use existing subscription logic).

In Vue: if `canUsePremium` prop is false (passed from controller via Inertia), show lock overlay on the page with upgrade CTA instead of the controls.

### Composable Update

Add to `useInvitationTemplate` return:
- `sectionBg(key)` — resolved background for a section

Add `sectionBgDefaults` to the `defaults` parameter:
```js
useInvitationTemplate(props, {
  galleryLayout: 'grid',
  sectionBgDefaults: SECTION_BG_DEFAULTS,
})
```

### Template Updates (Pearl + Nusantara)

Each template that supports section backgrounds:
1. Define `SECTION_BG_DEFAULTS` constant
2. Pass `sectionBgDefaults` to composable
3. Use `sectionBg('cover')` to render background in each section

Background rendering helper (inline in template or shared util):
```js
function bgStyle(bg) {
  if (!bg) return {}
  if (bg.type === 'color') return { backgroundColor: bg.value }
  if (bg.type === 'image') return {
    backgroundImage: `url(${bg.value})`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    opacity: bg.opacity ?? 0.7,
  }
  // video: handled separately via <iframe> embed
  return {}
}
```

---

## Plan 2: Wizard Simplification

### Goal

Reduce wizard to 3 steps (data-only). Remove Gallery, Music, and visual customization steps from wizard flow. After wizard completion, redirect to Kustomisasi page.

### New Wizard Steps

**Step 1 — Informasi Pasangan:**
- Nama lengkap pengantin pria + nama panggilan
- Nama lengkap pengantin wanita + nama panggilan
- Nama orang tua (ayah & ibu masing-masing)
- Foto pasangan (cover photo) — kept here since it's identity data, not decoration

**Step 2 — Detail Acara:**
- Per event: nama acara, tanggal, waktu mulai & selesai, nama venue, alamat lengkap
- Add/remove multiple events

**Step 3 — Publikasi:**
- Slug (URL undangan)
- Pilih template
- Preview link
- Publish toggle

### Removed from Wizard

- Gallery upload → moved to Kustomisasi page (Gallery section)
- Music upload → moved to Kustomisasi page (Music section)
- Appearance/theme (colors, fonts) → moved to Kustomisasi page or kept in Step 3 minimally
- RSVP settings → moved to Kustomisasi page (RSVP section panel)

### Post-Wizard Redirect

After wizard completion (Step 3 publish):
- Redirect to `/dashboard/invitations/{id}/customize`
- Show banner: *"Undangan berhasil dibuat! Lengkapi tampilan & media undanganmu di bawah ini."*

### Existing Wizard Files

The active wizard (`Create.vue`) uses these 6 steps:
- `StepInformasi.vue` → **keep** — nama pengantin, orang tua, foto pasangan
- `StepAcara.vue` → **keep** — tanggal, waktu, venue, alamat
- `StepMedia.vue` → **remove** — gallery & music moved to Kustomisasi page
- `StepInteraksi.vue` → **remove** — RSVP settings moved to Kustomisasi page
- `StepTampilan.vue` → **remove** — appearance/theme moved to Kustomisasi page
- `StepPublikasi.vue` → **keep** — slug, template picker, publish

Result: wizard goes from 6 steps → 3 steps (Informasi → Acara → Publikasi).

Files `Step1BasicInfo.vue` through `Step6Review.vue` are unused — leave them as-is.

---

## Acceptance Criteria

### Plan 1
- [ ] Premium users can access `/dashboard/invitations/{id}/customize`
- [ ] Free users see locked/upgrade page at that route
- [ ] All 6 section panels render with correct controls per section type
- [ ] Background image upload works, file stored in `storage/sections/{id}/{key}/`
- [ ] YouTube URL input shows embed preview
- [ ] Color picker updates preview correctly
- [ ] Opacity slider applies to image/video backgrounds
- [ ] Google Maps URL saved and rendered in Events section
- [ ] Gallery photos upload and appear in invitation
- [ ] Music file upload works, plays in invitation
- [ ] Save persists all changes, page reload shows correct state
- [ ] Template composable `sectionBg(key)` resolves user override → template default → null
- [ ] Pearl template uses `sectionBg()` for cover, opening, events, gallery, closing sections
- [ ] Nusantara template uses `sectionBg()` for applicable sections

### Plan 2
- [ ] Wizard has exactly 3 steps: Informasi, Acara, Publikasi
- [ ] Gallery, Music, Appearance steps removed from wizard flow
- [ ] After wizard completion → redirect to Kustomisasi page with success banner
- [ ] All existing invitations created before simplification still work correctly
- [ ] No data loss for invitations that had gallery/music set via old wizard
