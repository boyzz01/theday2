# PRD — Wedding Checklist MVP v2
Produk: TheDay
Owner: Product / Founder
Status: Draft for Engineering
Versi: 2.0

## Ringkasan
Wedding Checklist adalah modul dashboard TheDay untuk membantu user memantau persiapan pernikahan secara sederhana, ringan, dan mobile-first. Fitur ini bertujuan meningkatkan repeat visit dashboard, memperluas value produk di luar undangan digital, dan menjadi fondasi menuju wedding control center ringan.

## Problem Statement
User TheDay sering hanya aktif saat membuat dan menyebarkan undangan, lalu engagement turun. Persiapan pernikahan sebenarnya masih berjalan, tetapi user belum punya alat sederhana di dashboard untuk memantau apa yang sudah selesai, apa yang terlambat, dan apa yang perlu dilakukan berikutnya.

## Goal
- Meningkatkan frekuensi kunjungan ulang ke dashboard
- Memberi utilitas nyata setelah undangan dipublish
- Menambah perceived value TheDay tanpa mengubah positioning inti sebagai platform undangan digital premium

## Non-Goal
- Bukan project management tool lengkap
- Bukan kolaborasi real-time pasangan/WO
- Bukan vendor marketplace
- Bukan reminder automation pada fase MVP

## User Persona
Pasangan Indonesia usia 24–32 tahun yang sedang mempersiapkan pernikahan 3–12 bulan ke depan, aktif menggunakan layanan digital, dan lebih sering mengakses dashboard via mobile. [file:1]

## JTBD
- Saat saya sedang menyiapkan pernikahan, saya ingin tahu apa yang harus dikerjakan berikutnya agar persiapan tidak tercecer.
- Saat saya membuka dashboard TheDay, saya ingin melihat progres persiapan dengan cepat tanpa merasa sedang memakai aplikasi kerja.
- Saat ada kebutuhan mendadak, saya ingin bisa menambah task pribadi dengan cepat dari HP.

## Success Metrics
### North Star
- 30% user yang memiliki undangan aktif membuka halaman checklist minimal 1 kali dalam 14 hari pertama setelah fitur tersedia

### Supporting Metrics
- 20% user checklist kembali membuka checklist minimal 3 kali dalam 30 hari
- Rata-rata minimal 5 task ditoggle/create per user checklist dalam 30 hari
- CTR overview widget ke halaman checklist minimal 8%
- Retention D7 user yang pernah membuka checklist lebih tinggi dibanding user yang tidak membuka checklist

## Scope Prioritas
### P0 — Wajib di MVP
- Default checklist otomatis berbasis template
- Create task manual
- Edit task
- Toggle complete / uncomplete
- Archive/delete task
- Progress summary total
- Filter by status
- Filter by category
- Mobile-friendly checklist page
- Entry point dari sidebar dashboard
- Persist data antar sesi login

### P1 — Jika sempat dalam MVP
- Search task
- Sort task
- Grouping by timeline
- Grouping by category
- Overview widget di dashboard
- Empty state celebratory

### P2 — Post-MVP / Nice to have
- Suggested task dari aktivitas user
- Auto-complete task dari modul lain
- Progress per kategori
- Premium differentiator
- Reminder automation
- Shared checklist

## Out of Scope
- Multi-user collaboration real-time
- WhatsApp/email reminder otomatis
- File attachment
- Komentar per task
- Drag and drop kanban
- AI-generated personalization
- Vendor marketplace integration
- Recurring task

## Informasi Arsitektur Produk
### Entitas utama
1. WeddingPlan
2. ChecklistTask
3. ChecklistTemplate

### Ownership model
- Setiap user memiliki 1 wedding plan aktif
- Wedding plan dapat terhubung ke 0 atau 1 invitation utama pada MVP
- Checklist task bisa bersifat:
  - system-generated
  - user-generated

### Keputusan penting
Untuk MVP, checklist diposisikan sebagai bagian dari wedding plan utama user, bukan per event terpisah. Jika nanti ada akad/lamaran/resepsi terpisah, itu masuk fase premium/post-MVP.

## User Flow
### First-time flow
1. User masuk ke menu Checklist
2. Jika checklist belum pernah diinisialisasi, sistem otomatis membuat default checklist
3. Jika tanggal acara tersedia, due date task system-generated dihitung dari tanggal acara
4. Jika tanggal acara belum tersedia, task dibuat tanpa due date dan sistem menampilkan prompt untuk melengkapi tanggal acara
5. User melihat progress awal dan dapat langsung toggle atau tambah task

### Returning flow
1. User membuka Checklist
2. Sistem menampilkan task aktif, progress summary, dan task due terdekat
3. User dapat filter, cari, edit, dan toggle task
4. Semua perubahan tersimpan otomatis

## Data Model
### Table: wedding_plans
- id
- user_id
- primary_invitation_id nullable
- event_date nullable
- checklist_initialized_at nullable
- created_at
- updated_at

### Table: checklist_tasks
- id
- wedding_plan_id
- invitation_id nullable
- source enum(system, user)
- template_id nullable
- title
- description nullable
- category enum(administrasi, venue, vendor, busana, undangan, tamu, acara, dokumentasi, lainnya)
- priority enum(low, medium, high)
- status enum(todo, done, archived)
- due_date nullable
- sort_order nullable
- is_user_modified boolean default false
- completed_at nullable
- archived_at nullable
- created_at
- updated_at
- deleted_at nullable

### Table: checklist_templates
- id
- name
- category
- title
- description nullable
- day_offset nullable
- priority enum(low, medium, high)
- is_active boolean
- created_at
- updated_at

## Business Rules
- Default checklist hanya diinisialisasi 1 kali per wedding_plan
- Task source=system dibuat dari checklist_templates aktif
- Task source=user selalu dibuat manual oleh user
- Progress hanya menghitung task dengan status todo dan done
- Task archived tidak masuk progress
- Task done bisa diubah kembali ke todo
- Task archived bisa di-restore ke todo
- Task system yang sudah diedit user ditandai is_user_modified=true
- Saat event_date berubah, hanya task source=system dan is_user_modified=false yang due_date-nya direcalculate
- Task user tidak pernah diubah otomatis oleh sistem
- Jika event_date kosong, task system tetap dibuat tanpa due_date
- Jika user menghapus invitation utama, checklist tetap hidup di wedding_plan utama

## Recalculation Rules
### Saat event_date ditambahkan
- Semua task system dengan template day_offset mendapat due_date berdasarkan event_date

### Saat event_date diubah
- Recalculate hanya untuk task system yang belum dimodifikasi user

### Saat event_date dihapus
- Due date task system menjadi null hanya jika task belum dimodifikasi user
- Task user tetap tidak berubah

## State Transition
### Status task
- todo -> done
- done -> todo
- todo -> archived
- done -> archived
- archived -> todo

### Constraint
- archived tidak dapat langsung menjadi done; harus restore ke todo terlebih dahulu

## Endpoint Scope
### Checklist page
- GET /dashboard/checklist

### API / action
- POST /checklist/initialize
- GET /checklist/tasks
- POST /checklist/tasks
- PATCH /checklist/tasks/{id}
- PATCH /checklist/tasks/{id}/toggle
- PATCH /checklist/tasks/{id}/archive
- PATCH /checklist/tasks/{id}/restore
- GET /checklist/summary

## Request / Response Rules
### Create task validation
- title: required, 3–120 karakter
- category: required
- priority: optional, default medium
- due_date: optional, valid date
- description: optional, max 500 karakter

### Update rules
- User dapat mengedit title, description, category, priority, due_date
- Edit pada task system akan set is_user_modified=true

## UI Requirements
### Checklist page minimum
- Page title
- Subtitle singkat
- Summary cards
- Progress bar
- Filter status
- Filter category
- Search input (P1)
- Group switch timeline/category (P1)
- Task list
- Add task button / FAB mobile
- Empty state
- Error state
- Loading/skeleton state

### Task card minimum
- Checkbox/toggle
- Task title
- Due date
- Category badge
- Priority badge
- Edit action
- More menu: archive/delete

## UX Copy Principles
- Hangat
- Ringkas
- Tidak menghakimi
- Tidak memberi rasa tertinggal/panik

## Edge Cases
1. User membuka checklist pertama kali tanpa event_date
2. User menambahkan event_date setelah checklist dibuat
3. User mengubah event_date dari jauh ke dekat
4. User mengubah due_date task system secara manual
5. User archive semua task system
6. User restore task archived
7. User toggle task selesai lalu edit due_date
8. User punya invitation tapi belum publish
9. User menghapus invitation terkait task
10. User punya >100 task
11. Tidak ada koneksi / request gagal saat toggle
12. Dua request update datang hampir bersamaan

## Error Handling
- Jika initialize gagal, tampilkan retry state
- Jika toggle gagal, UI rollback ke status sebelumnya
- Jika create/update gagal, tampilkan error inline pada form
- Jika summary gagal dimuat, task list tetap boleh tampil
- Semua error menggunakan copy yang ramah dan tidak teknis

## Analytics Spec
### Event list
- checklist_page_viewed
- checklist_initialized
- checklist_task_created
- checklist_task_updated
- checklist_task_completed
- checklist_task_uncompleted
- checklist_task_archived
- checklist_task_restored
- checklist_filter_applied
- checklist_search_used
- checklist_view_mode_changed
- checklist_overview_widget_clicked

### Event properties minimum
- user_id
- wedding_plan_id
- invitation_id nullable
- source_task
- category
- priority
- due_date_exists
- task_status
- device_type

## Acceptance Criteria
### Functional
- User dapat membuka halaman Checklist dari sidebar dashboard
- Saat checklist belum ada, sistem menginisialisasi task default maksimal 1 kali
- User dapat membuat task manual baru
- User dapat mengedit task manual maupun task system
- User dapat toggle todo <-> done
- User dapat archive dan restore task
- Progress total diperbarui setelah create, toggle, archive, restore
- Filter status dan kategori bekerja tanpa reload penuh halaman
- Data tetap tersimpan saat user refresh halaman atau login ulang

### UX
- Pada lebar viewport 360px, halaman checklist dapat digunakan tanpa horizontal scroll
- Tombol tambah task terlihat dan dapat diakses pada mobile
- Empty state tampil jika tidak ada task aktif
- Loading state tampil saat data awal dimuat
- Error state tampil jika request gagal

### Performance
- Initial page load checklist target < 2.5 detik pada koneksi normal
- Toggle task memberi feedback visual dalam < 300ms
- Summary update tampil maksimal < 1 detik setelah aksi berhasil

## QA Scenarios
- Inisialisasi checklist saat user baru
- Re-open checklist saat user lama
- Edit task system dan cek is_user_modified=true
- Ubah event_date lalu verifikasi due date yang direcalculate
- Archive task lalu pastikan progress berubah
- Restore archived task lalu pastikan task muncul lagi
- Test mobile viewport
- Test empty state, loading state, error state
- Test filter kombinasi status + kategori
- Test race condition sederhana pada toggle cepat

## Rollout Plan
### Phase 1
- Internal testing / staging
- Seed template awal
- Uji dengan akun dummy dan akun existing

### Phase 2
- Release behind feature flag untuk sebagian user aktif

### Phase 3
- Full rollout jika activation dan error rate sehat

## Dependencies
- Data tanggal acara utama user
- Dashboard sidebar/menu
- Sistem auth existing
- Tracking analytics internal
- Komponen UI dashboard yang reusable

## Risiko dan Mitigasi
| Risiko | Dampak | Mitigasi |
|---|---|---|
| Checklist terasa seperti aplikasi kerja | Adopsi rendah | Gunakan microcopy hangat, field sederhana, tampilan ringan |
| Scope creep di MVP | Delivery meleset | Tetapkan P0/P1/P2 secara disiplin |
| Due date kacau saat event_date berubah | Trust user turun | Recalculate hanya task system yang belum dimodifikasi user |
| Terlalu sedikit task default | Value tidak terasa | Seed template cukup representatif |
| Terlalu banyak task default | User stres | Batasi task awal yang benar-benar penting |

## Definition of Done
- Semua acceptance criteria functional lulus
- Semua QA scenario P0 lulus
- Mobile dan desktop responsive
- Tidak ada bug blocker/critical terbuka
- Analytics event utama terkirim
- Error state dan loading state tersedia
- Copy sesuai tone brand TheDay