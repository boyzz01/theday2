# Storybook Customize Page — Design Spec

**Date:** 2026-04-25
**Status:** Approved

---

## Background

TheDay has two template categories:

- **Pernikahan** — traditional scroll templates (Bunga Abadi, Langit Senja, dll). Customize page shows per-section background controls (Cover, Opening, Acara, Galeri, Penutup) + Musik.
- **Storybook** — illustrated scene templates (Garden, Beach, Night Sky). Users interact via hotspot modals. There are no page-level sections with swappable backgrounds.

The current `/dashboard/invitations/{id}/customize` page is built around section background customization, which is irrelevant for Storybook. This spec defines what the page should show instead.

---

## Goal

When a user opens the customize page for a Storybook template, show only controls that are meaningful for that template type — gallery layout picker and music — and hide the section background controls entirely.

---

## Design Decision

**Approach: Conditional rendering in `Customize.vue`** (Option A — chosen)

Detect `isStorybook` from `invitation.template.category_slug === 'storybook'`. Use this flag to:
- Swap the `SECTIONS` array to only `['gallery', 'music']`
- Replace the Galeri accordion content with a layout picker instead of `SectionBgControl`
- Hide Cover, Opening, Acara, Penutup sections

No new page component is created. All changes are in `Customize.vue` and its controller.

---

## What Changes

### 1. Controller — `InvitationCustomizeController::show()`

Load the template's category slug and pass it to the page:

```php
// Eager-load category alongside template
$invitation->load(['details', 'events', 'galleries', 'music', 'template.category']);

// Pass category slug to the page
'template_category_slug' => $invitation->template->category?->slug,
```

### 2. `Customize.vue` — Category detection

```js
const isStorybook = computed(() =>
    props.invitation.template_category_slug === 'storybook'
)
```

### 3. `Customize.vue` — Sections array

```js
const SECTIONS_REGULAR = [
    { key: 'cover',   label: 'Cover',   icon: '🖼️' },
    { key: 'opening', label: 'Opening', icon: '✉️' },
    { key: 'events',  label: 'Acara',   icon: '📅' },
    { key: 'gallery', label: 'Galeri',  icon: '🖼️' },
    { key: 'music',   label: 'Musik',   icon: '🎵' },
    { key: 'closing', label: 'Penutup', icon: '💌' },
]

const SECTIONS_STORYBOOK = [
    { key: 'gallery', label: 'Galeri', icon: '🖼️' },
    { key: 'music',   label: 'Musik',  icon: '🎵' },
]

const sections = computed(() => isStorybook.value ? SECTIONS_STORYBOOK : SECTIONS_REGULAR)
```

### 4. `Customize.vue` — Accordion content

Inside the accordion for `key === 'gallery'`:

```html
<!-- Storybook: layout picker -->
<template v-if="isStorybook">
    <GalleryLayoutPicker v-model="customConfig.gallery_layout" />
</template>

<!-- Regular: background control -->
<template v-else>
    <SectionBgControl ... />
</template>
```

### 5. New component — `GalleryLayoutPicker.vue`

Location: `resources/js/Components/invitation/customize/GalleryLayoutPicker.vue`

Renders 4 selectable tiles (polaroid, masonry, grid, scroll) with icon + label. Emits `update:modelValue` on selection.

### 6. Saving `gallery_layout`

The current auto-save in `Customize.vue` only watches `form` (section_backgrounds) and posts `{ section_backgrounds }`. Two extensions are required:

**Frontend (`Customize.vue`):**
- Add `galleryLayout` ref, initialized from `invitation.config.gallery_layout ?? 'polaroid'`
- Watch `galleryLayout` with the same 1.5s debounce
- Include it in the POST payload: `{ section_backgrounds, gallery_layout }`

**Controller (`update()`):**
```php
$validated = $request->validate([
    'section_backgrounds'         => 'nullable|array',
    // ... existing section_backgrounds rules ...
    'gallery_layout'              => 'nullable|string|in:polaroid,masonry,grid,scroll',
]);

$invitation->update([
    'custom_config' => array_merge(
        $invitation->custom_config ?? [],
        array_filter($validated, fn($v) => $v !== null)
    ),
]);
```

This ensures both regular and Storybook saves go through the same endpoint.

---

## Out of Scope

- Cover overlay opacity — hardcoded in `SceneTemplate.vue`
- Hotspot position editing
- Per-template scene-specific settings (deferred to future "Scene Settings" feature)
- Music section behavior — unchanged, same for both template types

---

## Affected Files

| File | Change |
|---|---|
| `app/Http/Controllers/Dashboard/InvitationCustomizeController.php` | Load `template.category`, pass `template_category_slug` to page |
| `resources/js/Pages/Dashboard/Invitations/Customize.vue` | Add `isStorybook`, conditional SECTIONS, conditional accordion content |
| `resources/js/Components/invitation/customize/GalleryLayoutPicker.vue` | New component — 4-tile layout picker |

---

## Data Flow

```
User picks layout → customConfig.gallery_layout updated →
auto-save debounce (1.5s) → POST /customize →
controller merges into custom_config JSON →
useInvitationTemplate reads cfg.gallery_layout → scene renders correct layout
```
