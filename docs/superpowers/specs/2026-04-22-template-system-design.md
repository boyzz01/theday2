# Template System Design

**Date:** 2026-04-22
**Topic:** Multi-template system untuk undangan digital (25+ templates)
**Status:** Approved

## Latar Belakang

TheDay saat ini hanya punya satu template premium: `NusantaraTemplate.vue`. Rencana: tambah 25+ template baru. Source HTML bervariasi — dibuat manual, di-generate AI, atau disave dari kompetitor. Semua template baru adalah premium tier dan harus support kustomisasi warna/font.

---

## Keputusan Arsitektur

### 1. Template Registry (single source of truth)

**Masalah sekarang:** `TEMPLATE_MAP` didefinisikan duplikat di dua file:
- `InvitationRenderer.vue`
- `TemplatePreviewModal.vue`

Tambah template baru = update manual di 2 tempat. Rawan lupa.

**Solusi:** Buat `registry.js` sebagai satu-satunya tempat registrasi template. Kedua consumer import dari sini.

```
resources/js/Components/invitation/templates/
  registry.js                  ← satu-satunya file yang di-update saat tambah template
  _template-boilerplate.vue    ← digunakan scaffold script sebagai base
  NusantaraTemplate.vue        ← template pertama, juga jadi referensi konversi
  JasmineTemplate.vue
  SakuraTemplate.vue
  ...

scripts/
  make-template.js             ← npm run make:template <slug>
```

`registry.js`:
```js
import NusantaraTemplate from './NusantaraTemplate.vue'
import JasmineTemplate   from './JasmineTemplate.vue'
// ...

export const TEMPLATE_MAP = {
  'nusantara': NusantaraTemplate,
  'jasmine':   JasmineTemplate,
  // ...
}
```

`InvitationRenderer.vue` dan `TemplatePreviewModal.vue` keduanya:
```js
import { TEMPLATE_MAP } from '@/Components/invitation/templates/registry'
```

---

### 2. Composable `useInvitationTemplate(props, defaults)`

**NusantaraTemplate dijadikan base** — semua logic yang shared diekstrak ke composable. NusantaraTemplate sendiri direfactor untuk menggunakan composable ini (menjadi referensi implementasi yang benar). Tiap template baru hanya berisi visual (`<template>`), tidak duplikasi logic.

```js
// JasmineTemplate.vue
const {
  groomName, brideName, groomNick, brideNick,
  primary, accent, bgColor, darkBg,
  fontTitle, fontHeading, fontBody,
  events, galleries, countdown,
  sectionEnabled, sectionData,
  gateOpen, triggerGate,
  musicPlaying, toggleMusic,
  localMessages, msgForm, submitMessage,
  rsvpForm, submitRsvp,
  copyToClipboard, videoEmbedUrl,
  openingText, closingText,
  coverPhotoUrl, firstEvent, firstEventDate,
} = useInvitationTemplate(props, {
  galleryLayout: 'horizontal',  // default untuk template ini
  openingStyle:  'fade',
})
```

**Parameter `defaults`:** Tiap template mendefinisikan default style untuk section-nya. Composable membaca override dari `invitation.config` jika ada, fallback ke defaults template:

```js
// Di dalam composable:
const galleryLayout = computed(() =>
  cfg.value.gallery_layout ?? defaults.galleryLayout ?? 'vertical'
)
```

Ini memungkinkan:
- **Sekarang:** config kosong → template pakai default-nya sendiri
- **Nanti:** user set `config.gallery_layout` dari dashboard → override langsung bekerja tanpa ubah template

---

### 3. Section Map (15 sections)

Semua section mendukung toggle on/off via `sectionEnabled(key)`. Setiap section di template wajib di-wrap `v-if="sectionEnabled('key')"`.

| Section Key       | Data                                                        | Keterangan                    |
|-------------------|-------------------------------------------------------------|-------------------------------|
| `cover`           | groomNick, brideNick, firstEventDate, coverPhotoUrl, guest  | Opening gate / cover screen   |
| `opening`         | groomName, brideName, openingText, details.religion         | Sambutan + foto couple        |
| `quote`           | sectionData('quote').text                                   | Ayat/kutipan, opsional        |
| `events`          | events[].event_name/date_formatted/start_time/location      | Akad + Resepsi                |
| `countdown`       | countdown.days/hours/minutes/seconds                        | Auto hitung dari event pertama|
| `live_streaming`  | sectionData('live_streaming').url                           | Link YouTube/Zoom             |
| `additional_info` | sectionData('additional_info').text                         | Info dress code dll           |
| `gallery`         | galleries[].file_url, galleryLayout                         | Style = default template      |
| `love_story`      | sectionData('love_story').stories[].date/title/description  | Timeline kisah cinta          |
| `video`           | sectionData('video').url → videoEmbedUrl()                  | YouTube/Vimeo embed           |
| `rsvp`            | rsvpForm, submitRsvp(), isDemo                              | Form konfirmasi hadir         |
| `gift`            | sectionData('gift').accounts[].bank/account_number/name     | Nomor rekening + copy         |
| `wishes`          | localMessages[], msgForm, submitMessage(), isDemo           | Buku tamu / ucapan            |
| `closing`         | closingText, groomName, brideName                           | Penutup + footer              |
| `music`           | invitation.music.file_url, musicPlaying, toggleMusic()      | Float button + audio          |

---

### 4. Template Demo URL

Route `/templates/{slug}/demo` sudah ada dan bekerja via `TemplateGalleryController@demo`. Tidak ada perubahan backend yang diperlukan.

Template baru otomatis punya demo URL begitu:
1. Vue component terdaftar di `registry.js`
2. DB record punya `demo_data` yang lengkap

---

### 5. Scaffold Script

`npm run make:template <slug>` melakukan:
1. Buat `{Slug}Template.vue` dari boilerplate (composable sudah ter-import)
2. Tambah entry di `registry.js`
3. Print checklist konversi ke terminal

---

## HTML → Vue Conversion Workflow

Untuk setiap template baru (dari sumber manapun):

### Step 1: Prep HTML
- Hapus CDN script (analytics, tracking, jQuery, dll)
- Hapus JS kompetitor (interaksi, animasi berbasis JS)
- Pertahankan: struktur HTML + CSS visual
- Catat warna utama, font, section yang ada
- **Catatan:** Untuk HTML scraped dari kompetitor, ambil CSS visual saja. Hindari font/aset berlisensi.

### Step 2: Scaffold
```bash
npm run make:template <slug>
```

### Step 3: Map sections HTML → section key
Identifikasi setiap bagian visual HTML dan map ke section key (lihat tabel di atas).

### Step 4: Replace hardcoded values

| Hardcoded di HTML             | Ganti dengan (dari composable)       |
|-------------------------------|--------------------------------------|
| `#8B6914` (warna utama)       | `primary`                            |
| `#6B1D1D` (accent)            | `accent`                             |
| `#F5F0E8` (background)        | `bgColor`                            |
| `"Cinzel Decorative"` (font)  | `fontTitle`                          |
| `"Cormorant Garamond"`        | `fontHeading`                        |
| `"Crimson Text"`              | `fontBody`                           |
| `"Rizky"` / `"Sinta"` (nama) | `groomNick` / `brideNick`            |
| `"Sabtu, 12 Juli 2025"`       | `events[0].event_date_formatted`     |

### Step 5: Tambah v-if sectionEnabled
Setiap section HTML di-wrap:
```html
<section v-if="sectionEnabled('gift')">
  <!-- nomor rekening -->
</section>
```

### Step 6: Set section style defaults
Di `useInvitationTemplate(props, { galleryLayout: 'horizontal' })` sesuai desain template.

### Step 7: DB record + demo_data
Insert ke tabel `templates`:
```json
{
  "slug": "jasmine",
  "name": "Jasmine",
  "tier": "premium",
  "thumbnail_url": "...",
  "default_config": {
    "primary_color": "#...",
    "font_title": "...",
    "font_heading": "...",
    "font_body": "..."
  },
  "demo_data": {
    "details": { "groom_name": "...", "bride_name": "..." },
    "events": [...],
    "gallery": ["url1", "url2"],
    "love_story": { "stories": [...] },
    "gift": { "accounts": [...] }
  }
}
```

### Step 8: Test + screenshot
- Buka `/templates/<slug>/demo` — verifikasi semua section tampil
- Cek kustomisasi warna/font bekerja di TemplatePreviewModal
- Screenshot untuk thumbnail

---

## Checklist Konversi (10 langkah)

```
☐ Prep HTML (hapus CDN/JS kompetitor)
☐ npm run make:template <slug>
☐ Replace hardcoded warna → primary, accent, bgColor
☐ Replace hardcoded font → fontTitle, fontHeading, fontBody
☐ Map setiap section HTML → section key
☐ Wrap tiap section dengan v-if="sectionEnabled('key')"
☐ Set galleryLayout + style defaults di composable call
☐ Insert DB record + demo_data lengkap
☐ Test di /templates/<slug>/demo
☐ Screenshot → upload sebagai thumbnail_url
```

---

## Error Handling & Fallback UI

### Tanggung jawab composable
Semua computed value harus return safe default — tidak boleh ada crash karena data kosong:

```js
const events    = computed(() => props.invitation.events   ?? [])
const galleries = computed(() => props.invitation.galleries ?? [])
const firstEvent = computed(() => events.value[0] ?? null)
const targetDate = computed(() => firstEvent.value?.event_date ? new Date(...) : null)
const groomNick  = computed(() => details.value.groom_nickname?.trim() || groomName.value || '—')
```

### Tanggung jawab template
Section yang bergantung pada konten wajib cek data existence, bukan hanya `sectionEnabled`:

```html
<!-- Content-dependent: cek sectionEnabled DAN data ada -->
<section v-if="sectionEnabled('gallery') && galleries.length">
<section v-if="sectionEnabled('video') && videoEmbedUrl(sectionData('video').url)">
<section v-if="sectionEnabled('gift') && sectionData('gift').accounts?.length">
<section v-if="sectionEnabled('love_story') && sectionData('love_story').stories?.length">
<section v-if="sectionEnabled('countdown') && targetDate">
<section v-if="sectionEnabled('music') && invitation.music?.file_url">

<!-- Selalu ada (composable sudah provide fallback text) -->
<section v-if="sectionEnabled('cover')">    <!-- groomNick fallback '—' -->
<section v-if="sectionEnabled('rsvp')">     <!-- form selalu bisa render -->
<section v-if="sectionEnabled('closing')">  <!-- closingText ada fallback -->
```

### Tanggung jawab demo_data
`demo_data` di DB record harus lengkap untuk semua section yang template ini tampilkan — tidak boleh ada section kosong di demo URL.

---

## Acceptance Criteria per Template

Template dianggap **selesai** jika memenuhi semua kriteria berikut:

| # | Kriteria | Cara verifikasi |
|---|----------|-----------------|
| 1 | Render benar di mobile (≤ 390px) dan desktop | Buka di browser, resize / DevTools |
| 2 | Semua section toggle on/off aman (tidak crash saat dimatikan) | Toggle tiap section dari dashboard |
| 3 | Tidak ada hardcoded content tersisa (nama, warna, font, tanggal) | Ganti warna di customization → harus berubah |
| 4 | Preview modal tampil benar | Buka TemplatePreviewModal di gallery |
| 5 | Demo URL valid dan semua section terisi | Buka `/templates/<slug>/demo` |
| 6 | Section content-dependent tidak render saat data kosong | Hapus gallery/gift/video dari demo_data, cek tidak error |
| 7 | Music toggle berfungsi (jika template punya section music) | Klik float button, audio play/pause |
| 8 | RSVP & wishes form tidak aktif di demo (isDemo guard) | Submit form di demo URL → harus ditolak |

---

## Out of Scope (untuk sekarang)

- User-configurable section styles (gallery layout override dari dashboard) — didesain agar bisa ditambah nanti tanpa ubah template
- Template categories / filter
- Template preview di mobile mockup per template
