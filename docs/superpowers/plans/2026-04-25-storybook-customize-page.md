# Storybook Customize Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When a Storybook template user opens `/customize`, show only a gallery layout picker and music section — hiding the irrelevant section-background controls.

**Architecture:** Detect `isStorybook` in `Customize.vue` from a new `template_category_slug` prop passed by the controller. Swap the SECTIONS array and replace the gallery accordion content with a `GalleryLayoutPicker` component. Extend the controller's `update()` and `show()` minimally to support this.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3 `<script setup>`, Tailwind CSS

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `app/Http/Controllers/Dashboard/InvitationCustomizeController.php` | Modify | Load category slug, pass to page; extend `update()` to save `gallery_layout` |
| `resources/js/Pages/Dashboard/Invitations/Customize.vue` | Modify | `isStorybook` detection, conditional SECTIONS, gallery layout state + save |
| `resources/js/Components/invitation/customize/GalleryLayoutPicker.vue` | Create | 4-tile layout picker (polaroid / masonry / grid / scroll) |

---

## Task 1: Controller — pass category slug + extend update()

**Files:**
- Modify: `app/Http/Controllers/Dashboard/InvitationCustomizeController.php`

### `show()` — load category and pass slug

- [ ] **Step 1: Add `template.category` to the eager-load in `show()`**

In `show()`, line 23–29, change:
```php
$invitation->load([
    'details',
    'events'    => fn ($q) => $q->orderBy('sort_order')->orderBy('event_date'),
    'galleries' => fn ($q) => $q->orderBy('sort_order'),
    'music'     => fn ($q) => $q->where('is_default', true)->limit(1),
    'template:id,name,slug,default_config',
]);
```
To:
```php
$invitation->load([
    'details',
    'events'    => fn ($q) => $q->orderBy('sort_order')->orderBy('event_date'),
    'galleries' => fn ($q) => $q->orderBy('sort_order'),
    'music'     => fn ($q) => $q->where('is_default', true)->limit(1),
    'template:id,name,slug,default_config,category_id',
    'template.category:id,slug',
]);
```

- [ ] **Step 2: Pass `template_category_slug` in the Inertia response**

In `show()`, inside `Inertia::render(...)`, after `'template_slug'` (line 39), add:
```php
'template_category_slug' => $invitation->template?->category?->slug,
```

### `update()` — also save `gallery_layout`

- [ ] **Step 3: Extend the validation in `update()` to accept `gallery_layout`**

Replace the existing `$request->validate([...])` block (lines 81–85) with:
```php
$request->validate([
    'section_backgrounds'           => 'nullable|array',
    'section_backgrounds.*.type'    => 'nullable|in:image,video,color',
    'section_backgrounds.*.value'   => 'nullable|string|max:1000',
    'section_backgrounds.*.opacity' => 'nullable|numeric|min:0|max:1',
    'gallery_layout'                => 'nullable|string|in:polaroid,masonry,grid,scroll',
]);
```

- [ ] **Step 4: Merge both values into `custom_config`**

Replace lines 87–89:
```php
$config                        = $invitation->custom_config ?? [];
$config['section_backgrounds'] = $request->input('section_backgrounds');
$invitation->update(['custom_config' => $config]);
```
With:
```php
$config = $invitation->custom_config ?? [];

if ($request->has('section_backgrounds')) {
    $config['section_backgrounds'] = $request->input('section_backgrounds');
}
if ($request->has('gallery_layout')) {
    $config['gallery_layout'] = $request->input('gallery_layout');
}

$invitation->update(['custom_config' => $config]);
```

- [ ] **Step 5: Verify manually**

Open a browser console on the customize page for a Storybook template and run:
```js
// Confirm template_category_slug is available in the page props
$page.props.invitation.template_category_slug // should be "storybook"
```

- [ ] **Step 6: Commit**
```bash
rtk git add app/Http/Controllers/Dashboard/InvitationCustomizeController.php
rtk git commit -m "feat: pass template_category_slug and support gallery_layout in customize controller"
```

---

## Task 2: GalleryLayoutPicker component

**Files:**
- Create: `resources/js/Components/invitation/customize/GalleryLayoutPicker.vue`

- [ ] **Step 1: Create the component**

```vue
<!-- resources/js/Components/invitation/customize/GalleryLayoutPicker.vue -->
<script setup>
defineProps({
    modelValue: { type: String, default: 'polaroid' },
})
defineEmits(['update:modelValue'])

const layouts = [
    { key: 'polaroid', label: 'Polaroid', icon: '🃏', desc: 'Tersebar acak' },
    { key: 'masonry',  label: 'Masonry',  icon: '⬜', desc: 'Grid dinamis' },
    { key: 'grid',     label: 'Grid',     icon: '⊞', desc: 'Seragam 2 kolom' },
    { key: 'scroll',   label: 'Scroll',   icon: '↔', desc: 'Horizontal geser' },
]
</script>

<template>
    <div class="space-y-2">
        <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Layout Galeri</p>
        <div class="grid grid-cols-2 gap-2">
            <button
                v-for="layout in layouts"
                :key="layout.key"
                type="button"
                :class="[
                    'flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 text-center transition-all',
                    modelValue === layout.key
                        ? 'border-[#92A89C] bg-[#92A89C]/10 text-stone-800'
                        : 'border-stone-200 bg-white text-stone-500 hover:border-stone-300',
                ]"
                @click="$emit('update:modelValue', layout.key)"
            >
                <span class="text-xl">{{ layout.icon }}</span>
                <span class="text-xs font-semibold">{{ layout.label }}</span>
                <span class="text-[10px] leading-tight text-stone-400">{{ layout.desc }}</span>
            </button>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Commit**
```bash
rtk git add resources/js/Components/invitation/customize/GalleryLayoutPicker.vue
rtk git commit -m "feat: add GalleryLayoutPicker component for storybook customize"
```

---

## Task 3: Customize.vue — Storybook detection + conditional UI + save

**Files:**
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Import `GalleryLayoutPicker`**

At the top of `<script setup>` (after line 7), add:
```js
import GalleryLayoutPicker from '@/Components/invitation/customize/GalleryLayoutPicker.vue';
```

- [ ] **Step 2: Add `isStorybook` computed and `galleryLayout` ref**

After the `previewInvitation` computed (after line 34), add:
```js
const isStorybook = computed(() =>
    props.invitation.template_category_slug === 'storybook'
)

const galleryLayout = ref(
    props.invitation.config?.gallery_layout ?? 'polaroid'
)
```

- [ ] **Step 3: Replace the static `SECTIONS` constant with a computed**

Remove lines 37–44:
```js
const SECTIONS = [
    { key: 'cover',   label: 'Cover',   icon: '🖼️' },
    { key: 'opening', label: 'Opening', icon: '✉️' },
    { key: 'events',  label: 'Acara',   icon: '📅' },
    { key: 'gallery', label: 'Galeri',  icon: '🖼️' },
    { key: 'music',   label: 'Musik',   icon: '🎵' },
    { key: 'closing', label: 'Penutup', icon: '💌' },
];
```
Replace with:
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

const sections = computed(() =>
    isStorybook.value ? SECTIONS_STORYBOOK : SECTIONS_REGULAR
)
```

- [ ] **Step 4: Set `activeKey` default based on template type**

Change line 21:
```js
const activeKey = ref('cover');
```
To:
```js
const activeKey = ref(
    props.invitation.template_category_slug === 'storybook' ? 'gallery' : 'cover'
)
```

- [ ] **Step 5: Watch `galleryLayout` for auto-save**

After the existing `watch(activeKey, ...)` block (around line 106), add:
```js
watch(galleryLayout, () => scheduleAutoSave())
```

- [ ] **Step 6: Extend `save()` to include `gallery_layout`**

Replace the `save()` function (lines 75–84):
```js
async function save() {
    saveStatus.value = 'saving';
    try {
        await axios.post(`/dashboard/invitations/${props.invitation.id}/customize`, {
            section_backgrounds: form.value,
        });
        saveStatus.value = 'saved';
    } catch {
        saveStatus.value = 'error';
    }
}
```
With:
```js
async function save() {
    saveStatus.value = 'saving';
    try {
        const payload = isStorybook.value
            ? { gallery_layout: galleryLayout.value }
            : { section_backgrounds: form.value }

        await axios.post(`/dashboard/invitations/${props.invitation.id}/customize`, payload);
        saveStatus.value = 'saved';
    } catch {
        saveStatus.value = 'error';
    }
}
```

- [ ] **Step 7: Update `previewInvitation` to include `gallery_layout`**

Replace the `previewInvitation` computed (lines 28–34):
```js
const previewInvitation = computed(() => ({
    ...props.invitation,
    config: {
        ...props.invitation.config,
        section_backgrounds: form.value,
    },
}));
```
With:
```js
const previewInvitation = computed(() => ({
    ...props.invitation,
    config: {
        ...props.invitation.config,
        section_backgrounds: form.value,
        gallery_layout:      galleryLayout.value,
    },
}));
```

- [ ] **Step 8: Update the template — use `sections` computed instead of `SECTIONS`**

In the `<template>`, find:
```html
<div v-for="section in SECTIONS" :key="section.key">
```
Change to:
```html
<div v-for="section in sections" :key="section.key">
```

- [ ] **Step 9: Add conditional accordion content for gallery section**

Find the block inside the accordion (lines 157–162):
```html
<template v-if="section.key !== 'music'">
    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider pt-2">Background</p>
    <SectionBgControl
        :model-value="form[section.key]"
        :section-key="section.key"
        :invitation-id="invitation.id"
        :uploading="uploadingKey === section.key"
        @update:model-value="(bg) => { onBgChange(section.key, bg); scheduleAutoSave(); }"
        @upload="(file) => uploadBg(section.key, file).then(ok => ok && scheduleAutoSave())"
    />
</template>
```
Replace with:
```html
<template v-if="section.key !== 'music'">
    <!-- Storybook: gallery layout picker -->
    <GalleryLayoutPicker
        v-if="isStorybook && section.key === 'gallery'"
        :model-value="galleryLayout"
        @update:model-value="galleryLayout = $event"
    />
    <!-- Regular: background control -->
    <template v-else-if="!isStorybook">
        <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider pt-2">Background</p>
        <SectionBgControl
            :model-value="form[section.key]"
            :section-key="section.key"
            :invitation-id="invitation.id"
            :uploading="uploadingKey === section.key"
            @update:model-value="(bg) => { onBgChange(section.key, bg); scheduleAutoSave(); }"
            @upload="(file) => uploadBg(section.key, file).then(ok => ok && scheduleAutoSave())"
        />
    </template>
</template>
```

- [ ] **Step 10: Verify manually**

1. Open `http://127.0.0.1:8000/dashboard/invitations/{storybook-id}/customize`
2. Left panel should show only **Galeri** and **Musik** sections
3. Galeri accordion should show 4 layout tiles (Polaroid, Masonry, Grid, Scroll)
4. Clicking a tile should highlight it and trigger auto-save after 1.5s
5. Reload the page — selected layout should persist
6. Open a regular template's customize page — all 6 sections should still appear unchanged
7. Right panel preview: switching layout tile should update gallery layout in the live preview

- [ ] **Step 11: Commit**
```bash
rtk git add resources/js/Pages/Dashboard/Invitations/Customize.vue
rtk git commit -m "feat: show gallery layout picker for storybook templates on customize page"
```
