# Storybook Content Editor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow users with Storybook templates to edit all section content (gallery photos, events, love story, couple profile, RSVP, gift/bank accounts) directly from the customize page.

**Architecture:** Expand `SECTIONS_STORYBOOK` in Customize.vue to all 7 sections. Each section shows a summary badge + "Edit →" button that opens a `ContentModal` (full-screen mobile, centered desktop). RSVP is the only exception — it renders an inline toggle. All editors call existing API endpoints via axios. Live preview updates as local state changes.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3 `<script setup>`, Tailwind CSS, axios

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `app/Http/Controllers/Dashboard/InvitationCustomizeController.php` | Modify | Load sections for Storybook, add `venue_address` to events map |
| `resources/js/Pages/Dashboard/Invitations/Customize.vue` | Modify | Expand SECTIONS_STORYBOOK, add modal state, RSVP toggle, section badges, wire all editors |
| `resources/js/Components/invitation/customize/ContentModal.vue` | Create | Reusable modal: full-screen mobile, centered desktop. Header + scrollable body + footer slot |
| `resources/js/Components/invitation/customize/SectionGalleryPhotos.vue` | Create | Photo grid: upload, delete, ↑↓ reorder |
| `resources/js/Components/invitation/customize/SectionEventsEditor.vue` | Create | Editable list of events (name, date, time, venue, maps) |
| `resources/js/Components/invitation/customize/SectionLoveStoryEditor.vue` | Create | Story chapters: add/edit/delete, each with date, title, description, optional photo |
| `resources/js/Components/invitation/customize/SectionCoupleEditor.vue` | Create | Groom + bride profile: name, nickname, bio, photo upload |
| `resources/js/Components/invitation/customize/SectionGiftEditor.vue` | Create | Bank accounts list: add/delete, each with bank name, account number, account name |

---

## Task 1: Backend — Load sections + fix events map

**Files:**
- Modify: `app/Http/Controllers/Dashboard/InvitationCustomizeController.php`

- [ ] **Step 1: Add `venue_address` to the events map in `show()`**

In `show()`, the events map (lines 53–64) is missing `venue_address`. Add it:

```php
'events' => $invitation->events->map(fn ($e) => [
    'id'                   => $e->id,
    'event_name'           => $e->event_name,
    'event_date'           => $e->event_date?->format('Y-m-d'),
    'event_date_formatted' => $e->event_date
        ? Carbon::parse($e->event_date)->locale('id')->translatedFormat('l, d F Y')
        : null,
    'start_time'    => $e->start_time ? substr($e->start_time, 0, 5) : null,
    'end_time'      => $e->end_time   ? substr($e->end_time, 0, 5)   : null,
    'venue_name'    => $e->venue_name,
    'venue_address' => $e->venue_address,
    'maps_url'      => $e->maps_url,
])->values(),
```

- [ ] **Step 2: Load sections for Storybook in `show()`**

After the existing `$invitation->load([...])` block (ends at line 30), add:

```php
$isStorybook = $invitation->template?->category?->slug === 'storybook';

if ($isStorybook) {
    $invitation->load([
        'sections' => fn ($q) => $q->whereIn('section_key', ['love_story', 'gift', 'rsvp']),
    ]);
}
```

- [ ] **Step 3: Pass `sections` in the Inertia response**

Inside the `'invitation'` array in `Inertia::render(...)`, add after `'galleries'`:

```php
'sections' => $isStorybook
    ? $invitation->sections->keyBy('section_key')->map(fn ($s) => [
        'data'       => $s->data_json ?? [],
        'is_enabled' => $s->is_enabled,
    ])
    : null,
```

Also add `groom_nickname` and `bride_nickname` to the `details` map since they are used by Tentang Kami editor. The existing details block already includes them (lines 47–48) — no change needed here.

- [ ] **Step 4: Verify**

Open browser console on a Storybook template customize page:
```js
$page.props.invitation.sections
// Expected: { love_story: { data: {...}, is_enabled: true }, gift: {...}, rsvp: {...} }
$page.props.invitation.events[0].venue_address
// Expected: string or null (not undefined)
```

- [ ] **Step 5: Commit**
```bash
rtk git add app/Http/Controllers/Dashboard/InvitationCustomizeController.php
rtk git commit -m "feat: load sections and venue_address for storybook customize page"
```

---

## Task 2: ContentModal component

**Files:**
- Create: `resources/js/Components/invitation/customize/ContentModal.vue`

- [ ] **Step 1: Create the component**

```vue
<!-- resources/js/Components/invitation/customize/ContentModal.vue -->
<script setup>
defineProps({
    open:  { type: Boolean, required: true },
    title: { type: String,  required: true },
})
defineEmits(['close'])
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end lg:items-center justify-center p-0 lg:p-4"
            >
                <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

                <div class="relative w-full lg:max-w-lg max-h-[92vh] flex flex-col bg-white rounded-t-2xl lg:rounded-2xl shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100 flex-shrink-0">
                        <h2 class="text-sm font-bold text-stone-800">{{ title }}</h2>
                        <button
                            type="button"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors"
                            @click="$emit('close')"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-5 space-y-4">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="px-5 py-4 border-t border-stone-100 flex-shrink-0">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: translateY(20px);
}
</style>
```

- [ ] **Step 2: Commit**
```bash
rtk git add resources/js/Components/invitation/customize/ContentModal.vue
rtk git commit -m "feat: add ContentModal component for storybook content editors"
```

---

## Task 3: Customize.vue — Expand sections + modal scaffolding + RSVP toggle

**Files:**
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Import ContentModal**

After the existing imports, add:
```js
import ContentModal from '@/Components/invitation/customize/ContentModal.vue';
```

- [ ] **Step 2: Expand SECTIONS_STORYBOOK**

Replace the existing `SECTIONS_STORYBOOK` constant:
```js
const SECTIONS_STORYBOOK = [
    { key: 'gallery',    label: 'Galeri'       },
    { key: 'events',     label: 'Date & Venue' },
    { key: 'love_story', label: 'Love Story'   },
    { key: 'couple',     label: 'Tentang Kami' },
    { key: 'rsvp',       label: 'RSVP'         },
    { key: 'gift',       label: 'Hadiah'       },
    { key: 'music',      label: 'Musik'        },
]
```

- [ ] **Step 3: Add local state for sections, galleries, events, details**

After the `galleryLayout` ref, add:
```js
const galleries    = ref([...(props.invitation.galleries ?? [])])
const events       = ref([...(props.invitation.events     ?? [])])
const details      = ref({ ...(props.invitation.details   ?? {}) })
const sectionsData = ref(
    JSON.parse(JSON.stringify(props.invitation.sections ?? {}))
)
const modalSection = ref(null)
```

- [ ] **Step 4: Add helper functions**

After the `save()` function, add:
```js
function openModal(key)   { modalSection.value = key }
function closeModal()     { modalSection.value = null }

function sectionBadge(key) {
    switch (key) {
        case 'gallery':    return galleries.value.length ? `${galleries.value.length} foto` : null
        case 'events':     return events.value.length    ? `${events.value.length} acara`   : null
        case 'love_story': {
            const count = sectionsData.value.love_story?.data?.stories?.length ?? 0
            return count ? `${count} chapter` : null
        }
        case 'couple':
            return (details.value.groom_name || details.value.bride_name) ? 'terisi' : null
        case 'gift': {
            const count = sectionsData.value.gift?.data?.accounts?.length ?? 0
            return count ? `${count} rekening` : null
        }
        default: return null
    }
}

async function toggleRsvp() {
    try {
        const res = await axios.patch(
            `/api/invitations/${props.invitation.id}/sections/rsvp/toggle`
        )
        if (!sectionsData.value.rsvp) sectionsData.value.rsvp = {}
        sectionsData.value.rsvp.is_enabled = res.data.is_enabled
    } catch {
        alert('Gagal mengubah RSVP.')
    }
}
```

- [ ] **Step 5: Update `previewInvitation` to include local state**

Replace the existing `previewInvitation` computed:
```js
const previewInvitation = computed(() => ({
    ...props.invitation,
    config: {
        ...props.invitation.config,
        section_backgrounds: form.value,
        gallery_layout:      galleryLayout.value,
    },
    galleries:    galleries.value,
    events:       events.value,
    details:      details.value,
    sections:     sectionsData.value,
}))
```

- [ ] **Step 6: Update the accordion template for Storybook sections**

In the template, find the accordion section block and replace its entire contents with a conditional:

```html
<!-- Section accordion -->
<div :class="['flex-1 overflow-y-auto divide-y divide-stone-50', activeTab === 'preview' ? 'hidden lg:block' : '']">
    <div v-for="section in sections" :key="section.key">

        <!-- Row button -->
        <button
            type="button"
            @click="activeKey = activeKey === section.key ? null : section.key"
            class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-stone-50 transition-colors"
        >
            <span class="text-sm font-medium text-stone-700">{{ section.label }}</span>
            <div class="flex items-center gap-2">
                <span
                    v-if="isStorybook && sectionBadge(section.key)"
                    class="text-xs px-2 py-0.5 rounded-full bg-stone-100 text-stone-500"
                >{{ sectionBadge(section.key) }}</span>
                <svg
                    :class="['w-4 h-4 text-stone-400 transition-transform', activeKey === section.key ? 'rotate-180' : '']"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>

        <!-- Expanded content -->
        <div v-if="activeKey === section.key" class="px-5 pb-5 space-y-3 bg-stone-50/50">

            <!-- ── Storybook sections ─────────────────────── -->
            <template v-if="isStorybook">

                <!-- Gallery: layout picker + edit photos button -->
                <template v-if="section.key === 'gallery'">
                    <GalleryLayoutPicker
                        :model-value="galleryLayout"
                        @update:model-value="galleryLayout = $event"
                    />
                    <button
                        type="button"
                        @click="openModal('gallery')"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl border border-stone-200 bg-white text-sm text-stone-600 hover:bg-stone-50 transition-colors"
                    >
                        <span>Edit Foto</span>
                        <span class="text-stone-400">→</span>
                    </button>
                </template>

                <!-- RSVP: inline toggle -->
                <template v-else-if="section.key === 'rsvp'">
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-sm text-stone-600">Aktifkan RSVP</span>
                        <button
                            type="button"
                            @click="toggleRsvp"
                            :class="[
                                'w-10 h-6 rounded-full transition-colors relative',
                                sectionsData.rsvp?.is_enabled ? 'bg-[#92A89C]' : 'bg-stone-200'
                            ]"
                        >
                            <span :class="[
                                'block w-4 h-4 bg-white rounded-full absolute top-1 transition-transform',
                                sectionsData.rsvp?.is_enabled ? 'translate-x-5' : 'translate-x-1'
                            ]" />
                        </button>
                    </div>
                </template>

                <!-- Music: link to edit page -->
                <template v-else-if="section.key === 'music'">
                    <p class="text-xs text-stone-500">Upload file musik (MP3, maks 10MB). Gunakan fitur upload musik di halaman edit undangan.</p>
                    <Link
                        :href="route('dashboard.invitations.edit', invitation.id)"
                        class="inline-block text-xs px-3 py-2 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                    >
                        Buka Editor Musik →
                    </Link>
                </template>

                <!-- Other sections: edit button -->
                <template v-else>
                    <button
                        type="button"
                        @click="openModal(section.key)"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl border border-stone-200 bg-white text-sm text-stone-600 hover:bg-stone-50 transition-colors"
                    >
                        <span>Edit {{ section.label }}</span>
                        <span class="text-stone-400">→</span>
                    </button>
                </template>

            </template>

            <!-- ── Regular template sections ─────────────── -->
            <template v-else>
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
                <template v-if="section.key === 'music'">
                    <p class="text-xs text-stone-500">Upload file musik (MP3, maks 10MB). Gunakan fitur upload musik di halaman edit undangan.</p>
                    <Link
                        :href="route('dashboard.invitations.edit', invitation.id)"
                        class="inline-block text-xs px-3 py-2 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                    >
                        Buka Editor Musik →
                    </Link>
                </template>
            </template>

        </div>
    </div>
</div>
```

Also add the `<ContentModal>` placeholder (empty for now, editors wired in later tasks) after the editor `</div>` and before the `</div>` closing the `v-else` main content:

```html
<!-- Content modals (editors wired in Tasks 4–8) -->
<ContentModal
    :open="modalSection !== null"
    :title="sections.find(s => s.key === modalSection)?.label ?? ''"
    @close="closeModal"
>
    <div class="text-sm text-stone-400 text-center py-8">Editor segera hadir.</div>
</ContentModal>
```

- [ ] **Step 7: Verify**

1. Open Storybook customize page — accordion shows all 7 sections
2. Expand "Date & Venue" → see "Edit Date & Venue →" button
3. Click button → modal opens with placeholder text
4. Close modal → works
5. Expand "RSVP" → toggle visible and functional
6. Expand "Galeri" → layout picker + "Edit Foto →" button both visible
7. Open regular template customize — still shows 6 sections with background controls unchanged

- [ ] **Step 8: Commit**
```bash
rtk git add resources/js/Pages/Dashboard/Invitations/Customize.vue
rtk git commit -m "feat: expand storybook sections accordion with modal scaffold and rsvp toggle"
```

---

## Task 4: SectionGalleryPhotos component + wire

**Files:**
- Create: `resources/js/Components/invitation/customize/SectionGalleryPhotos.vue`
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Create the component**

```vue
<!-- resources/js/Components/invitation/customize/SectionGalleryPhotos.vue -->
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Array,  default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const uploading = ref(false)
const error     = ref(null)

async function upload(event) {
    const file = event.target.files[0]
    if (!file) return
    uploading.value = true
    error.value     = null
    try {
        const fd = new FormData()
        fd.append('image', file)
        const res = await axios.post(
            `/api/invitations/${props.invitationId}/galleries`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        emit('update:modelValue', [...props.modelValue, res.data.data])
    } catch {
        error.value = 'Upload gagal. Coba lagi.'
    } finally {
        uploading.value        = false
        event.target.value     = ''
    }
}

async function remove(gallery) {
    try {
        await axios.delete(`/api/invitations/${props.invitationId}/galleries/${gallery.id}`)
        emit('update:modelValue', props.modelValue.filter(g => g.id !== gallery.id))
    } catch {
        alert('Gagal menghapus foto.')
    }
}

async function move(index, direction) {
    const list = [...props.modelValue]
    const target = index + direction
    if (target < 0 || target >= list.length) return
    ;[list[index], list[target]] = [list[target], list[index]]
    emit('update:modelValue', list)
    await axios.put(`/api/invitations/${props.invitationId}/galleries/reorder`, {
        ids: list.map(g => g.id),
    })
}
</script>

<template>
    <div class="space-y-3">
        <!-- Photo grid -->
        <div v-if="modelValue.length" class="grid grid-cols-3 gap-2">
            <div
                v-for="(photo, i) in modelValue"
                :key="photo.id"
                class="relative aspect-square rounded-lg overflow-hidden group"
            >
                <img :src="photo.image_url" class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100">
                    <button
                        type="button"
                        @click="move(i, -1)"
                        :disabled="i === 0"
                        class="w-6 h-6 bg-white/90 rounded text-stone-700 text-xs disabled:opacity-30"
                    >↑</button>
                    <button
                        type="button"
                        @click="move(i, 1)"
                        :disabled="i === modelValue.length - 1"
                        class="w-6 h-6 bg-white/90 rounded text-stone-700 text-xs disabled:opacity-30"
                    >↓</button>
                    <button
                        type="button"
                        @click="remove(photo)"
                        class="w-6 h-6 bg-red-500/90 rounded text-white text-xs"
                    >×</button>
                </div>
            </div>
        </div>

        <p v-else class="text-xs text-stone-400 text-center py-4">Belum ada foto.</p>

        <!-- Upload button -->
        <label class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-dashed border-stone-200 text-sm text-stone-500 hover:border-stone-300 hover:bg-stone-50 cursor-pointer transition-colors">
            <span>{{ uploading ? 'Mengupload...' : '+ Tambah Foto' }}</span>
            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" :disabled="uploading" @change="upload" />
        </label>

        <p v-if="error" class="text-xs text-red-400">{{ error }}</p>
    </div>
</template>
```

- [ ] **Step 2: Wire into Customize.vue**

Import the component:
```js
import SectionGalleryPhotos from '@/Components/invitation/customize/SectionGalleryPhotos.vue';
```

Replace the `<ContentModal>` placeholder with a proper conditional body. Find the `<ContentModal>` block added in Task 3 and replace it with:

```html
<ContentModal
    :open="modalSection !== null"
    :title="sections.find(s => s.key === modalSection)?.label ?? ''"
    @close="closeModal"
>
    <SectionGalleryPhotos
        v-if="modalSection === 'gallery'"
        :invitation-id="invitation.id"
        :model-value="galleries"
        @update:model-value="galleries = $event"
    />
    <div v-else class="text-sm text-stone-400 text-center py-8">Editor segera hadir.</div>
</ContentModal>
```

- [ ] **Step 3: Verify**

1. Open Storybook customize → Galeri → "Edit Foto →"
2. Modal opens, shows existing photos in 3-column grid
3. Hover photo → shows ↑↓ × controls
4. Click + Tambah Foto → file picker opens, upload works, photo appears in grid
5. Click × → photo removed from grid and deleted from server
6. Click ↑/↓ → photo moves, reorder persists on reload
7. Live preview in PhoneMockup updates gallery count badge

- [ ] **Step 4: Commit**
```bash
rtk git add resources/js/Components/invitation/customize/SectionGalleryPhotos.vue
rtk git add resources/js/Pages/Dashboard/Invitations/Customize.vue
rtk git commit -m "feat: add gallery photo editor to storybook customize page"
```

---

## Task 5: SectionEventsEditor component + wire

**Files:**
- Create: `resources/js/Components/invitation/customize/SectionEventsEditor.vue`
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Create the component**

```vue
<!-- resources/js/Components/invitation/customize/SectionEventsEditor.vue -->
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Array,  default: () => [] },
})
const emit   = defineEmits(['update:modelValue'])
const saving = ref(null)  // event id currently saving
const errors = ref({})

async function saveEvent(event) {
    saving.value = event.id
    errors.value = {}
    try {
        await axios.put(`/api/invitations/${props.invitationId}/events/${event.id}`, {
            event_name:    event.event_name,
            event_date:    event.event_date,
            start_time:    event.start_time || null,
            end_time:      event.end_time   || null,
            venue_name:    event.venue_name,
            venue_address: event.venue_address || null,
            maps_url:      event.maps_url    || null,
        })
        emit('update:modelValue', props.modelValue.map(e => e.id === event.id ? { ...event } : e))
    } catch (err) {
        errors.value[event.id] = err.response?.data?.message ?? 'Gagal menyimpan.'
    } finally {
        saving.value = null
    }
}
</script>

<template>
    <div class="space-y-6">
        <div v-for="event in modelValue" :key="event.id" class="space-y-3 pb-5 border-b border-stone-100 last:border-0">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">{{ event.event_name || 'Acara' }}</p>

            <div class="space-y-2">
                <input
                    v-model="event.event_name"
                    type="text"
                    placeholder="Nama acara (mis. Akad Nikah)"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <input
                    v-model="event.event_date"
                    type="date"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <div class="flex gap-2">
                    <input
                        v-model="event.start_time"
                        type="time"
                        placeholder="Mulai"
                        class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                    />
                    <input
                        v-model="event.end_time"
                        type="time"
                        placeholder="Selesai"
                        class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                    />
                </div>
                <input
                    v-model="event.venue_name"
                    type="text"
                    placeholder="Nama venue"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <input
                    v-model="event.venue_address"
                    type="text"
                    placeholder="Alamat lengkap"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <input
                    v-model="event.maps_url"
                    type="url"
                    placeholder="Link Google Maps"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
            </div>

            <p v-if="errors[event.id]" class="text-xs text-red-400">{{ errors[event.id] }}</p>

            <button
                type="button"
                @click="saveEvent(event)"
                :disabled="saving === event.id"
                class="w-full py-2 rounded-xl text-xs font-bold text-white bg-[#92A89C] hover:opacity-90 disabled:opacity-60 transition-all"
            >
                {{ saving === event.id ? 'Menyimpan...' : 'Simpan' }}
            </button>
        </div>

        <p v-if="!modelValue.length" class="text-xs text-stone-400 text-center py-4">Belum ada acara.</p>
    </div>
</template>
```

- [ ] **Step 2: Wire into Customize.vue**

Import:
```js
import SectionEventsEditor from '@/Components/invitation/customize/SectionEventsEditor.vue';
```

In the `<ContentModal>`, add after the `SectionGalleryPhotos` block:
```html
<SectionEventsEditor
    v-else-if="modalSection === 'events'"
    :invitation-id="invitation.id"
    :model-value="events"
    @update:model-value="events = $event"
/>
```

- [ ] **Step 3: Verify**

1. Open Date & Venue modal
2. Both events (Akad, Resepsi) shown with pre-filled fields
3. Edit venue name → click Simpan → saved to server
4. Reload page → changes persisted

- [ ] **Step 4: Commit**
```bash
rtk git add resources/js/Components/invitation/customize/SectionEventsEditor.vue
rtk git add resources/js/Pages/Dashboard/Invitations/Customize.vue
rtk git commit -m "feat: add events editor to storybook customize page"
```

---

## Task 6: SectionLoveStoryEditor component + wire

**Files:**
- Create: `resources/js/Components/invitation/customize/SectionLoveStoryEditor.vue`
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Create the component**

```vue
<!-- resources/js/Components/invitation/customize/SectionLoveStoryEditor.vue -->
<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Object, default: () => ({ stories: [] }) },
})
const emit = defineEmits(['update:modelValue'])

const local   = ref(JSON.parse(JSON.stringify(props.modelValue?.stories ?? [])))
const saving  = ref(false)
const editing = ref(null)  // index of story being edited, or null
const photoUploading = ref(false)

const editForm = ref({ date: '', title: '', description: '', photo_url: '' })

function startAdd() {
    editForm.value = { date: '', title: '', description: '', photo_url: '' }
    editing.value  = -1  // -1 = new item
}

function startEdit(index) {
    editForm.value = { ...local.value[index] }
    editing.value  = index
}

function cancelEdit() {
    editing.value = null
}

function removeStory(index) {
    local.value.splice(index, 1)
    saveAll()
}

async function uploadPhoto(event) {
    const file = event.target.files[0]
    if (!file) return
    photoUploading.value = true
    try {
        const fd = new FormData()
        fd.append('image', file)
        const res = await axios.post(
            `/api/invitations/${props.invitationId}/galleries`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        editForm.value.photo_url = res.data.data.image_url
    } catch {
        alert('Upload foto gagal.')
    } finally {
        photoUploading.value   = false
        event.target.value     = ''
    }
}

function confirmEdit() {
    if (editing.value === -1) {
        local.value.push({ ...editForm.value })
    } else {
        local.value[editing.value] = { ...editForm.value }
    }
    editing.value = null
    saveAll()
}

async function saveAll() {
    saving.value = true
    try {
        await axios.patch(`/api/invitations/${props.invitationId}/sections/love_story`, {
            data:   { stories: local.value },
            status: local.value.length ? 'complete' : 'empty',
        })
        emit('update:modelValue', { stories: [...local.value] })
    } catch {
        alert('Gagal menyimpan love story.')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div class="space-y-3">
        <!-- Story list -->
        <div v-if="local.length" class="space-y-2">
            <div
                v-for="(story, i) in local"
                :key="i"
                class="flex items-center gap-3 p-3 rounded-xl border border-stone-100 bg-white"
            >
                <div
                    v-if="story.photo_url"
                    class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0"
                >
                    <img :src="story.photo_url" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-10 h-10 rounded-lg bg-stone-100 flex-shrink-0" />

                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-stone-700 truncate">{{ story.title || '(tanpa judul)' }}</p>
                    <p class="text-[10px] text-stone-400">{{ story.date }}</p>
                </div>

                <div class="flex gap-1">
                    <button type="button" @click="startEdit(i)" class="text-xs px-2 py-1 rounded-lg bg-stone-100 text-stone-600 hover:bg-stone-200">Edit</button>
                    <button type="button" @click="removeStory(i)" class="text-xs px-2 py-1 rounded-lg bg-red-50 text-red-400 hover:bg-red-100">×</button>
                </div>
            </div>
        </div>

        <p v-else-if="editing === null" class="text-xs text-stone-400 text-center py-3">Belum ada chapter.</p>

        <!-- Edit / Add form -->
        <div v-if="editing !== null" class="space-y-2 p-3 rounded-xl border border-[#92A89C]/30 bg-stone-50">
            <input v-model="editForm.date" type="text" placeholder="Tanggal (mis. Maret 2020)"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="editForm.title" type="text" placeholder="Judul chapter"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <textarea v-model="editForm.description" rows="3" placeholder="Cerita singkat..."
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C] resize-none" />

            <!-- Photo -->
            <div class="flex items-center gap-2">
                <img v-if="editForm.photo_url" :src="editForm.photo_url" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                <label class="flex-1 flex items-center justify-center px-3 py-2 rounded-lg border border-dashed border-stone-200 text-xs text-stone-500 cursor-pointer hover:bg-stone-100">
                    {{ photoUploading ? 'Mengupload...' : (editForm.photo_url ? 'Ganti foto' : '+ Foto') }}
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" :disabled="photoUploading" @change="uploadPhoto" />
                </label>
            </div>

            <div class="flex gap-2">
                <button type="button" @click="cancelEdit" class="flex-1 py-2 rounded-xl text-xs font-medium border border-stone-200 text-stone-600 hover:bg-stone-50">Batal</button>
                <button type="button" @click="confirmEdit" :disabled="saving" class="flex-1 py-2 rounded-xl text-xs font-bold text-white bg-[#92A89C] hover:opacity-90 disabled:opacity-60">
                    {{ saving ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </div>

        <!-- Add button -->
        <button
            v-if="editing === null"
            type="button"
            @click="startAdd"
            class="w-full py-2.5 rounded-xl border-2 border-dashed border-stone-200 text-sm text-stone-500 hover:border-stone-300 hover:bg-stone-50 transition-colors"
        >
            + Tambah Chapter
        </button>
    </div>
</template>
```

- [ ] **Step 2: Wire into Customize.vue**

Import:
```js
import SectionLoveStoryEditor from '@/Components/invitation/customize/SectionLoveStoryEditor.vue';
```

In `<ContentModal>`, add:
```html
<SectionLoveStoryEditor
    v-else-if="modalSection === 'love_story'"
    :invitation-id="invitation.id"
    :model-value="sectionsData.love_story?.data ?? { stories: [] }"
    @update:model-value="sectionsData = { ...sectionsData, love_story: { ...sectionsData.love_story, data: $event } }"
/>
```

- [ ] **Step 3: Verify**

1. Open Love Story modal → existing chapters listed
2. Click "Edit" on a chapter → edit form appears with pre-filled data
3. Edit title → Simpan → chapter updated, badge in accordion updates to correct count
4. Click "+ Tambah Chapter" → blank form → fill in + upload photo → Simpan → appears in list
5. Click × → chapter removed
6. Reload page → all changes persisted

- [ ] **Step 4: Commit**
```bash
rtk git add resources/js/Components/invitation/customize/SectionLoveStoryEditor.vue
rtk git add resources/js/Pages/Dashboard/Invitations/Customize.vue
rtk git commit -m "feat: add love story editor to storybook customize page"
```

---

## Task 7: SectionCoupleEditor component + wire

**Files:**
- Create: `resources/js/Components/invitation/customize/SectionCoupleEditor.vue`
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Create the component**

```vue
<!-- resources/js/Components/invitation/customize/SectionCoupleEditor.vue -->
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

const form    = ref({ ...props.modelValue })
const saving  = ref(false)
const error   = ref(null)
const groomPhotoFile  = ref(null)
const bridePhotoFile  = ref(null)

function setGroomPhoto(e) { groomPhotoFile.value = e.target.files[0] || null }
function setBridePhoto(e) { bridePhotoFile.value = e.target.files[0] || null }

async function save() {
    saving.value = true
    error.value  = null
    try {
        const fd = new FormData()
        const fields = ['groom_name','groom_nickname','bride_name','bride_nickname']
        fields.forEach(f => { if (form.value[f] != null) fd.append(f, form.value[f]) })
        if (groomPhotoFile.value) fd.append('groom_photo', groomPhotoFile.value)
        if (bridePhotoFile.value) fd.append('bride_photo', bridePhotoFile.value)

        const res = await axios.post(
            `/api/invitations/${props.invitationId}/details`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        emit('update:modelValue', { ...form.value, ...res.data.data })
    } catch {
        error.value = 'Gagal menyimpan. Coba lagi.'
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div class="space-y-5">
        <!-- Groom -->
        <div class="space-y-2">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Pengantin Pria</p>
            <input v-model="form.groom_name" type="text" placeholder="Nama lengkap"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="form.groom_nickname" type="text" placeholder="Nama panggilan"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <label class="flex items-center gap-2 cursor-pointer">
                <img v-if="form.groom_photo_url" :src="form.groom_photo_url" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                <div v-else class="w-10 h-10 rounded-full bg-stone-100 flex-shrink-0" />
                <span class="text-xs text-stone-500 underline">{{ groomPhotoFile ? groomPhotoFile.name : 'Ganti foto' }}</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="setGroomPhoto" />
            </label>
        </div>

        <!-- Bride -->
        <div class="space-y-2">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Pengantin Wanita</p>
            <input v-model="form.bride_name" type="text" placeholder="Nama lengkap"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="form.bride_nickname" type="text" placeholder="Nama panggilan"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <label class="flex items-center gap-2 cursor-pointer">
                <img v-if="form.bride_photo_url" :src="form.bride_photo_url" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                <div v-else class="w-10 h-10 rounded-full bg-stone-100 flex-shrink-0" />
                <span class="text-xs text-stone-500 underline">{{ bridePhotoFile ? bridePhotoFile.name : 'Ganti foto' }}</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="setBridePhoto" />
            </label>
        </div>

        <p v-if="error" class="text-xs text-red-400">{{ error }}</p>
    </div>
</template>
```

Note: `SectionCoupleEditor` does not have its own "Simpan" button — it is saved via the `ContentModal` footer slot wired in Customize.vue.

- [ ] **Step 2: Wire into Customize.vue**

Import:
```js
import SectionCoupleEditor from '@/Components/invitation/customize/SectionCoupleEditor.vue';
```

The couple editor needs its own ref for the save function. Add inside the `<ContentModal>` block:

```html
<SectionCoupleEditor
    v-else-if="modalSection === 'couple'"
    ref="coupleEditorRef"
    :invitation-id="invitation.id"
    :model-value="details"
    @update:model-value="details = $event"
/>
```

Add a footer save button for the couple modal. Update `ContentModal` usage to include a footer slot only for couple:

```html
<template v-if="modalSection === 'couple'" #footer>
    <button
        type="button"
        @click="coupleEditorRef?.save()"
        class="w-full py-2.5 rounded-xl text-sm font-bold text-white bg-[#92A89C] hover:opacity-90 transition-all"
    >
        Simpan
    </button>
</template>
```

Also expose `save` from `SectionCoupleEditor`:
```js
// In SectionCoupleEditor.vue script setup, add:
defineExpose({ save })
```

Add `coupleEditorRef` ref in Customize.vue:
```js
const coupleEditorRef = ref(null)
```

- [ ] **Step 3: Verify**

1. Open Tentang Kami modal
2. Fields pre-filled with existing groom/bride names
3. Edit name → click Simpan in footer → saved
4. Upload groom photo → save → photo appears
5. Reload → changes persisted
6. Badge in accordion shows "terisi"

- [ ] **Step 4: Commit**
```bash
rtk git add resources/js/Components/invitation/customize/SectionCoupleEditor.vue
rtk git add resources/js/Pages/Dashboard/Invitations/Customize.vue
rtk git commit -m "feat: add couple profile editor to storybook customize page"
```

---

## Task 8: SectionGiftEditor component + wire

**Files:**
- Create: `resources/js/Components/invitation/customize/SectionGiftEditor.vue`
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Create the component**

```vue
<!-- resources/js/Components/invitation/customize/SectionGiftEditor.vue -->
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Object, default: () => ({ accounts: [] }) },
})
const emit   = defineEmits(['update:modelValue'])
const saving = ref(false)
const local  = ref(JSON.parse(JSON.stringify(props.modelValue?.accounts ?? [])))

function addAccount() {
    local.value.push({ bank_name: '', account_number: '', account_name: '' })
}

function removeAccount(index) {
    local.value.splice(index, 1)
    saveAll()
}

async function saveAll() {
    saving.value = true
    try {
        await axios.patch(`/api/invitations/${props.invitationId}/sections/gift`, {
            data:   { accounts: local.value },
            status: local.value.length ? 'complete' : 'empty',
        })
        emit('update:modelValue', { accounts: [...local.value] })
    } catch {
        alert('Gagal menyimpan.')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div class="space-y-3">
        <div v-for="(account, i) in local" :key="i" class="space-y-2 p-3 rounded-xl border border-stone-100 bg-white">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-stone-500">Rekening {{ i + 1 }}</span>
                <button type="button" @click="removeAccount(i)" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
            </div>
            <input v-model="account.bank_name" type="text" placeholder="Nama bank (mis. BCA)"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="account.account_number" type="text" placeholder="Nomor rekening"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="account.account_name" type="text" placeholder="Nama pemilik rekening"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
        </div>

        <p v-if="!local.length" class="text-xs text-stone-400 text-center py-3">Belum ada rekening.</p>

        <button type="button" @click="addAccount"
            class="w-full py-2.5 rounded-xl border-2 border-dashed border-stone-200 text-sm text-stone-500 hover:border-stone-300 hover:bg-stone-50 transition-colors">
            + Tambah Rekening
        </button>
    </div>
</template>
```

- [ ] **Step 2: Wire into Customize.vue**

Import:
```js
import SectionGiftEditor from '@/Components/invitation/customize/SectionGiftEditor.vue';
```

In `<ContentModal>`, add:
```html
<SectionGiftEditor
    v-else-if="modalSection === 'gift'"
    :invitation-id="invitation.id"
    :model-value="sectionsData.gift?.data ?? { accounts: [] }"
    @update:model-value="sectionsData = { ...sectionsData, gift: { ...sectionsData.gift, data: $event } }"
/>
```

Add a footer save button for gift modal. In the `ContentModal` footer slot:

```html
<template v-if="modalSection === 'gift'" #footer>
    <button
        type="button"
        @click="giftEditorRef?.saveAll()"
        class="w-full py-2.5 rounded-xl text-sm font-bold text-white bg-[#92A89C] hover:opacity-90 transition-all"
    >
        Simpan
    </button>
</template>
```

Add `ref="giftEditorRef"` to the `SectionGiftEditor` component and expose `saveAll` from it:
```js
// In SectionGiftEditor.vue, add:
defineExpose({ saveAll })
```

Add in Customize.vue:
```js
const giftEditorRef = ref(null)
```

- [ ] **Step 3: Verify**

1. Open Hadiah modal
2. "+ Tambah Rekening" → form appears
3. Fill BCA, 1234567890, Ahmad Rizky → Simpan → saved
4. Badge in accordion shows "1 rekening"
5. Add second account → Simpan → badge shows "2 rekening"
6. Click Hapus → account removed immediately
7. Reload → changes persisted

- [ ] **Step 4: Final commit**
```bash
rtk git add resources/js/Components/invitation/customize/SectionGiftEditor.vue
rtk git add resources/js/Pages/Dashboard/Invitations/Customize.vue
rtk git commit -m "feat: add gift/bank account editor to storybook customize page"
```

---

## Final Verification

- [ ] Open Storybook customize page — 7 sections in accordion, all with badges
- [ ] All 5 modals open and close correctly
- [ ] RSVP toggle works inline
- [ ] Gallery: upload, delete, reorder all functional
- [ ] Events: edit and save per event functional
- [ ] Love Story: add/edit/delete chapters, with optional photo
- [ ] Tentang Kami: edit names, upload photos
- [ ] Hadiah: add/delete bank accounts
- [ ] Live preview in PhoneMockup reflects changes without page reload
- [ ] Regular template customize page unchanged (all 6 sections, background controls intact)
- [ ] Mobile: all modals open full-screen, inputs usable
