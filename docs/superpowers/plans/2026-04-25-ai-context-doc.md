# AI Context Document Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membuat `AI_CONTEXT.md` di root project — satu file markdown komprehensif yang bisa di-paste atau di-upload ke AI lain (ChatGPT, Gemini, dll) agar langsung memahami konteks TheDay.

**Architecture:** Satu file markdown flat, 9 sections berurutan dari identitas produk sampai instruksi penggunaan AI. Tidak ada dependency ke file lain. File ini human-readable dan AI-readable sekaligus.

**Tech Stack:** Markdown only.

---

### Task 1: Buat `AI_CONTEXT.md`

**Files:**
- Create: `AI_CONTEXT.md` (root project)

**Spec:** `docs/superpowers/specs/2026-04-25-ai-context-doc-design.md`

- [ ] **Step 1: Buat file dengan 9 sections**

Buat `AI_CONTEXT.md` di root project dengan konten berikut:

```markdown
# AI_CONTEXT.md — TheDay Project Context

> File ini dibuat khusus untuk onboarding AI assistant (ChatGPT, Gemini, dll) ke project TheDay.
> Paste atau upload file ini di awal sesi agar AI langsung memahami konteks project.
> Terakhir diupdate: 2026-04-25

---

## 1. Project Identity

**Nama:** TheDay
**Tipe:** SaaS web app — undangan digital pernikahan premium
**Target user:** Pasangan yang akan menikah di Indonesia, butuh undangan digital yang bisa dishare via WhatsApp
**Posisi produk:** Bukan sekadar template builder — TheDay fokus pada pengalaman tamu (mobile-first, personalized URL per tamu, RSVP, buku tamu digital)
**URL pattern:** `theday.id/{slug-undangan}`
**Bahasa UI:** Indonesia
**Status:** Production (aktif digunakan)

---

## 2. Tech Stack

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| Backend | Laravel | 11.x |
| PHP | PHP | 8.2+ |
| Frontend | Vue 3 + Inertia.js | Vue 3.4+, Inertia 1.x |
| Styling | Tailwind CSS | 3.x |
| Database | MySQL | 8.x |
| Auth | Laravel Breeze (email + Google OAuth) | — |
| Payment | Midtrans Snap + Mayar | — |
| File Storage | Spatie Media Library + S3-compatible | 11.x |
| Roles/Permissions | Spatie Permission | 6.x |
| Image Processing | Intervention Image | 3.x |
| Build Tool | Vite | — |
| Realtime | Laravel Echo + Pusher/Soketi | — |

**Catatan penting:** Inertia.js bukan SPA murni dan bukan server-rendered biasa — ia adalah hybrid. Controller Laravel me-return Inertia response, Vue component me-render di client tanpa full page reload.

---

## 3. Architecture Rules

### Backend
- **UUID everywhere** — semua model pakai UUID sebagai primary key, BUKAN auto-increment integer
- **Service pattern** — business logic di `app/Services/`, controller hanya orchestrate
- **Action classes** — operasi single-purpose di `app/Actions/` (contoh: `PublishInvitationAction`)
- **Form Requests** — validasi selalu via dedicated Form Request class, bukan inline di controller
- **Policies** — authorization selalu via Policy class yang di-attach ke model
- **PHP Enums** — gunakan PHP 8.1 backed enums di `app/Enums/`
- **API Resources** — response JSON via `JsonResource` / `ResourceCollection`
- **Eager Loading** — selalu eager load relasi, jangan N+1

### Frontend
- **Composition API only** — selalu pakai `<script setup>`, TIDAK pernah Options API
- **Composables** — reusable logic di `resources/js/Composables/useXxx.js`
- **No Pinia** — state management via Inertia shared data + composables
- **Tailwind only** — tidak ada custom CSS kecuali animasi untuk template undangan
- **Component naming** — PascalCase, prefix domain: `EditorStepBasicInfo.vue`, `DashboardStatCard.vue`

### Database
- UUID primary key di semua tabel
- JSON columns untuk flexible config (`custom_config`, `default_config`, `features`)
- Soft deletes pada: `invitations`, `users`
- Index pada: `slug`, `status`, `user_id`, `invitation_id`, `created_at`

---

## 4. Features Overview

### Invitation (Inti Produk)
- Editor step-by-step: basic info → tema → foto → events → gallery → review
- Template system: multi-template, kustomisasi warna + font per template
- Publish / unpublish / archive lifecycle
- Password protection (opsional)
- View counter per undangan
- Duplikasi undangan + ganti template

### Guest Management
- Guest List Manager: tambah tamu manual atau import CSV
- Personalized invitation URL per tamu: `/{invitationSlug}/{guestSlug}`
- WhatsApp blast dengan template pesan yang bisa dikustomisasi
- Tracking status pengiriman + open status per tamu
- RSVP: attending / not_attending / maybe
- Buku Tamu (guestbook): form ucapan + list tampil di undangan

### Subscription & Payment
- Free plan + Premium plan
- Addon: tambah kuota undangan
- Payment via Midtrans Snap + Mayar
- Transaction history dashboard

### Dashboard User
- Overview statistik undangan (views, RSVP, tamu)
- Manajemen undangan (CRUD)
- Checklist pernikahan (wedding planner)
- Budget tracker pernikahan
- Artikel / blog konten

### Admin Panel
- Manajemen user, plan, template, transaksi
- Template seeder + kategori management

---

## 5. Business Rules

### Free Plan
- Max 1 undangan aktif
- Max 5 foto gallery
- Tidak bisa upload musik sendiri
- Ada watermark "Dibuat dengan TheDay" di footer undangan
- Tidak ada analytics

### Premium Plan
- Kuota undangan sesuai plan (bisa tambah via addon)
- Full gallery (tidak ada batas foto)
- Upload musik sendiri
- No watermark
- Analytics view count

### Invitation Lifecycle
```
Draft → Published → Archived
          ↓
       Unpublished (kembali ke Draft)
```

### Auth Flow (Value-First)
- User bisa buat undangan tanpa register/login dulu
- Register/login diminta saat user mau **simpan** atau **publish** (via AuthWall modal)
- Data undangan guest disimpan di `guest_drafts`, di-convert ke `invitations` saat auth berhasil

---

## 6. Database Schema (Ringkas)

```
users (UUID)
  └── invitations (UUID, user_id, template_id, slug, status, expires_at)
        ├── invitation_details (groom_name, bride_name, cover_photo_url,
        │                        opening_text, closing_text, ...)
        ├── invitation_events (nama acara, tanggal, lokasi, maps_url, order)
        ├── invitation_galleries (photo_url, caption, order)
        ├── invitation_sections (section_key, enabled, data JSON, order,
        │                         is_visible_in_template)
        ├── invitation_music (music_url)
        ├── invitation_views (timestamp, ip, user_agent)
        └── rsvps (name, attendance_status, message, created_at)

guest_lists (UUID, user_id, invitation_id, name, phone, slug,
             send_status, rsvp_status, soft_deletes)
  └── guest_message_logs (status, channel, created_at)

templates (UUID, name, slug, tier, preview_url, config JSON)
  ├── template_sections
  └── template_assets

plans (UUID, name, slug, price, features JSON)
subscriptions (UUID, user_id, plan_id, status, expires_at, grace_until)
transactions (UUID, user_id, plan_id, amount, payment_method,
              payment_status, addon_quantity)
invitation_addons (UUID, user_id, invitation_id, quantity)

wedding_plans (user_id, ...)
wedding_budgets (user_id, ...)
  ├── wedding_budget_categories
  └── wedding_budget_items

checklist_tasks (user_id, ...)
  └── checklist_subtasks
```

**Key Enums:**
- `InvitationStatus`: draft, published, unpublished, archived
- `PaymentStatus`: pending, paid, failed, expired
- `PaymentMethod`: midtrans, mayar
- `GuestSendStatus`: not_sent, sent, opened
- `GuestRsvpStatus`: pending, attending, not_attending, maybe
- `TemplateTier`: free, premium

---

## 7. Template System

Ini adalah bagian paling penting untuk sesi brainstorming template undangan.

### Cara Kerja Template
- Template = Vue component di `resources/js/Components/invitation/templates/XxxTemplate.vue`
- Semua template share logic via composable `useInvitationTemplate(props, defaults)`
- Composable menyediakan: data mempelai, events, gallery, warna, font, section visibility, RSVP, buku tamu, musik, countdown
- Template hanya berisi visual (`<template>` HTML + Tailwind classes + animasi CSS kustom)
- **Registry:** `resources/js/Components/invitation/templates/registry.js` — satu-satunya file yang di-update saat tambah template baru

### Template yang Ada Sekarang
| Slug | Nama | Style | Status |
|------|------|-------|--------|
| `nusantara` | Nusantara | Jawa klasik, batik-inspired, warm gold & cream | ✅ Production |
| `pearl` | Pearl | Modern elegant, cream & gold, clean layout | ✅ Production |
| `minang` | Minang | Tradisional Minangkabau | 🚧 WIP |

### Kustomisasi Per Template
Disimpan di `invitation.config` (JSON column), dibaca oleh `useInvitationTemplate`:

| Config Key | Deskripsi | Contoh Default |
|-----------|-----------|---------------|
| `primary_color` | Warna utama | `#8B6914` |
| `primary_color_light` | Varian lebih terang | `#C9A84C` |
| `secondary_color` | Background utama | `#F5F0E8` |
| `accent_color` | Warna aksen/kontras | `#6B1D1D` |
| `dark_bg` | Background gelap (overlay) | `#2C1810` |
| `font_title` | Font judul (Google Fonts name) | `Cinzel Decorative` |
| `font_heading` | Font heading section | `Cormorant Garamond` |
| `font_body` | Font teks biasa | `Crimson Text` |
| `gallery_layout` | Layout galeri foto | `vertical` / `horizontal` |
| `opening_style` | Gaya animasi pembuka | `gate` / `fade` |

### Sections dalam Undangan
| Section Key | Nama Tampil | Deskripsi |
|-------------|-------------|-----------|
| `cover` | Cover | Foto utama + nama mempelai + tombol buka undangan |
| `opening` | Pembuka | Teks pembuka, ayat/kutipan |
| `couple` | Mempelai | Profil singkat pengantin + orang tua |
| `countdown` | Hitung Mundur | Timer menuju hari H |
| `events` | Acara | Jadwal akad + resepsi (bisa multiple) |
| `gallery` | Galeri Foto | Grid/carousel foto prewedding |
| `love_story` | Kisah Cinta | Timeline kenangan bersama |
| `video` | Video | YouTube/Vimeo embed |
| `rsvp` | RSVP | Form konfirmasi kehadiran |
| `guestbook` | Buku Tamu | Form ucapan + list pesan tamu |
| `gift` | Hadiah/Transfer | Nomor rekening / gift registry |
| `closing` | Penutup | Teks penutup + signature |

### Style Vocabulary untuk Brainstorming Template
Saat brainstorm template baru, gunakan kategori ini:

| Kategori | Ciri Khas |
|----------|-----------|
| **Jawa Klasik** | Motif batik, keris, warna coklat-emas-krem, serif tebal, nuansa keraton |
| **Sunda Tradisional** | Anyaman bambu, daun pisang, hijau tua, bunga melati, earthy |
| **Minang** | Rumah gadang silhouette, ukiran kayu, merah-hitam-emas, gonjong |
| **Modern Minimalis** | White space besar, sans-serif tipis, satu accent color, clean grid |
| **Garden / Floral** | Bunga-bunga watercolor, botanical illustration, pastel, romantic |
| **Coastal / Beach** | Biru laut, putih, pasir, nuansa santai, font handwritten |
| **Luxury Dark** | Background hitam/navy, tipografi emas, marble texture, premium feel |
| **Rustic** | Tekstur kayu/linen, earthy tones, vintage typography, warm |
| **Islamic Modern** | Ornamen geometris Islam, kaligrafi, ungu/hijau tua, gold accent |
| **Chinese / Peranakan** | Merah & emas, motif bunga Cina, modern fusion, festive |

### Cara Tambah Template Baru
```bash
npm run make:template <slug>
# → scaffold boilerplate di templates/
# → paste HTML dari sumber ke dalam component
# → sesuaikan dengan useInvitationTemplate() composable
# → daftarkan di registry.js
# → tambah record di tabel templates via seeder atau tinker
```

---

## 8. Current State (per 2026-04-25)

### Sudah Selesai ✅
- Invitation editor (full flow, semua steps)
- Template system — Nusantara + Pearl production-ready, registry.js tersedia
- Guest List Manager — import CSV, WhatsApp blast, RSVP tracking, personalized URL
- Subscription + Payment — Midtrans Snap + Mayar terintegrasi
- Transaction history halaman dashboard
- Wedding checklist + budget tracker
- Admin panel — users, plans, templates, transaksi
- Google OAuth login
- Section customization per invitation (toggle section on/off)
- Invitation duplication + template switching
- Artikel/blog dashboard

### Sedang Dikerjakan / Planned 📋
- 🚧 MinangTemplate (WIP, belum masuk registry)
- 📋 25+ template baru
- 📋 Halaman kontak
- 📋 Legal pages (syarat & ketentuan, privacy policy)
- 📋 Guest list improvements
- 📋 Section lock untuk template premium (free tier lihat tapi tidak bisa edit)

---

## 9. How to Help AI

Instruksi untuk AI yang membaca file ini:

1. **Bahasa:** Jawab dalam Bahasa Indonesia kecuali diminta lain oleh user
2. **Stack:** Jangan sarankan ganti tech stack. Tetap pakai Laravel + Vue 3 + Inertia.js + Tailwind CSS
3. **Konvensi kode:** Ikuti architecture rules di Section 3 saat suggest code:
   - Backend: UUID, Service pattern, Form Request, Policy, PHP Enum
   - Frontend: `<script setup>`, Composition API, Tailwind only, no Pinia
4. **Template brainstorming:**
   - Fokus pada konteks pernikahan Indonesia
   - Pertimbangkan nilai-nilai lokal, budaya, dan estetika Indonesia
   - Gunakan style vocabulary di Section 7 sebagai referensi
   - Saat suggest warna/font, pertimbangkan compatibility dengan kustomisasi yang ada
5. **Scope produk:** TheDay HANYA untuk undangan **pernikahan**. Tidak ada fitur ulang tahun, gathering, atau event lain
6. **UUID:** Selalu gunakan UUID (bukan integer) saat suggest schema atau query
7. **Inertia patterns:** Gunakan `useForm()`, `router.visit()`, `usePage()` — bukan axios/fetch langsung
8. **Mobile-first:** 90%+ tamu buka undangan dari HP via WhatsApp. Prioritaskan mobile layout
```

- [ ] **Step 2: Verifikasi file terbuat**

```bash
ls -la AI_CONTEXT.md
wc -l AI_CONTEXT.md
```

Expected: file ada, panjang ~200+ baris.

- [ ] **Step 3: Commit**

```bash
git add AI_CONTEXT.md docs/superpowers/specs/2026-04-25-ai-context-doc-design.md docs/superpowers/plans/2026-04-25-ai-context-doc.md
git commit -m "docs: add AI_CONTEXT.md for onboarding other AI assistants"
```

---

## Self-Review

**Spec coverage:**
- ✅ Section 1 Project Identity → ada
- ✅ Section 2 Tech Stack → ada (tabel lengkap)
- ✅ Section 3 Architecture Rules → ada (backend + frontend)
- ✅ Section 4 Features → ada (semua fitur utama)
- ✅ Section 5 Business Rules → ada (free/premium, lifecycle, auth flow)
- ✅ Section 6 Database Schema → ada (ringkas tapi lengkap)
- ✅ Section 7 Template System → ada (cara kerja, existing templates, kustomisasi, sections, style vocab)
- ✅ Section 8 Current State → ada
- ✅ Section 9 How to Help AI → ada

**Placeholder scan:** Tidak ada TBD/TODO ✅

**Consistency:** Semua section_key di Step 1 match dengan composable yang ada ✅
