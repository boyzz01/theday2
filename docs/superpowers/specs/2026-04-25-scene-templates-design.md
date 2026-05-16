# Scene Templates — Design Spec

**Date:** 2026-04-25
**Status:** Approved

## Overview

Paradigma template baru untuk TheDay: **interactive illustrated scene**. User membuka undangan dan "masuk" ke dunia visual yang sudah digambar — bukan scroll halaman biasa. Objek-objek di scene bisa diklik untuk membuka konten undangan via modal.

3 tema direncanakan: **Beach**, **Garden**, **Night Sky**.

---

## Architecture

### Component Tree

```
BeachTemplate.vue   ─┐
GardenTemplate.vue  ─┤→ SceneTemplate.vue → useInvitationTemplate.js
NightSkyTemplate.vue ┘        │
                              ├── SceneHotspot.vue
                              ├── SceneModal.vue
                              └── SceneGuestbook.vue
```

### File Structure

```
resources/js/Components/invitation/templates/
  ├── scene/
  │   ├── SceneTemplate.vue          # base component, semua logika
  │   ├── SceneHotspot.vue           # satu objek klikable di scene
  │   ├── SceneModal.vue             # browser-chrome modal
  │   ├── SceneGuestbook.vue         # bottom bar ucapan
  │   ├── content/
  │   │   ├── SceneContentGallery.vue
  │   │   ├── SceneContentEvents.vue
  │   │   ├── SceneContentCouple.vue
  │   │   ├── SceneContentLoveStory.vue
  │   │   ├── SceneContentRsvp.vue
  │   │   └── SceneContentGift.vue
  │   └── configs/
  │       ├── BeachConfig.js
  │       ├── GardenConfig.js
  │       └── NightSkyConfig.js
  ├── BeachTemplate.vue              # wrapper tipis, inject BeachConfig
  ├── GardenTemplate.vue
  └── NightSkyTemplate.vue

public/images/templates/
  ├── beach/
  │   └── scene.webp
  ├── garden/
  │   └── scene.webp
  └── night-sky/
      └── scene.webp
```

---

## Scene Config Structure

Tiap tema adalah satu config object. Wrapper template hanya inject config ini ke `SceneTemplate`.

```js
// BeachConfig.js
export default {
  background: '/images/templates/beach/scene.webp',
  hotspots: [
    { id: 'gallery',    x: 18, y: 12, label: 'Gallery',      section: 'gallery' },
    { id: 'date_venue', x: 55, y: 10, label: 'Date & Venue', section: 'events' },
    { id: 'about',      x: 45, y: 38, label: 'About Us',     section: 'couple' },
    { id: 'love_story', x: 52, y: 55, label: 'Love Story',   section: 'love_story' },
    { id: 'rsvp',       x: 72, y: 50, label: 'RSVP',         section: 'rsvp' },
    { id: 'gift',       x: 68, y: 72, label: 'Gift',         section: 'gift' },
  ]
}
```

`x` dan `y` adalah persentase dari lebar/tinggi container. Nilai di-tune manual setelah aset ilustrasi final.

---

## SceneTemplate.vue

Props yang diterima (sama persis dengan template lain):
```js
defineProps({
  invitation: { type: Object,  required: true },
  messages:   { type: Array,   default: () => [] },
  guest:      { type: Object,  default: null },
  isDemo:     { type: Boolean, default: false },
  autoOpen:   { type: Boolean, default: false },
})
```

Tambahan prop:
```js
sceneConfig: { type: Object, required: true }  // inject dari wrapper
```

State lokal:
```js
const activeSection = ref(null)   // string | null — section yang sedang dibuka
const guestbookOpen = ref(true)   // bottom bar visible/hidden
```

Semua data undangan (groomName, events, galleries, dll) dari `useInvitationTemplate` tanpa modifikasi.

---

## Layout & Responsiveness

Container utama:
```css
.scene-container {
  position: relative;
  width: 100%;
  max-width: 480px;
  aspect-ratio: 9 / 16;
  margin: 0 auto;
  overflow: hidden;
}
```

Background image mengisi `100% × 100%` container (`object-fit: cover`).

Hotspot positioning:
```css
.hotspot {
  position: absolute;
  left: calc(v-bind('hotspot.x') * 1%);
  top:  calc(v-bind('hotspot.y') * 1%);
  transform: translate(-50%, -50%);
}
```

Mobile-first. Pada desktop, scene terpusat dengan max-width 480px.

---

## SceneHotspot.vue

Satu button per hotspot. Styling: pill/badge dengan label + neon glow effect.

```css
.hotspot-btn {
  background: rgba(255,255,255,0.15);
  border: 1.5px solid rgba(255,255,255,0.6);
  border-radius: 999px;
  padding: 4px 10px;
  backdrop-filter: blur(4px);
  box-shadow:
    0 0 8px rgba(255,255,255,0.6),
    0 0 20px rgba(100,220,255,0.4);
  animation: pulse-glow 2s ease-in-out infinite;
  cursor: pointer;
}

@keyframes pulse-glow {
  0%, 100% { box-shadow: 0 0 8px rgba(255,255,255,0.6), 0 0 20px rgba(100,220,255,0.4); }
  50%       { box-shadow: 0 0 14px rgba(255,255,255,0.9), 0 0 30px rgba(100,220,255,0.7); }
}
```

---

## SceneModal.vue

Modal dengan browser-chrome aesthetic. Muncul sebagai `position: fixed` overlay, slide-up dari bawah.

**Visual structure:**
```
┌─────────────────────────────┐
│ 🔴🟡🟢  theday.id           │  ← browser chrome header (dekoratif)
├─────────────────────────────┤
│ ←  [Judul Section]       ✕  │  ← navigation bar
├─────────────────────────────┤
│                             │
│   <slot />                  │  ← konten section, scrollable
│                             │
└─────────────────────────────┘
```

Props:
```js
defineProps({
  title:     { type: String,  required: true },
  modelValue: { type: Boolean, default: false },  // v-model untuk open/close
})
```

Animasi: `transition: transform 0.3s ease` dari `translateY(100%)` ke `translateY(0)`.
Backdrop: semi-transparent gelap. Klik backdrop → tutup modal.
Swipe down → tutup modal (touch event listener).

---

## SceneGuestbook.vue (Bottom Bar)

Sticky bar selalu visible di bawah layar saat scene aktif:

```
┌──────────────────────────────────────┐
│ [+] [↑]   Sembunyikan Ucapan         │
└──────────────────────────────────────┘
```

- `[+]` → emit `open-form` → SceneTemplate buka SceneModal dengan SceneContentGuestbookForm
- `[↑]` → emit `open-list` → SceneTemplate buka SceneModal dengan SceneContentGuestbookList
- "Sembunyikan Ucapan" → emit `toggle` → bottom bar collapse (transform translateY ke bawah)
- Data dari `localMessages`, `msgForm`, `submitMessage` composable

---

## Opening Gate

Pakai `gateOpen` dari `useInvitationTemplate`. Cover screen:
- Background: scene.webp dengan overlay gelap (rgba 0,0,0,0.5)
- Tengah: nama mempelai (fontTitle), tanggal akad (fontHeading)
- Bawah: tombol "Buka Undangan"
- Setelah klik → `triggerGate()` → animasi fade, scene reveal, hotspot muncul satu per satu (staggered CSS animation)

---

## Fixed Buttons (Top Corners)

| Posisi | Fungsi |
|--------|--------|
| Top-left | Info — buka modal ringkasan undangan (nama tamu, nama mempelai) |
| Top-right | Music toggle — `toggleMusic()` dari composable |

---

## Asset Pipeline

Alur kerja per tema:

1. Generate ilustrasi via Midjourney/DALL-E dengan prompt bertema (pantai chibi, taman magical, dll)
2. Export `scene.webp` ukuran 1080×1920px (portrait 9:16)
3. Simpan di `public/images/templates/{theme}/scene.webp`
4. Referensikan di `{Theme}Config.js` → `background` key
5. Tune koordinat `x/y` hotspot secara manual sesuai posisi objek di ilustrasi final

Aset adalah **static file** di `public/` — bukan user-uploaded content, tidak perlu S3.

---

## Registry

Setelah 3 template selesai, tambahkan ke `registry.js`:

```js
import BeachTemplate    from './BeachTemplate.vue'
import GardenTemplate   from './GardenTemplate.vue'
import NightSkyTemplate from './NightSkyTemplate.vue'

export const TEMPLATE_MAP = {
  // ... existing
  'beach':     BeachTemplate,
  'garden':    GardenTemplate,
  'night-sky': NightSkyTemplate,
}
```

---

## Sections yang Didukung

| Section | Modal Content Component | Data Source |
|---------|------------------------|-------------|
| `gallery` | SceneContentGallery | `galleries` |
| `events` | SceneContentEvents | `events` |
| `couple` | SceneContentCouple | `details`, `groomName`, `brideName` |
| `love_story` | SceneContentLoveStory | `sectionData('love_story')` |
| `rsvp` | SceneContentRsvp | `rsvpForm`, `submitRsvp` |
| `gift` | SceneContentGift | `sectionData('gift')` |
| `guestbook` | SceneContentGuestbook | `localMessages`, `msgForm` |

---

## What This Is NOT

- Bukan perubahan ke `useInvitationTemplate.js` — composable dipakai apa adanya
- Bukan perubahan ke template existing (Nusantara, Pearl, Minang)
- Bukan fitur baru di dashboard/editor — template ini langsung masuk ke TEMPLATE_MAP, editor existing sudah support
- Aset ilustrasi bukan bagian dari implementasi kode — harus disiapkan terpisah sebelum koordinat hotspot bisa di-tune
