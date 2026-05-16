# Kustomisasi Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a premium-only split-panel Kustomisasi page where users can configure per-section backgrounds (image/video/color) with a live preview of their invitation.

**Architecture:** New `InvitationCustomizeController` stores section backgrounds in `invitation.custom_config.section_backgrounds` (no DB migration needed). Frontend is a split-panel Vue page: left=accordion editor, right=live template preview at 0.45× scale. A new `sectionBg(key)` helper in `useInvitationTemplate` composable resolves user override → template default → null. Templates define their own `SECTION_BG_DEFAULTS`.

**Tech Stack:** Laravel 10, Vue 3 + Inertia.js, Tailwind CSS, Laravel Storage (public disk). Branch: `editorv2`.

---

## File Map

**Create:**
- `app/Http/Controllers/Dashboard/InvitationCustomizeController.php` — 3 actions: show, update, uploadBackground
- `resources/js/Pages/Dashboard/Invitations/Customize.vue` — split-panel page
- `resources/js/Components/invitation/customize/SectionBgControl.vue` — reusable bg type selector (Foto/Video/Warna + inputs + opacity)

**Modify:**
- `routes/web.php` — add 3 customize routes inside `dashboard` middleware group
- `resources/js/Composables/useInvitationTemplate.js` — add `sectionBg(key)`, accept `sectionBgDefaults` in defaults param
- `resources/js/Components/invitation/templates/NusantaraTemplate.vue` — add `SECTION_BG_DEFAULTS`, pass to composable, use `sectionBg('cover')` for cover background
- `resources/js/Components/invitation/templates/PearlTemplate.vue` — same as Nusantara
- `resources/js/Pages/Dashboard/Invitations/Index.vue` — add "Kustomisasi" Link button in action row

---

## Task 1: Routes + InvitationCustomizeController

**Files:**
- Create: `app/Http/Controllers/Dashboard/InvitationCustomizeController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create the controller file**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Support\SectionAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvitationCustomizeController extends Controller
{
    public function show(Request $request, Invitation $invitation): Response
    {
        abort_unless($invitation->user_id === $request->user()->id, 403);

        $invitation->load([
            'details',
            'events'    => fn ($q) => $q->orderBy('sort_order')->orderBy('event_date'),
            'galleries' => fn ($q) => $q->orderBy('sort_order'),
            'music'     => fn ($q) => $q->where('is_default', true)->limit(1),
            'template:id,name,slug,default_config',
        ]);

        $config = array_merge(
            $invitation->template->default_config ?? [],
            $invitation->custom_config             ?? []
        );

        return Inertia::render('Dashboard/Invitations/Customize', [
            'invitation'    => [
                'id'            => $invitation->id,
                'slug'          => $invitation->slug,
                'template_slug' => $invitation->template?->slug,
                'config'        => $config,
                'details'       => $invitation->details ? [
                    'groom_name'      => $invitation->details->groom_name,
                    'groom_nickname'  => $invitation->details->groom_nickname,
                    'bride_name'      => $invitation->details->bride_name,
                    'bride_nickname'  => $invitation->details->bride_nickname,
                    'opening_text'    => $invitation->details->opening_text,
                    'closing_text'    => $invitation->details->closing_text,
                    'cover_photo_url' => $invitation->details->cover_photo_url,
                ] : null,
                'events'    => $invitation->events->map(fn ($e) => [
                    'id'                   => $e->id,
                    'event_name'           => $e->event_name,
                    'event_date'           => $e->event_date?->format('Y-m-d'),
                    'event_date_formatted' => $e->event_date
                        ? Carbon::parse($e->event_date)->locale('id')->translatedFormat('l, d F Y')
                        : null,
                    'start_time'  => $e->start_time ? substr($e->start_time, 0, 5) : null,
                    'end_time'    => $e->end_time   ? substr($e->end_time, 0, 5)   : null,
                    'venue_name'  => $e->venue_name,
                    'maps_url'    => $e->maps_url,
                ])->values(),
                'galleries' => $invitation->galleries->map(fn ($g) => [
                    'id'        => $g->id,
                    'image_url' => $g->image_url,
                ])->values(),
                'music' => $invitation->music->first() ? [
                    'file_url' => $invitation->music->first()->file_url,
                ] : null,
                'sections' => null,
            ],
            'canUsePremium' => SectionAccess::isPremium($request->user()),
        ]);
    }

    public function update(Request $request, Invitation $invitation): JsonResponse
    {
        abort_unless($invitation->user_id === $request->user()->id, 403);

        if (! SectionAccess::isPremium($request->user())) {
            return response()->json(['error' => 'Fitur ini tersedia di paket Premium.'], 403);
        }

        $request->validate([
            'section_backgrounds'          => 'required|array',
            'section_backgrounds.*.type'   => 'nullable|in:image,video,color',
            'section_backgrounds.*.value'  => 'nullable|string|max:1000',
            'section_backgrounds.*.opacity' => 'nullable|numeric|min:0|max:1',
        ]);

        $config                       = $invitation->custom_config ?? [];
        $config['section_backgrounds'] = $request->input('section_backgrounds');

        $invitation->update(['custom_config' => $config]);

        return response()->json(['ok' => true]);
    }

    public function uploadBackground(Request $request, Invitation $invitation, string $key): JsonResponse
    {
        abort_unless($invitation->user_id === $request->user()->id, 403);

        if (! SectionAccess::isPremium($request->user())) {
            return response()->json(['error' => 'Fitur ini tersedia di paket Premium.'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = $request->file('file')->store(
            "invitations/{$invitation->id}/sections/{$key}",
            'public'
        );

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
```

- [ ] **Step 2: Add routes to `routes/web.php`**

Find the block that starts with `// Invitation list & wizard` inside the `dashboard` middleware group and add after the existing invitation routes:

```php
    // Kustomisasi page (premium)
    Route::get( '/invitations/{invitation}/customize',                          [\App\Http\Controllers\Dashboard\InvitationCustomizeController::class, 'show'])->name('invitations.customize');
    Route::post('/invitations/{invitation}/customize',                          [\App\Http\Controllers\Dashboard\InvitationCustomizeController::class, 'update'])->name('invitations.customize.update');
    Route::post('/invitations/{invitation}/sections/{key}/background',          [\App\Http\Controllers\Dashboard\InvitationCustomizeController::class, 'uploadBackground'])->name('invitations.sections.background');
```

- [ ] **Step 3: Verify routes registered**

Run:
```bash
php artisan route:list --name=invitations.customize
```
Expected: 2 rows for `invitations.customize` and `invitations.customize.update`, plus 1 for `invitations.sections.background`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Dashboard/InvitationCustomizeController.php routes/web.php
git commit -m "feat: add InvitationCustomizeController + customize routes"
```

---

## Task 2: sectionBg() in Composable

**Files:**
- Modify: `resources/js/Composables/useInvitationTemplate.js`

- [ ] **Step 1: Add `sectionBgDefaults` to defaults param and `sectionBg()` function**

In `resources/js/Composables/useInvitationTemplate.js`, find the `sectionData(key)` function (around line 56) and add `sectionBg` directly after it:

```js
// After sectionData() function:
function sectionBg(key) {
    const userBg = cfg.value.section_backgrounds?.[key]
    if (userBg?.type && userBg?.value) return userBg
    return defaults.sectionBgDefaults?.[key] ?? null
}
```

- [ ] **Step 2: Add `bgStyle()` helper**

Add after `sectionBg()`:

```js
function bgStyle(bg) {
    if (!bg) return {}
    if (bg.type === 'color') return { backgroundColor: bg.value }
    if (bg.type === 'image') return {
        backgroundImage: `url(${bg.value})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        opacity: String(bg.opacity ?? 0.7),
    }
    return {}
}
```

- [ ] **Step 3: Add to return object**

Find the `return {` block at the bottom of `useInvitationTemplate` and add to the Section group:

```js
// Section
sectionEnabled, sectionData, sectionBg, bgStyle,
```

- [ ] **Step 4: Verify no runtime errors**

Open any existing invitation in the browser. No console errors should appear (the new functions are additive, not breaking).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Composables/useInvitationTemplate.js
git commit -m "feat: add sectionBg() and bgStyle() to useInvitationTemplate composable"
```

---

## Task 3: SECTION_BG_DEFAULTS in Templates

**Files:**
- Modify: `resources/js/Components/invitation/templates/NusantaraTemplate.vue`
- Modify: `resources/js/Components/invitation/templates/PearlTemplate.vue`

### NusantaraTemplate

- [ ] **Step 1: Add SECTION_BG_DEFAULTS constant and destructure sectionBg + bgStyle**

In `NusantaraTemplate.vue`, after the `import` statements and before `const props = defineProps`, add:

```js
const SECTION_BG_DEFAULTS = {
    cover:   { type: 'color', value: '#2C1810' },
    opening: { type: 'color', value: '#1a1008' },
    events:  { type: 'color', value: '#f5efe8' },
    gallery: { type: 'color', value: '#f5efe8' },
    closing: { type: 'color', value: '#2C1810' },
}
```

- [ ] **Step 2: Pass sectionBgDefaults to composable + destructure**

Change the composable call from:
```js
} = useInvitationTemplate(props, {
    galleryLayout: 'grid',
    openingStyle:  'gate',
    revealClass:   'n-visible',
});
```
To:
```js
} = useInvitationTemplate(props, {
    galleryLayout:    'grid',
    openingStyle:     'gate',
    revealClass:      'n-visible',
    sectionBgDefaults: SECTION_BG_DEFAULTS,
});
```

Add `sectionBg, bgStyle` to the destructured return.

- [ ] **Step 3: Use sectionBg on cover section**

Find the cover section in NusantaraTemplate's template. It currently uses `coverPhotoUrl`. Add a background overlay that uses `sectionBg('cover')` when `coverPhotoUrl` is not set:

```html
<!-- In the cover section, find the outermost cover div and add inside it: -->
<div
    v-if="sectionBg('cover')"
    class="absolute inset-0 pointer-events-none"
    :style="bgStyle(sectionBg('cover'))"
/>
```

The overlay goes before content children (inside `position: relative` cover div) so it renders behind content.

### PearlTemplate

- [ ] **Step 4: Add SECTION_BG_DEFAULTS to PearlTemplate**

In `PearlTemplate.vue`, after imports and before `const props`:

```js
const SECTION_BG_DEFAULTS = {
    cover:   { type: 'image', value: '/image/demo-image/bride-groom.png', opacity: 0.75 },
    opening: { type: 'color', value: '#0f0e0c' },
    events:  { type: 'color', value: '#faf9f7' },
    gallery: { type: 'color', value: '#f5f0e8' },
    closing: { type: 'image', value: '/image/demo-image/bride-groom.png', opacity: 0.6 },
}
```

- [ ] **Step 5: Pass sectionBgDefaults to composable + destructure sectionBg, bgStyle**

Same pattern as NusantaraTemplate: add `sectionBgDefaults: SECTION_BG_DEFAULTS` to composable call, add `sectionBg, bgStyle` to destructure.

- [ ] **Step 6: Gate background uses sectionBg('cover')**

In PearlTemplate, the gate `<div class="pearl-gate">` has a `pearl-gate__bg` div that reads `coverPhotoUrl`. Update it to prefer `sectionBg('cover')`:

```html
<!-- Replace the existing pearl-gate__bg div with: -->
<div
    v-if="sectionBg('cover') || coverPhotoUrl"
    class="pearl-gate__bg"
    :style="sectionBg('cover')
        ? bgStyle(sectionBg('cover'))
        : { backgroundImage: `url(${coverPhotoUrl})`, backgroundSize: 'cover', backgroundPosition: 'center' }"
/>
```

- [ ] **Step 7: Verify both templates render correctly**

Visit `/templates/nusantara/demo` and `/templates/pearl/demo` in browser. Both should render identically to before (no visual regression — SECTION_BG_DEFAULTS match what was hardcoded).

- [ ] **Step 8: Commit**

```bash
git add resources/js/Components/invitation/templates/NusantaraTemplate.vue resources/js/Components/invitation/templates/PearlTemplate.vue
git commit -m "feat: add SECTION_BG_DEFAULTS and sectionBg() usage to Nusantara and Pearl templates"
```

---

## Task 4: SectionBgControl Component

**Files:**
- Create: `resources/js/Components/invitation/customize/SectionBgControl.vue`

- [ ] **Step 1: Create the component**

```vue
<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Object, default: () => ({ type: 'color', value: '#ffffff', opacity: 0.7 }) },
    sectionKey: { type: String, required: true },
    invitationId: { type: String, required: true },
    uploading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'upload']);

const bg = computed(() => props.modelValue ?? { type: 'color', value: '#ffffff', opacity: 0.7 });

function setType(type) {
    emit('update:modelValue', { ...bg.value, type });
}
function setValue(value) {
    emit('update:modelValue', { ...bg.value, value });
}
function setOpacity(val) {
    emit('update:modelValue', { ...bg.value, opacity: parseFloat(val) });
}
function onFileInput(e) {
    const file = e.target.files[0];
    if (file) emit('upload', file);
}

const inputClass = 'w-full px-3 py-2 rounded-lg border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-[#92A89C] transition-colors';
</script>

<template>
    <div class="space-y-3">
        <!-- Type selector -->
        <div class="flex gap-1.5">
            <button
                v-for="opt in [{ key: 'image', label: 'Foto' }, { key: 'video', label: 'Video' }, { key: 'color', label: 'Warna' }]"
                :key="opt.key"
                type="button"
                @click="setType(opt.key)"
                :class="[
                    'flex-1 py-1.5 rounded-lg text-xs font-medium border transition-all',
                    bg.type === opt.key
                        ? 'bg-[#92A89C] text-white border-[#92A89C]'
                        : 'text-stone-500 border-stone-200 hover:border-[#92A89C]/50'
                ]"
            >
                {{ opt.label }}
            </button>
        </div>

        <!-- Image upload -->
        <template v-if="bg.type === 'image'">
            <div v-if="bg.value" class="relative rounded-lg overflow-hidden h-24">
                <img :src="bg.value" class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                    <label class="cursor-pointer text-white text-xs font-medium">
                        Ganti Foto
                        <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" @change="onFileInput" />
                    </label>
                </div>
            </div>
            <label v-else :class="['flex flex-col items-center justify-center h-20 rounded-lg border-2 border-dashed border-stone-200 cursor-pointer hover:border-[#92A89C]/50 transition-colors', uploading ? 'opacity-60 pointer-events-none' : '']">
                <span class="text-xs text-stone-400">{{ uploading ? 'Mengupload...' : 'Pilih Foto' }}</span>
                <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" @change="onFileInput" :disabled="uploading" />
            </label>
            <!-- Opacity slider -->
            <div class="space-y-1">
                <div class="flex justify-between text-xs text-stone-500">
                    <span>Opacity</span>
                    <span>{{ Math.round((bg.opacity ?? 0.7) * 100) }}%</span>
                </div>
                <input type="range" min="0.1" max="1" step="0.05"
                    :value="bg.opacity ?? 0.7"
                    @input="setOpacity($event.target.value)"
                    class="w-full accent-[#92A89C]"
                />
            </div>
        </template>

        <!-- Video URL -->
        <template v-if="bg.type === 'video'">
            <input
                type="url"
                :value="bg.value"
                @input="setValue($event.target.value)"
                placeholder="https://youtube.com/watch?v=..."
                :class="inputClass"
            />
            <div v-if="bg.value" class="rounded-lg overflow-hidden aspect-video">
                <iframe
                    :src="`https://www.youtube.com/embed/${bg.value.match(/(?:v=|youtu\.be\/)([^&?/\s]+)/)?.[1]}?autoplay=0&mute=1`"
                    class="w-full h-full"
                    frameborder="0"
                    allowfullscreen
                />
            </div>
            <!-- Opacity slider for video -->
            <div class="space-y-1">
                <div class="flex justify-between text-xs text-stone-500">
                    <span>Opacity</span>
                    <span>{{ Math.round((bg.opacity ?? 0.7) * 100) }}%</span>
                </div>
                <input type="range" min="0.1" max="1" step="0.05"
                    :value="bg.opacity ?? 0.7"
                    @input="setOpacity($event.target.value)"
                    class="w-full accent-[#92A89C]"
                />
            </div>
        </template>

        <!-- Color picker -->
        <template v-if="bg.type === 'color'">
            <div class="flex gap-2 items-center">
                <input type="color"
                    :value="bg.value || '#ffffff'"
                    @input="setValue($event.target.value)"
                    class="w-10 h-10 rounded-lg border border-stone-200 cursor-pointer p-0.5"
                />
                <input type="text"
                    :value="bg.value || '#ffffff'"
                    @input="setValue($event.target.value)"
                    placeholder="#ffffff"
                    :class="[inputClass, 'flex-1']"
                    maxlength="7"
                />
            </div>
        </template>
    </div>
</template>
```

- [ ] **Step 2: Verify component renders**

Temporarily import and render it anywhere in Dashboard. Confirm all 3 type modes render correctly (Foto / Video / Warna switch, inputs appear).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/invitation/customize/SectionBgControl.vue
git commit -m "feat: add SectionBgControl component for background type selector"
```

---

## Task 5: Customize.vue — Editor Panel + Save

**Files:**
- Create: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Create the page (editor panel only, preview added in Task 6)**

```vue
<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SectionBgControl from '@/Components/invitation/customize/SectionBgControl.vue';

const props = defineProps({
    invitation:    { type: Object,  required: true },
    canUsePremium: { type: Boolean, default: false },
});

// ── Local form: mirrors config.section_backgrounds ────────────────────────
const form = ref(
    JSON.parse(JSON.stringify(props.invitation.config?.section_backgrounds ?? {}))
);

const uploadingKey = ref(null); // which section is currently uploading
const saveStatus   = ref('saved'); // 'saved' | 'saving' | 'error'
const activeKey    = ref('cover');

const groomName = computed(() => props.invitation.details?.groom_name ?? '—');
const brideName = computed(() => props.invitation.details?.bride_name ?? '—');

// ── Sections config ───────────────────────────────────────────────────────
const SECTIONS = [
    { key: 'cover',   label: 'Cover',   icon: '🖼️' },
    { key: 'opening', label: 'Opening', icon: '✉️' },
    { key: 'events',  label: 'Acara',   icon: '📅' },
    { key: 'gallery', label: 'Galeri',  icon: '🖼️' },
    { key: 'music',   label: 'Musik',   icon: '🎵' },
    { key: 'closing', label: 'Penutup', icon: '💌' },
];

// ── Background change handler ─────────────────────────────────────────────
function onBgChange(key, bg) {
    form.value = { ...form.value, [key]: bg };
}

// ── File upload ───────────────────────────────────────────────────────────
async function uploadBg(sectionKey, file) {
    uploadingKey.value = sectionKey;
    try {
        const fd = new FormData();
        fd.append('file', file);
        const res = await axios.post(
            `/dashboard/invitations/${props.invitation.id}/sections/${sectionKey}/background`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        onBgChange(sectionKey, {
            ...(form.value[sectionKey] ?? {}),
            type:  'image',
            value: res.data.url,
        });
    } catch {
        alert('Upload gagal. Coba lagi.');
    } finally {
        uploadingKey.value = null;
    }
}

// ── Save ──────────────────────────────────────────────────────────────────
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

// Auto-save 1.5s after last change
let autoSaveTimer = null;
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(save, 1500);
}
</script>

<template>
    <DashboardLayout title="Kustomisasi Undangan">
        <!-- Premium lock overlay -->
        <div v-if="!canUsePremium" class="min-h-screen flex items-center justify-center p-8">
            <div class="max-w-sm text-center space-y-4">
                <div class="text-4xl">🔒</div>
                <h2 class="text-lg font-semibold text-stone-800">Fitur Premium</h2>
                <p class="text-sm text-stone-500">Kustomisasi tampilan per-section tersedia di paket Premium.</p>
                <Link href="/dashboard/paket" class="inline-block px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-[#92A89C] hover:opacity-90 transition-opacity">
                    Upgrade ke Premium
                </Link>
            </div>
        </div>

        <!-- Main content (premium users) -->
        <div v-else class="flex h-full min-h-screen">

            <!-- ── Left: Editor ──────────────────────────────────── -->
            <div class="w-full lg:w-[420px] flex-shrink-0 flex flex-col border-r border-stone-100 bg-white">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                    <div>
                        <h1 class="text-sm font-bold text-stone-800">Kustomisasi</h1>
                        <p class="text-xs text-stone-400 mt-0.5">{{ groomName }} & {{ brideName }}</p>
                    </div>
                    <Link
                        :href="`/${invitation.slug}`"
                        target="_blank"
                        class="text-xs px-3 py-1.5 rounded-lg border border-stone-200 text-stone-500 hover:bg-stone-50 transition-colors lg:hidden"
                    >
                        Lihat Preview
                    </Link>
                </div>

                <!-- Section accordion -->
                <div class="flex-1 overflow-y-auto divide-y divide-stone-50">
                    <div v-for="section in SECTIONS" :key="section.key">
                        <button
                            type="button"
                            @click="activeKey = activeKey === section.key ? null : section.key"
                            class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-stone-50 transition-colors"
                        >
                            <span class="text-sm font-medium text-stone-700">{{ section.label }}</span>
                            <svg
                                :class="['w-4 h-4 text-stone-400 transition-transform', activeKey === section.key ? 'rotate-180' : '']"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div v-if="activeKey === section.key" class="px-5 pb-5 space-y-4 bg-stone-50/50">
                            <!-- Background control (all sections except music) -->
                            <template v-if="section.key !== 'music'">
                                <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider pt-2">Background</p>
                                <SectionBgControl
                                    :model-value="form[section.key]"
                                    :section-key="section.key"
                                    :invitation-id="invitation.id"
                                    :uploading="uploadingKey === section.key"
                                    @update:model-value="(bg) => { onBgChange(section.key, bg); scheduleAutoSave(); }"
                                    @upload="(file) => uploadBg(section.key, file).then(scheduleAutoSave)"
                                />
                            </template>

                            <!-- Music upload -->
                            <template v-if="section.key === 'music'">
                                <p class="text-xs text-stone-500">Upload file musik (MP3, maks 10MB). Gunakan fitur upload musik di halaman edit undangan.</p>
                                <Link
                                    :href="route('editor.invitations.show', invitation.id)"
                                    class="inline-block text-xs px-3 py-2 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                                >
                                    Buka Editor Musik →
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer: Save -->
                <div class="px-5 py-4 border-t border-stone-100 flex items-center gap-3">
                    <button
                        type="button"
                        @click="save"
                        :disabled="saveStatus === 'saving'"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-[#92A89C] hover:opacity-90 disabled:opacity-60 transition-all"
                    >
                        {{ saveStatus === 'saving' ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <span v-if="saveStatus === 'saved'" class="text-xs text-emerald-500 font-medium">✓ Tersimpan</span>
                    <span v-if="saveStatus === 'error'"  class="text-xs text-red-400 font-medium">Gagal simpan</span>
                </div>
            </div>

            <!-- ── Right: Preview (desktop only, added in Task 6) ── -->
            <div class="hidden lg:flex flex-1 items-start justify-center bg-stone-100 overflow-hidden p-8">
                <p class="text-stone-400 text-sm">Preview akan tampil di sini</p>
            </div>

        </div>

        <!-- Mobile: floating preview button -->
        <a
            :href="`/${invitation.slug}`"
            target="_blank"
            class="lg:hidden fixed bottom-6 right-4 z-50 px-4 py-2.5 rounded-full shadow-lg text-xs font-bold text-white bg-[#92A89C]"
        >
            Lihat Preview
        </a>
    </DashboardLayout>
</template>
```

- [ ] **Step 2: Verify page loads**

Visit `http://127.0.0.1:8000/dashboard/invitations/{id}/customize` (replace `{id}` with a real invitation ID). Page should load with accordion sections. Premium lock should show for free users.

- [ ] **Step 3: Test save**

Open Cover section, set a background color, click Simpan. Check DB: `php artisan tinker --execute="echo json_encode(\App\Models\Invitation::first()->custom_config);"` — should show `section_backgrounds.cover`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Dashboard/Invitations/Customize.vue
git commit -m "feat: add Customize.vue page with accordion editor and save"
```

---

## Task 6: Live Preview Panel

**Files:**
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`

- [ ] **Step 1: Add previewInvitation computed and previewTemplate**

In `<script setup>`, add after the existing imports:

```js
import { TEMPLATE_MAP } from '@/Components/invitation/templates/registry';

const previewTemplate = computed(() => TEMPLATE_MAP[props.invitation.template_slug] ?? null);

const previewInvitation = computed(() => ({
    ...props.invitation,
    config: {
        ...props.invitation.config,
        section_backgrounds: form.value,
    },
}));
```

- [ ] **Step 2: Replace the preview placeholder div**

Replace:
```html
<!-- ── Right: Preview (desktop only, added in Task 6) ── -->
<div class="hidden lg:flex flex-1 items-start justify-center bg-stone-100 overflow-hidden p-8">
    <p class="text-stone-400 text-sm">Preview akan tampil di sini</p>
</div>
```

With:
```html
<!-- ── Right: Live preview (desktop only) ────────────── -->
<div class="hidden lg:flex flex-1 bg-stone-100 overflow-y-auto">
    <div class="preview-panel-wrapper">
        <component
            v-if="previewTemplate"
            :is="previewTemplate"
            :invitation="previewInvitation"
            :is-demo="true"
            :auto-open="true"
        />
        <div v-else class="flex items-center justify-center h-full text-stone-400 text-sm">
            Template tidak ditemukan
        </div>
    </div>
</div>
```

- [ ] **Step 3: Add preview panel CSS**

In the `<style>` block (add one if not present):

```css
<style scoped>
.preview-panel-wrapper {
    width: 390px;           /* simulate mobile width */
    min-height: 100%;
    transform-origin: top left;
    margin: 0 auto;
    background: white;
    box-shadow: 0 0 40px rgba(0,0,0,0.12);
}
</style>
```

- [ ] **Step 4: Verify live preview updates**

Open Customize page on desktop. Right panel should show the invitation template rendered. Change a background color in the editor — preview should update in real-time without save.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Dashboard/Invitations/Customize.vue
git commit -m "feat: add live preview panel to Customize page"
```

---

## Task 7: Kustomisasi Button on Index Page

**Files:**
- Modify: `resources/js/Pages/Dashboard/Invitations/Index.vue`

- [ ] **Step 1: Add "Kustomisasi" Link in the invitation actions row**

In `Index.vue`, find the actions `<div class="flex gap-2">` for each invitation card (around line 188). Add a "Kustomisasi" link after the "Edit" link:

```html
<Link
    :href="route('dashboard.invitations.customize', inv.id)"
    class="flex-1 text-center py-2 rounded-xl text-xs font-semibold border border-[#92A89C]/50 text-[#73877C] hover:bg-[#92A89C]/10 transition-colors"
    title="Kustomisasi tampilan"
>
    Kustomisasi
</Link>
```

- [ ] **Step 2: Verify button appears and navigates**

Open the dashboard invitations list. Each card should show a "Kustomisasi" button. Clicking it should navigate to `/dashboard/invitations/{id}/customize`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Dashboard/Invitations/Index.vue
git commit -m "feat: add Kustomisasi button to invitations index page"
```

---

## Self-Review Checklist

**Spec coverage:**
- [x] Premium gate — Task 5 (lock overlay) + Task 1 (403 in controller)
- [x] GET/POST/uploadBackground routes — Task 1
- [x] All 6 section panels — Task 5 (accordion)
- [x] Background image upload — Task 4 (SectionBgControl) + Task 5 (uploadBg)
- [x] YouTube URL input + preview — Task 4
- [x] Color picker — Task 4
- [x] Opacity slider — Task 4
- [x] Google Maps URL — Note: not yet wired (maps_url is on the event model, not section background). Deferred — add in next iteration.
- [x] Gallery photos upload — Note: deferred to next iteration (uses existing syncGallery endpoint, complex multi-file UX)
- [x] Save persists — Task 5
- [x] sectionBg() composable — Task 2
- [x] Pearl + Nusantara template updates — Task 3
- [x] Live preview desktop — Task 6
- [x] Mobile preview button — Task 5

**Gaps noted (defer to next iteration):**
- Google Maps URL input per event (needs separate event update endpoint)
- Gallery multi-photo upload (needs complex drag-and-drop UX, use existing wizard for now)
- Music section (redirects to editor — acceptable for v1)
