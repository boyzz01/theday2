# Storybook Content Editor — Design Spec

**Date:** 2026-04-25
**Status:** Approved

---

## Background

The Storybook customize page (`/dashboard/invitations/{id}/customize`) currently only shows a gallery layout picker and music section. All content for Storybook sections (gallery photos, love story, events, couple profile, RSVP, gift) must be edited via the separate Edit page.

This spec adds inline content editing for all Storybook hotspot sections directly from the customize page — keeping users in one place while building their invitation.

---

## Goal

Allow users with Storybook templates to view and edit all section content from the customize page, without navigating to the separate Edit page.

---

## Scope

**In scope:**
- Gallery photos (upload, delete, order)
- Love Story chapters (add, edit, delete — each with date, title, description, optional photo)
- Date & Venue / Events (edit date, time, venue name, maps link per event)
- Tentang Kami / Couple profile (names, bio, photos for each partner)
- RSVP (enable / disable toggle — inline, no modal)
- Hadiah / Gift (add, delete bank accounts: bank name, account number, account name)

**Out of scope:**
- Editing content for regular (non-Storybook) templates — unchanged
- Background/visual customization for Storybook — no section backgrounds
- Guestbook messages management
- Music upload (already links to Edit page)

---

## Design Decision

**Pattern: Accordion entry points + ContentModal**

Each section in `SECTIONS_STORYBOOK` shows a summary badge (e.g., "4 foto", "3 chapters") and an "Edit →" button. Clicking opens a `ContentModal` — full-screen on mobile, centered overlay on desktop — with the section's full editor.

RSVP is the exception: it's a simple toggle rendered inline in the accordion, no modal needed.

This avoids scroll-within-scroll UX issues on mobile and gives each content type a focused editing surface.

---

## Sections

### SECTIONS_STORYBOOK (expanded)

```js
const SECTIONS_STORYBOOK = [
    { key: 'gallery',    label: 'Galeri',       icon: '🖼️' },
    { key: 'events',     label: 'Date & Venue',  icon: '📅' },
    { key: 'love_story', label: 'Love Story',    icon: '📖' },
    { key: 'couple',     label: 'Tentang Kami',  icon: '💑' },
    { key: 'rsvp',       label: 'RSVP',          icon: '✅' },
    { key: 'gift',       label: 'Hadiah',        icon: '🎁' },
    { key: 'music',      label: 'Musik',         icon: '🎵' },
]
```

---

## Component Architecture

### New components

| File | Responsibility |
|---|---|
| `resources/js/Components/invitation/customize/ContentModal.vue` | Reusable modal wrapper — full-screen mobile, centered desktop. Props: `open` Boolean, `title` String. Emits: `close`. Slot: default content. |
| `resources/js/Components/invitation/customize/SectionGalleryPhotos.vue` | Photo grid: upload new, delete existing, reorder with ↑↓ buttons. Emits: `change` |
| `resources/js/Components/invitation/customize/SectionLoveStoryEditor.vue` | List of story chapters. Each: date, title, description, optional photo. Add/edit/delete. Emits: `change` |
| `resources/js/Components/invitation/customize/SectionEventsEditor.vue` | List of events. Each: name, date, time, venue, maps URL. Edit only (no add/delete — events fixed by template). Emits: `change` |
| `resources/js/Components/invitation/customize/SectionCoupleEditor.vue` | Two profiles (groom + bride): name, nickname, bio, photo. Emits: `change` |
| `resources/js/Components/invitation/customize/SectionGiftEditor.vue` | List of bank accounts. Each: bank name, account number, account name. Add/delete. Emits: `change` |

### Modified files

| File | Change |
|---|---|
| `app/Http/Controllers/Dashboard/InvitationCustomizeController.php` | `show()`: load `sections` (love_story, couple, gift, rsvp) and pass to page for Storybook |
| `resources/js/Pages/Dashboard/Invitations/Customize.vue` | Expand `SECTIONS_STORYBOOK`, add modal state management, import new section components |

---

## Data Flow

### Loading

`InvitationCustomizeController::show()` already loads galleries and events. For Storybook, additionally load sections:

```php
if ($isStorybook) {
    $invitation->load([
        'sections' => fn($q) => $q->whereIn('section_key', ['love_story', 'couple', 'gift', 'rsvp']),
    ]);
}
```

Pass to page:
```php
'sections' => $isStorybook
    ? $invitation->sections->keyBy('section_key')->map(fn($s) => $s->data_json)
    : null,
```

### Saving

Each section editor saves independently via existing API endpoints:

| Section | Method | Endpoint |
|---|---|---|
| Gallery upload | POST | `/api/invitations/{id}/galleries` (multipart) |
| Gallery delete | DELETE | `/api/invitations/{id}/galleries/{galleryId}` |
| Gallery reorder | PUT | `/api/invitations/{id}/galleries/reorder` |
| Events | PATCH | `/api/invitations/{id}/events/{eventId}` |
| Love Story | PATCH | `/api/invitations/{id}/sections/love_story` |
| Couple | PATCH | `/api/invitations/{id}/details` (invitation_details table, not sections) |
| RSVP toggle | PATCH | `/api/invitations/{id}/sections/rsvp/toggle` |
| Gift | PATCH | `/api/invitations/{id}/sections/gift` |

### Live preview

After each save, Customize.vue updates `previewInvitation` so the PhoneMockup reflects the latest content without a page reload.

---

## ContentModal Behavior

- **Mobile** (< lg): full-screen modal, slides in from bottom, `position: fixed; inset: 0`
- **Desktop** (lg+): centered overlay with `max-w-lg`, semi-transparent backdrop
- Header: title + × close button
- Body: scrollable content area
- Footer: "Simpan" button (per section) or save is automatic (gallery)

---

## Section-by-Section UI

### Galeri
Accordion shows: layout picker (existing) + pill "N foto" + "Edit Foto →" button.
Modal: 3-column photo grid. Each photo has × delete. Bottom: upload button. ↑↓ reorder.

### Date & Venue
Accordion shows: pill "N acara" + "Edit →".
Modal: list of events, each editable inline — name, date (date input), time (time input), venue (text), maps URL (text).

### Love Story
Accordion shows: pill "N chapters" + "Edit →".
Modal: list of chapters (date, title, truncated description). Tap chapter → edit form. "+ Tambah" button at top.

### Tentang Kami
Accordion shows: couple names + "Edit →".
Modal: two sections (Pengantin Pria / Pengantin Wanita). Each: full name, nickname, bio (textarea), photo upload.

### RSVP
Accordion: inline toggle (on/off). No modal. Uses `PATCH .../rsvp/toggle`.

### Hadiah
Accordion shows: pill "N rekening" + "Edit →".
Modal: list of bank accounts. Each row: bank name, account number, account name. "+ Tambah Rekening" button. Delete × per row.

### Musik
Unchanged — shows link to Edit page.

---

## State Management in Customize.vue

```js
const modalSection  = ref(null)   // which section modal is open: null | 'gallery' | 'events' | ...
const sectionsData  = ref(...)    // initialized from props.invitation.sections
const galleries     = ref(...)    // initialized from props.invitation.galleries
const events        = ref(...)    // initialized from props.invitation.events

function openModal(key) { modalSection.value = key }
function closeModal()   { modalSection.value = null }
```

`previewInvitation` computed includes `sectionsData`, `galleries`, `events` so preview updates live.

---

## API Compatibility Notes

- `PATCH /api/invitations/{id}/sections/{key}` expects `{ data: {...}, status: 'complete' }`
- `PATCH /api/invitations/{id}/sections/{key}/toggle` expects no body
- `PATCH /api/invitations/{id}/details` — needs verification; if endpoint doesn't exist, add it to `Api/InvitationController`
- Events PATCH — needs verification; may need a new `PATCH /api/invitations/{id}/events/{id}` route if not already present
- Gallery reorder: `PUT /api/invitations/{id}/galleries/reorder` expects `{ ids: [...] }`

---

## Out of Scope / Future

- Drag-and-drop reordering (use ↑↓ buttons for now)
- Adding new events (fixed by template)
- Couple photo cropping
- Love story photo cropping
