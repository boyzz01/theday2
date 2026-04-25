# AI Context Document Design

**Date:** 2026-04-25
**Topic:** Membuat `AI_CONTEXT.md` untuk onboarding AI lain (ChatGPT, dll) ke project TheDay
**Status:** Draft

---

## Tujuan

Membuat satu file `AI_CONTEXT.md` di root project yang bisa di-paste atau di-upload ke AI lain (ChatGPT, Gemini, dll) agar AI tersebut langsung memahami konteks project TheDay — baik untuk keperluan coding maupun brainstorming (terutama brainstorming desain template undangan).

File ini **terpisah** dari `SKILL.md` (yang tetap jadi panduan khusus Claude Code).

---

## Struktur Dokumen

### Section 1 — Project Identity
Deskripsi singkat produk:
- Nama: TheDay
- Tipe: SaaS web app — undangan digital pernikahan (Indonesia market)
- Target user: pasangan yang akan menikah, butuh undangan digital premium
- Posisi produk: bukan template builder biasa — focus on mobile-first, WhatsApp sharing, guest management
- URL pattern: `theday.id/{slug-undangan}`
- Bahasa UI: Indonesia

### Section 2 — Tech Stack
Tabel lengkap teknologi + versi:
- Backend: Laravel 11, PHP 8.2+
- Frontend: Vue 3.4+ + Inertia.js 1.x (bukan SPA penuh, bukan server-side biasa)
- Styling: Tailwind CSS 3.x
- Database: MySQL 8.x
- Auth: Laravel Breeze (Google OAuth + email/password)
- Payment: Midtrans Snap + Mayar
- File Storage: Spatie Media Library + S3-compatible
- Roles/Permissions: Spatie Permission
- Build: Vite
- Realtime: Laravel Echo + Pusher/Soketi

### Section 3 — Architecture Rules
Konvensi wajib yang harus diikuti AI saat suggest code:

**Backend:**
- UUID primary key di semua model (bukan auto-increment)
- Business logic di `app/Services/`, bukan di controller
- Single-purpose operations di `app/Actions/`
- Validasi via Form Request class
- Authorization via Policy
- PHP 8.1 backed Enums di `app/Enums/`

**Frontend:**
- Selalu `<script setup>` (Composition API) — tidak pernah Options API
- Reusable logic di `resources/js/Composables/useXxx.js`
- Tidak pakai Pinia — state via Inertia shared data + composables
- Tailwind only — tidak ada custom CSS kecuali animasi template undangan
- Component naming: PascalCase, prefix domain (misal: `EditorStepBasicInfo.vue`)

### Section 4 — Features Overview
List fitur yang sudah ada:

**Invitation:**
- Editor step-by-step (basic info → tema → foto → events → gallery → review)
- Template system (multi-template, kustomisasi warna + font)
- Publish/unpublish/archive lifecycle
- Password protection (optional)
- View counter

**Guest Management:**
- Guest List Manager (tambah tamu manual atau import CSV)
- Personalized invitation URL per tamu (`/{invitationSlug}/{guestSlug}`)
- WhatsApp blast dengan template pesan kustom
- Tracking open status per tamu
- RSVP (attending / not_attending / maybe)
- Buku Tamu (guestbook messages)

**Subscription & Payment:**
- Free plan + Premium plan
- Addon: tambah kuota undangan
- Payment via Midtrans Snap + Mayar
- Transaction history

**Dashboard:**
- Overview statistik undangan
- Manajemen undangan (CRUD)
- Template picker + duplication
- Checklist pernikahan (wedding planner)
- Budget tracker pernikahan
- Artikel/blog

**Admin Panel:**
- Manajemen user, plan, template, transaksi
- Template seeder + kategori

### Section 5 — Business Rules

**Free Plan:**
- Max 1 undangan aktif
- Max 5 foto gallery
- Tidak bisa upload musik sendiri
- Ada watermark "Dibuat dengan TheDay"
- Tidak ada analytics

**Premium Plan:**
- Unlimited undangan (atau sesuai kuota)
- Full gallery
- Upload musik sendiri
- No watermark
- Analytics view

**Invitation Lifecycle:**
```
Draft → Published → Archived
          ↓
       Unpublished (kembali ke Draft)
```

**Auth Flow (Value-First):**
- User bisa buat undangan tanpa register dulu
- Register/login diminta saat mau simpan atau publish
- Guest draft data di-convert ke invitation saat auth

### Section 6 — Database Schema (Ringkas)

Tabel utama dan relasi kunci:

```
users (UUID)
  └── invitations (UUID, user_id, template_id, slug, status, expires_at)
        ├── invitation_details (groom_name, bride_name, cover_photo_url, opening_text, ...)
        ├── invitation_events (nama acara, tanggal, lokasi, maps_url)
        ├── invitation_galleries (photo_url, caption, order)
        ├── invitation_sections (section_key, enabled, data JSON, order)
        ├── invitation_music (music_url)
        ├── invitation_views (timestamp)
        └── rsvps (name, attendance_status, message)

guest_lists (UUID, user_id, invitation_id, name, phone, slug, send_status, rsvp_status)
  └── guest_message_logs

templates (UUID, name, slug, tier, preview_url, config JSON)
  └── template_sections
  └── template_assets

plans (UUID, name, slug, price, features JSON)
  └── subscriptions (user_id, plan_id, status, expires_at)
        └── transactions (amount, payment_method, payment_status)

invitation_addons (user_id, invitation_id, quantity)
```

### Section 7 — Template System

Ini section paling penting untuk sesi brainstorming template undangan.

**Cara Kerja Template:**
- Template = Vue component (`resources/js/Components/invitation/templates/XxxTemplate.vue`)
- Semua template share logic via composable `useInvitationTemplate(props, defaults)`
- Composable menyediakan: data mempelai, events, gallery, warna, font, section visibility, RSVP, buku tamu, musik, countdown
- Template hanya berisi visual (`<template>` HTML + CSS kustom animasi)
- Registry: `resources/js/Components/invitation/templates/registry.js`

**Template yang Ada Sekarang:**
| Slug | Nama | Style | Status |
|------|------|-------|--------|
| `nusantara` | Nusantara | Jawa klasik, batik-inspired, warm gold | ✅ Production |
| `pearl` | Pearl | Modern elegant, cream & gold | ✅ Production |
| `minang` | Minang | (dalam pengembangan) | 🚧 WIP |

**Kustomisasi Per Template (via `invitation.config`):**
- `primary_color` — warna utama (hex)
- `primary_color_light` — varian terang
- `secondary_color` — background utama
- `accent_color` — warna aksen
- `dark_bg` — background gelap (untuk section overlay)
- `font_title` — font untuk judul (Google Fonts)
- `font_heading` — font untuk heading section
- `font_body` — font untuk teks biasa

**Sections dalam Undangan (section_key):**
| Key | Nama | Deskripsi |
|-----|------|-----------|
| `cover` | Cover | Foto utama + nama mempelai + tombol buka |
| `opening` | Pembuka | Teks pembuka, kutipan |
| `couple` | Mempelai | Profil singkat pengantin + orang tua |
| `countdown` | Hitung Mundur | Timer menuju hari H |
| `events` | Acara | Jadwal akad + resepsi |
| `gallery` | Galeri Foto | Grid/carousel foto prewedding |
| `love_story` | Kisah Cinta | Timeline kenangan bersama |
| `video` | Video | YouTube/Vimeo embed |
| `rsvp` | RSVP | Form konfirmasi kehadiran |
| `guestbook` | Buku Tamu | Form + list ucapan |
| `gift` | Hadiah/Transfer | Nomor rekening / gift registry |
| `closing` | Penutup | Teks penutup + signature |

**Style Vocabulary untuk Brainstorming:**
Saat brainstorm template baru, gunakan kategori ini sebagai referensi:
- **Jawa Klasik** — batik, keris, warna coklat emas, serif tebal
- **Sunda Tradisional** — anyaman, daun pisang, hijau tua, bunga melati
- **Minang** — rumah gadang, ukiran kayu, merah-hitam-emas
- **Modern Minimalis** — clean white space, sans-serif tipis, satu accent color
- **Garden / Floral** — bunga-bunga, botanical, pastel, watercolor
- **Coastal / Beach** — biru laut, putih, pasir, nuansa santai
- **Luxury Dark** — hitam, emas, marble texture, high-end feel
- **Rustic** — kayu, linen, earthy tones, vintage feel
- **Islamic Modern** — ornamen geometris Islam, kaligrafi, ungu/hijau tua
- **Chinese / Peranakan** — merah, emas, motif bunga Cina, modern fusion

**Cara Tambah Template Baru:**
```bash
npm run make:template <slug>   # scaffold boilerplate
# → paste HTML dari sumber ke dalam component
# → sesuaikan warna/font dengan useInvitationTemplate()
# → daftarkan di registry.js
# → tambah record di tabel templates (DB)
```

### Section 8 — Current State (per 2026-04-25)

Fitur selesai:
- ✅ Invitation editor (full flow)
- ✅ Template system (Nusantara + Pearl production-ready)
- ✅ Guest List Manager (import CSV, WhatsApp blast, RSVP tracking)
- ✅ Subscription + Payment (Midtrans + Mayar)
- ✅ Transaction history
- ✅ Wedding checklist + budget tracker
- ✅ Admin panel (users, plans, templates, transactions)
- ✅ Google OAuth login
- ✅ Section customization per invitation
- ✅ Invitation duplication + template switching

Sedang dikerjakan / planned:
- 🚧 MinangTemplate (WIP)
- 📋 25+ template baru
- 📋 Halaman kontak
- 📋 Legal pages
- 📋 Guest list improvements

### Section 9 — How to Help AI

Instruksi untuk AI yang membaca file ini:

1. **Bahasa**: Jawab dalam Bahasa Indonesia kecuali diminta lain
2. **Stack**: Jangan sarankan ganti tech stack. Pakai Laravel + Vue 3 + Inertia + Tailwind
3. **Konvensi**: Ikuti architecture rules di Section 3 saat suggest code
4. **Template brainstorming**: Fokus pada konteks pernikahan Indonesia. Pertimbangkan nilai-nilai lokal, budaya, dan estetika Indonesia
5. **Code style**: Backend = Laravel conventions. Frontend = `<script setup>`, Composition API, Tailwind classes
6. **UUID**: Semua model pakai UUID, bukan integer ID
7. **Scope**: Produk ini khusus undangan pernikahan. Tidak ada fitur ulang tahun atau event lain

---

## Implementation Notes

- File dibuat di root project: `AI_CONTEXT.md`
- Tidak perlu frontmatter atau metadata khusus — murni Markdown yang readable
- Panjang target: ~400-600 baris
- Bahasa: campuran Indonesia + istilah teknis Inggris (sama seperti dokumen ini)
- Tidak perlu commit terpisah — commit sekalian dengan spec ini
