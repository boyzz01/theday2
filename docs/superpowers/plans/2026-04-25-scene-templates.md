# Scene Templates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bangun 3 interactive scene templates (Beach, Garden, Night Sky) dengan paradigma illustrated world + clickable hotspot + modal browser-chrome.

**Architecture:** Satu `SceneTemplate.vue` base component menerima `sceneConfig` prop. Tiga wrapper tipis inject config masing-masing. Modal, hotspot, dan guestbook bar adalah komponen terpisah yang reusable. Semua data undangan dari `useInvitationTemplate` tanpa modifikasi.

**Tech Stack:** Vue 3 Composition API (`<script setup>`), Tailwind CSS, `useInvitationTemplate` composable existing.

---

### Task 1: Scaffold direktori + placeholder assets

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/` (dir)
- Create: `resources/js/Components/invitation/templates/scene/content/` (dir)
- Create: `resources/js/Components/invitation/templates/scene/configs/` (dir)
- Create: `public/images/templates/beach/scene.webp` (placeholder)
- Create: `public/images/templates/garden/scene.webp` (placeholder)
- Create: `public/images/templates/night-sky/scene.webp` (placeholder)

- [ ] **Step 1: Buat direktori**

```bash
mkdir -p resources/js/Components/invitation/templates/scene/content
mkdir -p resources/js/Components/invitation/templates/scene/configs
mkdir -p public/images/templates/beach
mkdir -p public/images/templates/garden
mkdir -p public/images/templates/night-sky
```

- [ ] **Step 2: Buat placeholder SVG untuk tiap tema**

Simpan sebagai `public/images/templates/beach/scene.webp` — ini placeholder sementara sebelum aset AI-generated tersedia. Karena browser tidak bisa render SVG yang di-rename .webp, buat file HTML saja untuk test:

```bash
# Placeholder: gunakan file SVG yang valid (rename saat aset real tersedia)
# Untuk dev: cukup buat file kosong, SceneTemplate punya CSS fallback
touch public/images/templates/beach/scene.webp
touch public/images/templates/garden/scene.webp
touch public/images/templates/night-sky/scene.webp
```

> **Catatan:** Koordinat hotspot di config Task 2 adalah nilai placeholder. Setelah aset ilustrasi AI final ditempatkan, koordinat x/y tiap hotspot harus di-tune manual dengan membuka undangan di browser dan mengukur posisi objek.

- [ ] **Step 3: Commit scaffold**

```bash
git add resources/js/Components/invitation/templates/scene/ public/images/templates/
git commit -m "chore: scaffold scene templates directory structure and placeholder assets"
```

---

### Task 2: Scene configs (Beach, Garden, Night Sky)

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/configs/BeachConfig.js`
- Create: `resources/js/Components/invitation/templates/scene/configs/GardenConfig.js`
- Create: `resources/js/Components/invitation/templates/scene/configs/NightSkyConfig.js`

- [ ] **Step 1: Buat BeachConfig.js**

```js
// resources/js/Components/invitation/templates/scene/configs/BeachConfig.js
export default {
    background: '/images/templates/beach/scene.webp',
    fallbackBg: 'linear-gradient(180deg, #87CEEB 0%, #F0E68C 60%, #DEB887 100%)',
    hotspots: [
        { id: 'gallery',    x: 18, y: 12, label: 'Gallery',      section: 'gallery' },
        { id: 'date_venue', x: 55, y: 10, label: 'Date & Venue', section: 'events' },
        { id: 'about',      x: 45, y: 38, label: 'About Us',     section: 'couple' },
        { id: 'love_story', x: 52, y: 55, label: 'Love Story',   section: 'love_story' },
        { id: 'rsvp',       x: 72, y: 50, label: 'RSVP',         section: 'rsvp' },
        { id: 'gift',       x: 68, y: 72, label: 'Gift',         section: 'gift' },
    ],
}
```

- [ ] **Step 2: Buat GardenConfig.js**

```js
// resources/js/Components/invitation/templates/scene/configs/GardenConfig.js
export default {
    background: '/images/templates/garden/scene.webp',
    fallbackBg: 'linear-gradient(180deg, #E8F5E9 0%, #C8E6C9 40%, #A5D6A7 100%)',
    hotspots: [
        { id: 'gallery',    x: 20, y: 15, label: 'Gallery',      section: 'gallery' },
        { id: 'date_venue', x: 60, y: 12, label: 'Date & Venue', section: 'events' },
        { id: 'about',      x: 50, y: 40, label: 'About Us',     section: 'couple' },
        { id: 'love_story', x: 30, y: 55, label: 'Love Story',   section: 'love_story' },
        { id: 'rsvp',       x: 70, y: 52, label: 'RSVP',         section: 'rsvp' },
        { id: 'gift',       x: 65, y: 70, label: 'Gift',         section: 'gift' },
    ],
}
```

- [ ] **Step 3: Buat NightSkyConfig.js**

```js
// resources/js/Components/invitation/templates/scene/configs/NightSkyConfig.js
export default {
    background: '/images/templates/night-sky/scene.webp',
    fallbackBg: 'linear-gradient(180deg, #0D1B2A 0%, #1B2A4A 50%, #2E3F6F 100%)',
    hotspots: [
        { id: 'gallery',    x: 22, y: 18, label: 'Gallery',      section: 'gallery' },
        { id: 'date_venue', x: 58, y: 14, label: 'Date & Venue', section: 'events' },
        { id: 'about',      x: 48, y: 42, label: 'About Us',     section: 'couple' },
        { id: 'love_story', x: 28, y: 58, label: 'Love Story',   section: 'love_story' },
        { id: 'rsvp',       x: 70, y: 54, label: 'RSVP',         section: 'rsvp' },
        { id: 'gift',       x: 62, y: 68, label: 'Gift',         section: 'gift' },
    ],
}
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/configs/
git commit -m "feat: add scene configs for Beach, Garden, and Night Sky templates"
```

---

### Task 3: SceneHotspot.vue

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/SceneHotspot.vue`

- [ ] **Step 1: Buat SceneHotspot.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/SceneHotspot.vue -->
<script setup>
const props = defineProps({
    hotspot: { type: Object, required: true },
    index:   { type: Number, default: 0 },
})
const emit = defineEmits(['click'])
</script>

<template>
    <button
        class="hotspot-btn"
        :style="{
            position:         'absolute',
            left:             props.hotspot.x + '%',
            top:              props.hotspot.y + '%',
            transform:        'translate(-50%, -50%)',
            animationDelay:   (props.index * 0.12) + 's',
        }"
        @click="emit('click', props.hotspot.section)"
    >
        {{ props.hotspot.label }}
    </button>
</template>

<style scoped>
.hotspot-btn {
    background:       rgba(255, 255, 255, 0.15);
    border:           1.5px solid rgba(255, 255, 255, 0.65);
    border-radius:    999px;
    padding:          4px 12px;
    font-size:        11px;
    font-weight:      600;
    color:            #fff;
    letter-spacing:   0.04em;
    backdrop-filter:  blur(4px);
    -webkit-backdrop-filter: blur(4px);
    cursor:           pointer;
    white-space:      nowrap;
    box-shadow:
        0 0 8px  rgba(255, 255, 255, 0.55),
        0 0 20px rgba(100, 220, 255, 0.4);
    animation: pulse-glow 2.2s ease-in-out infinite;
}

.hotspot-btn:active {
    transform: translate(-50%, -50%) scale(0.95);
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow:
            0 0 8px  rgba(255, 255, 255, 0.55),
            0 0 20px rgba(100, 220, 255, 0.40);
    }
    50% {
        box-shadow:
            0 0 14px rgba(255, 255, 255, 0.90),
            0 0 32px rgba(100, 220, 255, 0.75);
    }
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/SceneHotspot.vue
git commit -m "feat: add SceneHotspot component with neon glow animation"
```

---

### Task 4: SceneModal.vue

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/SceneModal.vue`

- [ ] **Step 1: Buat SceneModal.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/SceneModal.vue -->
<script setup>
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title:      { type: String,  required: true },
})
const emit = defineEmits(['update:modelValue'])

function close() {
    emit('update:modelValue', false)
}

let touchStartY = 0

function onTouchStart(e) {
    touchStartY = e.touches[0].clientY
}

function onTouchEnd(e) {
    if (e.changedTouches[0].clientY - touchStartY > 80) close()
}
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition name="backdrop-fade">
            <div
                v-if="modelValue"
                class="modal-backdrop"
                @click="close"
            />
        </Transition>

        <!-- Sheet -->
        <Transition name="slide-up">
            <div
                v-if="modelValue"
                class="modal-sheet"
                @touchstart="onTouchStart"
                @touchend="onTouchEnd"
            >
                <!-- Browser chrome (dekoratif) -->
                <div class="browser-chrome">
                    <span class="dot dot-red" />
                    <span class="dot dot-yellow" />
                    <span class="dot dot-green" />
                    <span class="url-bar">theday.id</span>
                </div>

                <!-- Nav bar -->
                <div class="nav-bar">
                    <span class="modal-title">{{ title }}</span>
                    <button class="close-btn" @click="close" aria-label="Tutup">✕</button>
                </div>

                <!-- Scrollable content -->
                <div class="modal-content">
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-backdrop {
    position:   fixed;
    inset:      0;
    background: rgba(0, 0, 0, 0.55);
    z-index:    40;
}

.modal-sheet {
    position:         fixed;
    bottom:           0;
    left:             50%;
    transform:        translateX(-50%);
    width:            100%;
    max-width:        480px;
    max-height:       82vh;
    background:       #fff;
    border-radius:    16px 16px 0 0;
    z-index:          50;
    display:          flex;
    flex-direction:   column;
    overflow:         hidden;
}

.browser-chrome {
    display:         flex;
    align-items:     center;
    gap:             6px;
    padding:         8px 14px 6px;
    background:      #f0f0f0;
    border-bottom:   1px solid #ddd;
}

.dot {
    width:         10px;
    height:        10px;
    border-radius: 50%;
    display:       inline-block;
}
.dot-red    { background: #FF5F57; }
.dot-yellow { background: #FEBC2E; }
.dot-green  { background: #28C840; }

.url-bar {
    margin-left:   8px;
    font-size:     11px;
    color:         #888;
    background:    #e4e4e4;
    border-radius: 4px;
    padding:       2px 10px;
    flex:          1;
    text-align:    center;
}

.nav-bar {
    display:         flex;
    align-items:     center;
    justify-content: space-between;
    padding:         10px 16px;
    border-bottom:   1px solid #eee;
    flex-shrink:     0;
}

.modal-title {
    font-size:   15px;
    font-weight: 600;
    color:       #333;
}

.close-btn {
    background: none;
    border:     none;
    font-size:  16px;
    color:      #666;
    cursor:     pointer;
    padding:    4px 8px;
}

.modal-content {
    overflow-y:  auto;
    flex:        1;
    padding:     16px;
    -webkit-overflow-scrolling: touch;
}

/* Transitions */
.backdrop-fade-enter-active,
.backdrop-fade-leave-active { transition: opacity 0.25s ease; }
.backdrop-fade-enter-from,
.backdrop-fade-leave-to     { opacity: 0; }

.slide-up-enter-active,
.slide-up-leave-active { transition: transform 0.3s ease; }
.slide-up-enter-from,
.slide-up-leave-to     { transform: translateX(-50%) translateY(100%); }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/SceneModal.vue
git commit -m "feat: add SceneModal with browser-chrome aesthetic and slide-up animation"
```

---

### Task 5: SceneGuestbook.vue

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/SceneGuestbook.vue`

- [ ] **Step 1: Buat SceneGuestbook.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/SceneGuestbook.vue -->
<script setup>
defineProps({
    open: { type: Boolean, default: true },
})
const emit = defineEmits(['open-form', 'open-list', 'toggle'])
</script>

<template>
    <div class="guestbook-bar" :class="{ 'bar-collapsed': !open }">
        <div class="bar-actions">
            <button class="action-btn" @click="emit('open-form')" aria-label="Tulis ucapan">
                +
            </button>
            <button class="action-btn" @click="emit('open-list')" aria-label="Lihat ucapan">
                ↑
            </button>
        </div>
        <button class="toggle-btn" @click="emit('toggle')">
            {{ open ? 'Sembunyikan Ucapan' : 'Tampilkan Ucapan' }}
        </button>
    </div>
</template>

<style scoped>
.guestbook-bar {
    position:        fixed;
    bottom:          0;
    left:            50%;
    transform:       translateX(-50%);
    width:           100%;
    max-width:       480px;
    background:      rgba(255, 235, 230, 0.95);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-top:      1px solid rgba(0, 0, 0, 0.08);
    display:         flex;
    align-items:     center;
    gap:             8px;
    padding:         10px 16px;
    z-index:         30;
    transition:      transform 0.3s ease;
}

.bar-collapsed {
    transform: translateX(-50%) translateY(100%);
}

.bar-actions {
    display: flex;
    gap:     6px;
}

.action-btn {
    width:         36px;
    height:        36px;
    border-radius: 50%;
    background:    #fff;
    border:        1px solid rgba(0,0,0,0.12);
    font-size:     18px;
    line-height:   1;
    cursor:        pointer;
    display:       flex;
    align-items:   center;
    justify-content: center;
    box-shadow:    0 1px 4px rgba(0,0,0,0.1);
}

.toggle-btn {
    background: none;
    border:     none;
    font-size:  13px;
    color:      #555;
    cursor:     pointer;
    flex:       1;
    text-align: left;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/SceneGuestbook.vue
git commit -m "feat: add SceneGuestbook bottom bar component"
```

---

### Task 6: Content components — Gallery & Events

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/content/SceneContentGallery.vue`
- Create: `resources/js/Components/invitation/templates/scene/content/SceneContentEvents.vue`

- [ ] **Step 1: Buat SceneContentGallery.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/content/SceneContentGallery.vue -->
<script setup>
defineProps({
    galleries: { type: Array, default: () => [] },
})
</script>

<template>
    <div>
        <p v-if="!galleries.length" class="text-center text-gray-400 py-8">
            Belum ada foto.
        </p>
        <div v-else class="grid grid-cols-2 gap-2">
            <div v-for="img in galleries" :key="img.id" class="rounded-lg overflow-hidden">
                <img
                    :src="img.file_url"
                    :alt="img.caption ?? ''"
                    loading="lazy"
                    class="w-full h-40 object-cover"
                />
                <p v-if="img.caption" class="text-xs text-gray-500 text-center py-1 px-1">
                    {{ img.caption }}
                </p>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Buat SceneContentEvents.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/content/SceneContentEvents.vue -->
<script setup>
defineProps({
    events: { type: Array, default: () => [] },
})
</script>

<template>
    <div class="space-y-4">
        <p v-if="!events.length" class="text-center text-gray-400 py-8">
            Belum ada jadwal acara.
        </p>
        <div
            v-for="event in events"
            :key="event.id"
            class="border border-gray-100 rounded-xl p-4 bg-gray-50"
        >
            <h3 class="font-semibold text-gray-800 text-base mb-2">{{ event.event_name }}</h3>
            <p class="text-sm text-gray-600">📅 {{ event.event_date_formatted }}</p>
            <p v-if="event.start_time" class="text-sm text-gray-600">🕐 {{ event.start_time }}</p>
            <p class="text-sm text-gray-600 mt-1">📍 {{ event.location }}</p>
            <a
                v-if="event.maps_url"
                :href="event.maps_url"
                target="_blank"
                rel="noopener"
                class="inline-block mt-3 text-sm text-blue-500 underline"
            >
                Buka di Maps →
            </a>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/content/SceneContentGallery.vue resources/js/Components/invitation/templates/scene/content/SceneContentEvents.vue
git commit -m "feat: add SceneContentGallery and SceneContentEvents modal content components"
```

---

### Task 7: Content components — Couple & LoveStory

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/content/SceneContentCouple.vue`
- Create: `resources/js/Components/invitation/templates/scene/content/SceneContentLoveStory.vue`

- [ ] **Step 1: Buat SceneContentCouple.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/content/SceneContentCouple.vue -->
<script setup>
defineProps({
    groomName: { type: String, default: '' },
    brideName: { type: String, default: '' },
    details:   { type: Object, default: () => ({}) },
})
</script>

<template>
    <div class="text-center space-y-6 pb-4">
        <p v-if="details.opening_text" class="text-sm text-gray-500 italic px-2">
            {{ details.opening_text }}
        </p>

        <div class="space-y-4">
            <!-- Groom -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h3 class="font-semibold text-lg text-gray-800">{{ groomName }}</h3>
                <p v-if="details.groom_nickname" class="text-sm text-gray-500">
                    "{{ details.groom_nickname }}"
                </p>
                <p v-if="details.groom_parents" class="text-sm text-gray-600 mt-1">
                    Putra dari {{ details.groom_parents }}
                </p>
            </div>

            <div class="text-2xl text-gray-300">&</div>

            <!-- Bride -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h3 class="font-semibold text-lg text-gray-800">{{ brideName }}</h3>
                <p v-if="details.bride_nickname" class="text-sm text-gray-500">
                    "{{ details.bride_nickname }}"
                </p>
                <p v-if="details.bride_parents" class="text-sm text-gray-600 mt-1">
                    Putri dari {{ details.bride_parents }}
                </p>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Buat SceneContentLoveStory.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/content/SceneContentLoveStory.vue -->
<script setup>
defineProps({
    stories: { type: Array, default: () => [] },
})
</script>

<template>
    <div>
        <p v-if="!stories.length" class="text-center text-gray-400 py-8">
            Belum ada kisah cinta.
        </p>
        <div v-else class="relative pl-6 space-y-6">
            <!-- Timeline line -->
            <div class="absolute left-2 top-2 bottom-2 w-0.5 bg-gray-200" />

            <div v-for="(story, i) in stories" :key="i" class="relative">
                <!-- Dot -->
                <div class="absolute -left-4 top-1 w-3 h-3 rounded-full bg-pink-400 border-2 border-white shadow" />

                <p class="text-xs text-gray-400 mb-0.5">{{ story.date }}</p>
                <h4 class="font-semibold text-gray-800 text-sm">{{ story.title }}</h4>
                <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{{ story.description }}</p>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/content/SceneContentCouple.vue resources/js/Components/invitation/templates/scene/content/SceneContentLoveStory.vue
git commit -m "feat: add SceneContentCouple and SceneContentLoveStory modal content components"
```

---

### Task 8: Content components — RSVP, Gift & Guestbook

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/content/SceneContentRsvp.vue`
- Create: `resources/js/Components/invitation/templates/scene/content/SceneContentGift.vue`
- Create: `resources/js/Components/invitation/templates/scene/content/SceneContentGuestbook.vue`

- [ ] **Step 1: Buat SceneContentRsvp.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/content/SceneContentRsvp.vue -->
<script setup>
defineProps({
    rsvpForm:       { type: Object,  required: true },
    rsvpSubmitting: { type: Boolean, default: false },
    rsvpSuccess:    { type: Boolean, default: false },
    rsvpError:      { type: String,  default: '' },
})
const emit = defineEmits(['submit'])
</script>

<template>
    <div>
        <div v-if="rsvpSuccess" class="text-center py-8">
            <p class="text-2xl mb-2">🎉</p>
            <p class="font-semibold text-gray-800">Terima kasih!</p>
            <p class="text-sm text-gray-500 mt-1">Konfirmasi kehadiran kamu sudah kami terima.</p>
        </div>

        <form v-else @submit.prevent="emit('submit')" class="space-y-3">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nama Lengkap</label>
                <input
                    v-model="rsvpForm.guest_name"
                    placeholder="Nama lengkap kamu"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Konfirmasi Kehadiran</label>
                <select
                    v-model="rsvpForm.attendance"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300 bg-white"
                >
                    <option value="">Pilih konfirmasi</option>
                    <option value="hadir">Hadir</option>
                    <option value="tidak_hadir">Tidak Hadir</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Jumlah Tamu</label>
                <input
                    v-model.number="rsvpForm.guest_count"
                    type="number"
                    min="1"
                    max="10"
                    placeholder="1"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Catatan (opsional)</label>
                <textarea
                    v-model="rsvpForm.notes"
                    placeholder="Catatan untuk pengantin..."
                    rows="2"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300 resize-none"
                />
            </div>

            <p v-if="rsvpError" class="text-xs text-red-500">{{ rsvpError }}</p>

            <button
                type="submit"
                :disabled="rsvpSubmitting"
                class="w-full bg-pink-400 text-white rounded-lg py-2.5 text-sm font-semibold disabled:opacity-50"
            >
                {{ rsvpSubmitting ? 'Mengirim...' : 'Kirim RSVP' }}
            </button>
        </form>
    </div>
</template>
```

- [ ] **Step 2: Buat SceneContentGift.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/content/SceneContentGift.vue -->
<script setup>
defineProps({
    accounts:      { type: Array,  default: () => [] },
    copiedAccount: { type: String, default: null },
})
const emit = defineEmits(['copy'])
</script>

<template>
    <div class="space-y-4">
        <p v-if="!accounts.length" class="text-center text-gray-400 py-8">
            Informasi rekening belum diisi.
        </p>
        <div
            v-for="acc in accounts"
            :key="acc.account_number"
            class="border border-gray-100 rounded-xl p-4 bg-gray-50"
        >
            <p class="font-semibold text-gray-800 text-sm">{{ acc.bank }}</p>
            <p class="text-lg font-mono text-gray-700 mt-1 tracking-wider">{{ acc.account_number }}</p>
            <p class="text-xs text-gray-500 mt-0.5">a/n {{ acc.account_name }}</p>
            <button
                @click="emit('copy', acc.account_number)"
                class="mt-3 w-full border border-gray-300 rounded-lg py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
            >
                {{ copiedAccount === acc.account_number ? '✓ Tersalin' : 'Salin Nomor' }}
            </button>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Buat SceneContentGuestbook.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/content/SceneContentGuestbook.vue -->
<script setup>
defineProps({
    messages:      { type: Array,   default: () => [] },
    msgForm:       { type: Object,  required: true },
    msgSubmitting: { type: Boolean, default: false },
    msgSuccess:    { type: Boolean, default: false },
    msgError:      { type: String,  default: '' },
    mode:          { type: String,  default: 'list' }, // 'form' | 'list'
})
const emit = defineEmits(['submit'])
</script>

<template>
    <div>
        <!-- Form mode -->
        <form v-if="mode === 'form'" @submit.prevent="emit('submit')" class="space-y-3">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nama</label>
                <input
                    v-model="msgForm.name"
                    placeholder="Nama kamu"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Ucapan & Doa</label>
                <textarea
                    v-model="msgForm.message"
                    placeholder="Tulis ucapan dan doa untuk kedua mempelai..."
                    required
                    rows="4"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300 resize-none"
                />
            </div>
            <p v-if="msgError" class="text-xs text-red-500">{{ msgError }}</p>
            <p v-if="msgSuccess" class="text-xs text-green-600">Ucapan terkirim! 🎉</p>
            <button
                type="submit"
                :disabled="msgSubmitting"
                class="w-full bg-pink-400 text-white rounded-lg py-2.5 text-sm font-semibold disabled:opacity-50"
            >
                {{ msgSubmitting ? 'Mengirim...' : 'Kirim Ucapan' }}
            </button>
        </form>

        <!-- List mode -->
        <div v-else class="space-y-3">
            <p v-if="!messages.length" class="text-center text-gray-400 py-8">
                Belum ada ucapan. Jadilah yang pertama! 🌸
            </p>
            <div
                v-for="msg in messages"
                :key="msg.id ?? msg.name"
                class="bg-gray-50 rounded-xl px-4 py-3"
            >
                <p class="font-semibold text-sm text-gray-800">{{ msg.name }}</p>
                <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{{ msg.message }}</p>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/content/
git commit -m "feat: add SceneContentRsvp, SceneContentGift, and SceneContentGuestbook modal content components"
```

---

### Task 9: SceneTemplate.vue (base)

**Files:**
- Create: `resources/js/Components/invitation/templates/scene/SceneTemplate.vue`

- [ ] **Step 1: Buat SceneTemplate.vue**

```vue
<!-- resources/js/Components/invitation/templates/scene/SceneTemplate.vue -->
<script setup>
import { ref, computed } from 'vue'
import { useInvitationTemplate } from '@/Composables/useInvitationTemplate'
import SceneHotspot        from './SceneHotspot.vue'
import SceneModal          from './SceneModal.vue'
import SceneGuestbook      from './SceneGuestbook.vue'
import SceneContentGallery   from './content/SceneContentGallery.vue'
import SceneContentEvents    from './content/SceneContentEvents.vue'
import SceneContentCouple    from './content/SceneContentCouple.vue'
import SceneContentLoveStory from './content/SceneContentLoveStory.vue'
import SceneContentRsvp      from './content/SceneContentRsvp.vue'
import SceneContentGift      from './content/SceneContentGift.vue'
import SceneContentGuestbook from './content/SceneContentGuestbook.vue'

const props = defineProps({
    invitation:  { type: Object,  required: true },
    messages:    { type: Array,   default: () => [] },
    guest:       { type: Object,  default: null },
    isDemo:      { type: Boolean, default: false },
    autoOpen:    { type: Boolean, default: false },
    sceneConfig: { type: Object,  required: true },
})

const {
    fontTitle, fontHeading,
    groomName, brideName, groomNick, brideNick,
    details, events, galleries,
    sectionEnabled, sectionData,
    firstEventDate,
    gateOpen, contentOpen, triggerGate,
    audioEl, musicPlaying, toggleMusic,
    toastMsg, toastVisible,
    copiedAccount, copyToClipboard,
    localMessages, msgForm, msgSubmitting, msgSuccess, msgError, submitMessage,
    rsvpForm, rsvpSubmitting, rsvpSuccess, rsvpError, submitRsvp,
} = useInvitationTemplate(props, {
    openingStyle:  'fade',
    galleryLayout: 'grid',
})

// Modal state
const activeSection  = ref(null)
const guestbookOpen  = ref(true)
const guestbookMode  = ref('list')

const modalOpen = computed(() => activeSection.value !== null)

const modalTitles = {
    gallery:    'Gallery',
    events:     'Date & Venue',
    couple:     'Tentang Kami',
    love_story: 'Kisah Cinta',
    rsvp:       'RSVP',
    gift:       'Hadiah',
    guestbook:  'Buku Tamu',
}

const modalTitle = computed(() => modalTitles[activeSection.value] ?? '')

function openSection(section) {
    activeSection.value = section
}

function closeModal() {
    activeSection.value = null
}

function openGuestbookForm() {
    guestbookMode.value  = 'form'
    activeSection.value  = 'guestbook'
}

function openGuestbookList() {
    guestbookMode.value  = 'list'
    activeSection.value  = 'guestbook'
}

const visibleHotspots = computed(() =>
    props.sceneConfig.hotspots.filter(h => sectionEnabled(h.section))
)
</script>

<template>
    <div>
        <!-- Audio -->
        <audio
            v-if="invitation.music?.file_url && sectionEnabled('music')"
            ref="audioEl"
            :src="invitation.music.file_url"
            loop
            preload="none"
            class="sr-only"
        />

        <!-- ── Cover / Gate ── -->
        <div
            v-if="!gateOpen"
            class="scene-cover"
            :style="{
                backgroundImage:  `url(${sceneConfig.background})`,
                background:       sceneConfig.fallbackBg,
            }"
            @click="triggerGate"
        >
            <div class="cover-overlay">
                <h1 class="cover-names" :style="{ fontFamily: fontTitle }">
                    {{ groomNick }} &amp; {{ brideNick }}
                </h1>
                <p v-if="firstEventDate" class="cover-date" :style="{ fontFamily: fontHeading }">
                    {{ firstEventDate }}
                </p>
                <p v-if="guest?.name" class="cover-guest" :style="{ fontFamily: fontHeading }">
                    Kepada Yth. {{ guest.name }}
                </p>
                <button
                    class="open-btn"
                    :style="{ fontFamily: fontHeading }"
                    @click.stop="triggerGate"
                >
                    Buka Undangan
                </button>
            </div>
        </div>

        <!-- ── Scene ── -->
        <div v-if="contentOpen" class="scene-container">
            <!-- Background image -->
            <img
                :src="sceneConfig.background"
                class="scene-bg"
                alt=""
                draggable="false"
            />

            <!-- Top-left: Info -->
            <button
                class="fixed-btn fixed-top-left"
                aria-label="Info undangan"
                @click="openSection('couple')"
            >
                ⓘ
            </button>

            <!-- Top-right: Music -->
            <button
                v-if="sectionEnabled('music') && invitation.music?.file_url"
                class="fixed-btn fixed-top-right"
                :aria-label="musicPlaying ? 'Pause musik' : 'Play musik'"
                @click="toggleMusic"
            >
                {{ musicPlaying ? '🎵' : '🎶' }}
            </button>

            <!-- Hotspots -->
            <SceneHotspot
                v-for="(hotspot, i) in visibleHotspots"
                :key="hotspot.id"
                :hotspot="hotspot"
                :index="i"
                @click="openSection"
            />

            <!-- Bottom guestbook bar -->
            <SceneGuestbook
                v-if="sectionEnabled('wishes')"
                :open="guestbookOpen"
                @open-form="openGuestbookForm"
                @open-list="openGuestbookList"
                @toggle="guestbookOpen = !guestbookOpen"
            />
        </div>

        <!-- ── Modal ── -->
        <SceneModal
            :modelValue="modalOpen"
            :title="modalTitle"
            @update:modelValue="closeModal"
        >
            <SceneContentGallery
                v-if="activeSection === 'gallery'"
                :galleries="galleries"
            />
            <SceneContentEvents
                v-else-if="activeSection === 'events'"
                :events="events"
            />
            <SceneContentCouple
                v-else-if="activeSection === 'couple'"
                :groomName="groomName"
                :brideName="brideName"
                :details="details"
            />
            <SceneContentLoveStory
                v-else-if="activeSection === 'love_story'"
                :stories="sectionData('love_story').stories ?? []"
            />
            <SceneContentRsvp
                v-else-if="activeSection === 'rsvp'"
                :rsvpForm="rsvpForm"
                :rsvpSubmitting="rsvpSubmitting"
                :rsvpSuccess="rsvpSuccess"
                :rsvpError="rsvpError"
                @submit="submitRsvp"
            />
            <SceneContentGift
                v-else-if="activeSection === 'gift'"
                :accounts="sectionData('gift').accounts ?? []"
                :copiedAccount="copiedAccount"
                @copy="copyToClipboard"
            />
            <SceneContentGuestbook
                v-else-if="activeSection === 'guestbook'"
                :messages="localMessages"
                :msgForm="msgForm"
                :msgSubmitting="msgSubmitting"
                :msgSuccess="msgSuccess"
                :msgError="msgError"
                :mode="guestbookMode"
                @submit="submitMessage"
            />
        </SceneModal>

        <!-- Toast -->
        <Transition name="toast-fade">
            <div v-if="toastVisible" class="scene-toast">{{ toastMsg }}</div>
        </Transition>
    </div>
</template>

<style scoped>
/* ── Container ── */
.scene-container {
    position:   relative;
    width:      100%;
    max-width:  480px;
    aspect-ratio: 9 / 16;
    margin:     0 auto;
    overflow:   hidden;
    background: #111;
}

.scene-bg {
    position:   absolute;
    inset:      0;
    width:      100%;
    height:     100%;
    object-fit: cover;
    user-select: none;
    -webkit-user-drag: none;
}

/* ── Cover ── */
.scene-cover {
    position:            relative;
    width:               100%;
    max-width:           480px;
    aspect-ratio:        9 / 16;
    margin:              0 auto;
    background-size:     cover;
    background-position: center;
    cursor:              pointer;
    display:             flex;
    align-items:         center;
    justify-content:     center;
}

.cover-overlay {
    position:        absolute;
    inset:           0;
    background:      rgba(0, 0, 0, 0.45);
    display:         flex;
    flex-direction:  column;
    align-items:     center;
    justify-content: center;
    gap:             12px;
    padding:         24px;
    text-align:      center;
}

.cover-names {
    font-size:   28px;
    font-weight: 700;
    color:       #fff;
    line-height: 1.2;
}

.cover-date {
    font-size: 15px;
    color:     rgba(255,255,255,0.85);
}

.cover-guest {
    font-size: 13px;
    color:     rgba(255,255,255,0.7);
}

.open-btn {
    margin-top:    8px;
    background:    rgba(255,255,255,0.2);
    border:        1.5px solid rgba(255,255,255,0.7);
    border-radius: 999px;
    color:         #fff;
    padding:       10px 28px;
    font-size:     14px;
    cursor:        pointer;
    backdrop-filter: blur(4px);
}

/* ── Fixed buttons ── */
.fixed-btn {
    position:      absolute;
    z-index:       20;
    background:    rgba(255,255,255,0.2);
    border:        1.5px solid rgba(255,255,255,0.6);
    border-radius: 50%;
    width:         40px;
    height:        40px;
    display:       flex;
    align-items:   center;
    justify-content: center;
    font-size:     16px;
    cursor:        pointer;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.fixed-top-left  { top: 16px; left:  16px; }
.fixed-top-right { top: 16px; right: 16px; }

/* ── Toast ── */
.scene-toast {
    position:  fixed;
    bottom:    80px;
    left:      50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.75);
    color:     #fff;
    padding:   8px 18px;
    border-radius: 999px;
    font-size: 13px;
    z-index:   60;
    white-space: nowrap;
}

.toast-fade-enter-active,
.toast-fade-leave-active { transition: opacity 0.3s; }
.toast-fade-enter-from,
.toast-fade-leave-to     { opacity: 0; }
</style>
```

- [ ] **Step 2: Jalankan dev server dan buka undangan demo di browser, pastikan tidak ada error console**

```bash
npm run dev
# Buka: http://localhost/theday/public/demo atau route undangan yang ada
# Cek console browser — pastikan tidak ada import error atau Vue warning
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/invitation/templates/scene/SceneTemplate.vue
git commit -m "feat: add SceneTemplate base component with gate, hotspots, modals, and guestbook"
```

---

### Task 10: Wrapper templates + Registry

**Files:**
- Create: `resources/js/Components/invitation/templates/BeachTemplate.vue`
- Create: `resources/js/Components/invitation/templates/GardenTemplate.vue`
- Create: `resources/js/Components/invitation/templates/NightSkyTemplate.vue`
- Modify: `resources/js/Components/invitation/templates/registry.js`

- [ ] **Step 1: Buat BeachTemplate.vue**

```vue
<!-- resources/js/Components/invitation/templates/BeachTemplate.vue -->
<script setup>
import SceneTemplate from './scene/SceneTemplate.vue'
import BeachConfig   from './scene/configs/BeachConfig.js'

defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
})
</script>

<template>
    <SceneTemplate v-bind="$props" :sceneConfig="BeachConfig" />
</template>
```

- [ ] **Step 2: Buat GardenTemplate.vue**

```vue
<!-- resources/js/Components/invitation/templates/GardenTemplate.vue -->
<script setup>
import SceneTemplate from './scene/SceneTemplate.vue'
import GardenConfig  from './scene/configs/GardenConfig.js'

defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
})
</script>

<template>
    <SceneTemplate v-bind="$props" :sceneConfig="GardenConfig" />
</template>
```

- [ ] **Step 3: Buat NightSkyTemplate.vue**

```vue
<!-- resources/js/Components/invitation/templates/NightSkyTemplate.vue -->
<script setup>
import SceneTemplate  from './scene/SceneTemplate.vue'
import NightSkyConfig from './scene/configs/NightSkyConfig.js'

defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
})
</script>

<template>
    <SceneTemplate v-bind="$props" :sceneConfig="NightSkyConfig" />
</template>
```

- [ ] **Step 4: Update registry.js**

```js
// resources/js/Components/invitation/templates/registry.js
import NusantaraTemplate from './NusantaraTemplate.vue'
import PearlTemplate     from './PearlTemplate.vue'
import BeachTemplate     from './BeachTemplate.vue'
import GardenTemplate    from './GardenTemplate.vue'
import NightSkyTemplate  from './NightSkyTemplate.vue'

export const TEMPLATE_MAP = {
    'nusantara': NusantaraTemplate,
    'pearl':     PearlTemplate,
    'beach':     BeachTemplate,
    'garden':    GardenTemplate,
    'night-sky': NightSkyTemplate,
}
```

- [ ] **Step 5: Smoke test di browser**

```bash
# Dev server harus sudah jalan dari Task 9 Step 2
# 1. Buka undangan yang menggunakan template 'beach' (atau ubah sementara slug template di DB)
# 2. Pastikan cover screen muncul
# 3. Klik "Buka Undangan" — pastikan scene container muncul
# 4. Klik tiap hotspot — pastikan modal muncul dengan konten yang benar
# 5. Klik [+] di bottom bar — pastikan modal Buku Tamu form muncul
# 6. Klik [↑] di bottom bar — pastikan modal Buku Tamu list muncul
# 7. Klik backdrop — pastikan modal tertutup
# 8. Swipe down di modal — pastikan modal tertutup
# 9. Cek console browser: tidak ada error
```

- [ ] **Step 6: Commit final**

```bash
git add resources/js/Components/invitation/templates/BeachTemplate.vue resources/js/Components/invitation/templates/GardenTemplate.vue resources/js/Components/invitation/templates/NightSkyTemplate.vue resources/js/Components/invitation/templates/registry.js
git commit -m "feat: add Beach, Garden, NightSky wrapper templates and register in TEMPLATE_MAP"
```

---

## Post-Implementation: Asset Tuning

Setelah aset ilustrasi AI-generated tersedia:

1. Letakkan file di `public/images/templates/{theme}/scene.webp`
2. Buka undangan di browser (mobile viewport 390px)
3. Aktifkan DevTools overlay
4. Untuk tiap hotspot, ukur posisi relatif (%) terhadap container
5. Update nilai `x` dan `y` di `{Theme}Config.js` sesuai hasil pengukuran
6. Commit: `chore: tune hotspot coordinates for {theme} template`

---

## Self-Review

**Spec coverage:**
- ✅ 3 template themes (Beach, Garden, Night Sky) → Task 10
- ✅ Config-driven architecture → Task 2 + Task 10
- ✅ SceneHotspot + neon glow → Task 3
- ✅ SceneModal browser-chrome aesthetic + slide-up + swipe-to-close → Task 4
- ✅ SceneGuestbook bottom bar → Task 5
- ✅ All 7 content sections (gallery, events, couple, love_story, rsvp, gift, guestbook) → Task 6-8
- ✅ SceneTemplate base (gate, hotspot render, modal state, fixed buttons, toast) → Task 9
- ✅ Wrapper templates + registry → Task 10
- ✅ Asset pipeline documented → Post-Implementation section
- ✅ `useInvitationTemplate` dipakai tanpa modifikasi → Task 9
- ✅ Tidak ada perubahan ke template existing → registry.js hanya menambah entry

**Placeholder scan:** Tidak ada TBD/TODO. Koordinat hotspot placeholder sudah didokumentasikan sebagai intentional (perlu di-tune setelah aset final).

**Type consistency:**
- `sceneConfig` prop consistent di SceneTemplate (Task 9) dan semua wrapper (Task 10) ✅
- `hotspot.section` string di config match dengan `activeSection` values di SceneTemplate ✅
- Emit names: `open-form`, `open-list`, `toggle` di SceneGuestbook match listener di SceneTemplate ✅
- Content component prop names consistent dengan data yang di-pass dari SceneTemplate ✅
