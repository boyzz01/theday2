# Spec: Wedding Planner Enhancements
Last updated: 2026-04-20  
Product: TheDay  
Module: Wedding Planner

## Latar Belakang

Wedding Planner saat ini sudah memiliki struktur dasar yang baik:
- Ringkasan progress
- Input tanggal acara
- Filter status, kategori, prioritas
- Daftar task per kategori
- Tambah, edit, hapus task

Namun, pengalaman saat ini masih terasa seperti checklist statis. User belum langsung terbantu untuk memahami:
- task mana yang paling mendesak,
- task mana yang sudah melewati tenggat,
- task mana yang harus dikerjakan minggu ini,
- siapa yang bertanggung jawab atas task tertentu,
- dan task besar terdiri dari subtugas apa saja.

Agar Wedding Planner terasa seperti “asisten persiapan nikah”, perlu ditambahkan sistem deadline yang lebih jelas, grouping berbasis urgency, subtasks, assignee, dan reminder-ready structure.

---

## Tujuan

Meningkatkan kegunaan Wedding Planner agar:
- lebih actionable setiap hari,
- lebih mudah dipahami secara cepat,
- cocok dipakai pasangan bersama keluarga/WO,
- siap dikembangkan ke reminder otomatis di tahap berikutnya.

---

## Goals

- Menampilkan urgency task dengan jelas
- Membantu user fokus pada task yang dekat deadline
- Memecah task besar menjadi subtasks
- Mendukung kolaborasi ringan antar pihak
- Menyiapkan fondasi untuk fitur reminder otomatis

## Non-Goals

- Belum membangun integrasi WhatsApp reminder
- Belum membangun calendar sync ke Google Calendar
- Belum membangun workflow approval multi-user yang kompleks
- Belum membangun automasi vendor management penuh

---

## Problem Statement

User dapat melihat daftar task, tetapi belum mendapat konteks:
- kapan task harus selesai,
- apakah task terlambat,
- apa yang perlu dikerjakan minggu ini,
- siapa yang harus mengerjakan,
- dan langkah kecil apa saja di dalam task besar.

Akibatnya:
- planner terasa pasif,
- user harus berpikir sendiri untuk prioritas,
- task besar terlihat membingungkan,
- dan progress sulit terasa nyata.

---

## Solusi

Tambahkan 5 peningkatan utama:
1. Due date dan status urgency per task
2. Smart grouping berdasarkan deadline
3. Subtasks
4. Assignee / PIC task
5. Reminder-ready infrastructure

---

# 1. Due Date dan Urgency

## Deskripsi

Setiap task memiliki tanggal target yang dihitung otomatis dari tanggal acara, atau bisa diatur manual oleh user.

Task akan menampilkan informasi seperti:
- H-180
- Due 12 Mei 2026
- Terlambat 3 hari
- Hari ini
- Besok

## Tujuan

Agar user langsung tahu mana task yang harus diprioritaskan tanpa membuka detail task.

## Functional Requirements

- Setiap task memiliki field `due_date`
- Jika user mengisi tanggal acara, sistem dapat menghitung `due_date` otomatis berdasarkan offset template
- User tetap bisa override `due_date` secara manual
- Task card menampilkan salah satu badge:
  - Terlambat
  - Hari ini
  - Besok
  - Minggu ini
  - Upcoming
- Task tanpa due date tetap bisa ada, tetapi diberi label “Tanpa tenggat”

## UX Notes

- Badge urgency harus lebih menonjol daripada label prioritas
- Gunakan warna hati-hati:
  - merah = terlambat
  - oranye = mendekati deadline
  - abu/hijau = aman / selesai
- Format yang disarankan:
  - `H-120`
  - `Due 12 Mei`
  - `Lewat 3 hari`

---

# 2. Smart Grouping

## Deskripsi

Selain grouping per kategori, user bisa melihat task berdasarkan urgency.

## Mode grouping baru

- Berdasarkan kategori
- Berdasarkan deadline
- Berdasarkan assignee

## Group deadline

Jika mode “deadline” aktif, task dibagi menjadi:
- Terlambat
- Hari ini
- 7 hari ke depan
- 30 hari ke depan
- Nanti
- Selesai

## Tujuan

Membantu user fokus pada apa yang harus dilakukan sekarang, bukan hanya melihat daftar berdasarkan tema.

## Functional Requirements

- Tambah dropdown `Group by`
- Opsi:
  - Kategori
  - Deadline
  - Penanggung jawab
- Default tetap `Kategori`
- Sistem menyusun task otomatis berdasarkan due date

## UX Notes

- Mode “deadline” sangat cocok jadi default sekunder setelah user mengisi tanggal acara
- Bisa ditambahkan shortcut tab:
  - Semua
  - Mendesak
  - Minggu ini
  - Selesai

---

# 3. Subtasks

## Deskripsi

Task besar bisa dipecah menjadi langkah-langkah kecil.

Contoh:
Task: Urus dokumen & buku nikah  
Subtasks:
- Siapkan KTP
- Siapkan KK
- Siapkan akta lahir
- Siapkan pas foto
- Cek syarat KUA

## Tujuan

Agar task besar terasa lebih ringan, jelas, dan mudah dicentang progresnya.

## Functional Requirements

- Task dapat memiliki banyak subtasks
- Subtask minimal memiliki:
  - title
  - is_completed
  - sort_order
- Progress task parent dapat dihitung dari subtasks
- Jika semua subtasks selesai, task parent bisa otomatis ditandai selesai atau user diminta konfirmasi
- Subtask bisa ditambah, edit, hapus, reorder

## UX Notes

- Pada task list, cukup tampilkan ringkas:
  - `3/5 subtugas selesai`
- Detail subtasks bisa expandable inline
- Jangan membuat UI terlalu berat di halaman utama

---

# 4. Assignee / PIC

## Deskripsi

Task dapat diberi penanggung jawab.

Contoh assignee:
- Mempelai wanita
- Mempelai pria
- Orang tua
- Keluarga
- Wedding organizer
- Bersama

## Tujuan

Mengurangi kebingungan soal siapa yang harus mengerjakan apa.

## Functional Requirements

- Tambahkan field `assignee_type`
- Nilai default:
  - bride
  - groom
  - parents
  - family
  - wo
  - both
  - custom
- User dapat menambahkan label custom jika diperlukan
- Task card menampilkan assignee sebagai badge kecil

## UX Notes

- Cukup badge teks, tidak perlu avatar dulu
- Jika user tunggal, fitur ini tetap berguna untuk personal organization

---

# 5. Reminder-ready Infrastructure

## Deskripsi

Tahap ini belum harus mengirim reminder ke WhatsApp/email, tetapi struktur datanya harus siap.

## Tujuan

Agar ke depan bisa menambah reminder otomatis tanpa refactor besar.

## Functional Requirements

Tambahkan metadata reminder pada task:
- `reminder_enabled`
- `reminder_offset_days`
- `last_reminded_at`
- `next_reminder_at`

Default reminder offset:
- 30 hari sebelum
- 7 hari sebelum
- 1 hari sebelum

Tahap awal:
- hanya tampilkan toggle reminder di form task
- belum wajib kirim notifikasi eksternal
- bisa mulai dari in-app reminder badge

## Future Scope

- Email reminder
- Push notification
- WhatsApp reminder
- Reminder ke pasangan / assignee tertentu

---

# Perubahan UI

## Task Card

Tambahkan elemen berikut:
- checkbox status
- judul task
- deskripsi singkat
- badge prioritas
- badge urgency
- assignee badge
- info subtasks jika ada
- action icons

### Contoh isi card
- Urus dokumen & buku nikah
- Siapkan KTP, KK, akta lahir, dan dokumen lainnya
- Tinggi
- H-120
- Bride
- 2/5 subtugas selesai

## Summary Cards

Tambahkan ringkasan baru:
- Terlambat
- Deadline 7 hari
- Prioritas tinggi belum selesai

Summary lama tetap ada bila perlu:
- Progress
- Selesai
- Perlu dikerjakan
- Diarsipkan

Saran:
- Total card jangan terlalu banyak dalam satu baris
- Bisa pilih 4 utama + expandable insight

## Filter & Sorting

Tambahkan filter:
- Semua assignee
- Semua tenggat
- Dengan subtasks / tanpa subtasks

Tambahkan sorting:
- Deadline terdekat
- Prioritas tertinggi
- Terakhir diperbarui
- Nama A-Z

---

# User Flow

## Flow 1: User mengisi tanggal acara

1. User membuka Wedding Planner
2. User mengisi tanggal acara
3. Sistem menghitung due date semua task template
4. Task mendapatkan badge urgency
5. User dapat melihat grouping berdasarkan deadline

## Flow 2: User menyelesaikan task besar

1. User membuka task
2. User melihat subtasks
3. User mencentang subtasks satu per satu
4. Progress parent task ikut bergerak
5. Saat semua subtasks selesai, task parent ditandai siap diselesaikan

## Flow 3: User ingin fokus ke task paling mendesak

1. User buka filter/grouping
2. Pilih `Group by: Deadline`
3. Sistem menampilkan section:
   - Terlambat
   - Hari ini
   - 7 hari ke depan
4. User kerjakan task teratas dulu

---

# Data Model

## Table: wedding_planner_tasks

Tambahan field:
- `due_date` datetime nullable
- `due_offset_days` integer nullable
- `urgency_status` varchar nullable
- `assignee_type` varchar nullable
- `assignee_label` varchar nullable
- `reminder_enabled` boolean default false
- `reminder_offset_days` integer nullable
- `last_reminded_at` datetime nullable
- `next_reminder_at` datetime nullable
- `parent_task_id` bigint nullable
- `is_template_generated` boolean default false

## Table: wedding_planner_subtasks

Field:
- `id`
- `task_id`
- `title`
- `is_completed`
- `sort_order`
- `created_at`
- `updated_at`

Catatan:
- Jika ingin sederhana, subtasks juga bisa memakai self-reference pada table tasks
- Tetapi table terpisah lebih rapi untuk MVP yang jelas

---

# Business Rules

- Task selesai tidak otomatis diarsipkan
- Task tanpa due date tidak masuk kelompok “deadline”
- Jika user override due date manual, sistem tidak menimpa lagi kecuali user reset
- Jika parent task punya subtasks, progress parent mengikuti subtasks
- Assignee tidak wajib
- Reminder tidak wajib aktif

---

# Edge Cases

- User belum isi tanggal acara  
  -> task tetap tampil, tetapi tanpa urgency berbasis H-minus

- User mengubah tanggal acara setelah task disesuaikan manual  
  -> hanya task auto-generated yang ikut dihitung ulang; task manual override tetap aman

- Semua subtasks selesai, tetapi parent belum dicentang  
  -> tampilkan state “Siap diselesaikan”

- Task overdue tetapi status masih “disiapkan”  
  -> tetap masuk section “Terlambat”

---

# Success Metrics

## Product Metrics
- Naikkan completion rate task
- Naikkan jumlah task yang diberi due date
- Naikkan penggunaan filter/grouping
- Naikkan engagement mingguan pada Wedding Planner

## UX Metrics
- User lebih cepat menemukan task penting
- User lebih sedikit melewatkan task deadline dekat
- User lebih sering update planner setelah isi tanggal acara

---

# MVP Recommendation

Rilis bertahap:

## Phase 1
- Due date
- Urgency badge
- Sorting by nearest deadline
- Summary: terlambat + 7 hari ke depan

## Phase 2
- Group by deadline
- Subtasks
- Assignee badge

## Phase 3
- Reminder toggle
- In-app reminder
- Reminder center / notification log

---

# Prioritas Implementasi

## P1
- Due date
- Urgency badge
- Sort by nearest deadline
- Summary cards deadline

## P2
- Group by deadline
- Subtasks
- Assignee

## P3
- Reminder system foundation

---

# Open Questions

- Apakah assignee hanya label statis atau benar-benar terkait user account?
- Apakah subtasks perlu support due date masing-masing?
- Apakah task template bawaan akan berbeda untuk akad-only, resepsi, atau intimate wedding?
- Apakah reminder nantinya hanya in-app atau juga WhatsApp/email?
- Apakah mode default setelah isi tanggal acara tetap kategori atau berubah ke deadline?

---

# Rekomendasi

Fokus implementasi pertama sebaiknya pada fitur yang langsung meningkatkan clarity:
1. due date,
2. urgency badge,
3. sorting/grouping deadline.

Ini akan memberi efek UX paling besar dengan effort relatif lebih kecil dibanding fitur kolaborasi atau reminder eksternal.

Subtasks dan assignee sangat layak menyusul setelah fondasi urgency berjalan dengan baik.