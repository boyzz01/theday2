# specUI.md

## Tujuan Dokumen
Dokumen ini menerjemahkan spesifikasi fitur Budget Planner menjadi spesifikasi UI/UX yang siap dipakai untuk desain layar, handoff ke developer, dan penyusunan komponen antarmuka. Fokus utama adalah membuat pengalaman yang sederhana, menenangkan, jelas secara visual, dan nyaman digunakan di mobile untuk pasangan yang sedang mengelola anggaran pernikahan.

## Prinsip UX
- Calm and helpful: antarmuka harus membantu user merasa lebih terkontrol, bukan merasa dihakimi.
- Simple first: prioritaskan tugas inti, yaitu set budget, lihat progres, tambah pengeluaran, dan pantau kategori bermasalah.
- Mobile-first: semua aksi utama harus bisa dilakukan cepat dari layar HP dengan satu tangan.
- Visual clarity: user harus bisa memahami kondisi budget dalam kurang dari 3 detik.
- Localized: semua angka menggunakan format Rupiah, istilah kategori relevan untuk pernikahan Indonesia, dan microcopy memakai Bahasa Indonesia yang natural.
- Wedding companion, not finance app: nuansa UI harus terasa hangat, elegan, ringan, dan tetap fungsional.

## Sasaran Pengguna
Target utama adalah pasangan Indonesia usia 24–32 tahun yang sedang merencanakan pernikahan dan butuh cara yang lebih praktis daripada spreadsheet untuk memantau budget. Mereka sering membuka dashboard lewat HP, terutama saat diskusi dengan vendor, sehingga akses cepat ke total budget, sisa dana, dan status kategori menjadi kebutuhan inti.

## Outcome UX
UI harus membantu user:
- Mengetahui total budget, total terpakai, dan sisa dana secara instan.
- Melihat kategori yang aman, mendekati batas, atau sudah overbudget.
- Menambah dan memperbarui item pengeluaran dengan effort minimal.
- Mencari item tertentu dengan cepat.
- Tetap merasa tenang walau ada kategori yang mulai membengkak.

## Arsitektur Informasi
### Posisi di navigasi
Budget Planner diletakkan di sidebar dashboard, dekat dengan Checklist Pernikahan, karena keduanya termasuk modul inti persiapan wedding.

### Struktur halaman utama
Urutan blok konten pada halaman Budget Planner:
1. Header halaman
2. Summary cards
3. Progress budget total
4. Toggle tampilan breakdown
5. Daftar category cards
6. Search dan filter
7. Daftar item pengeluaran
8. Floating / sticky primary CTA di mobile

## Daftar Layar
Layar minimum untuk MVP:
- Dashboard Overview dengan widget Budget Planner
- Budget Planner Main Page
- Bottom sheet / modal filter
- Bottom sheet / modal tambah item
- Bottom sheet / modal edit item
- Bottom sheet / modal kelola kategori
- Empty state pertama kali
- State tanpa hasil pencarian/filter
- Archive / delete confirmation dialog

## Dashboard Overview Widget
### Tujuan
Memberi snapshot cepat tentang kondisi anggaran dan mendorong user masuk ke modul Budget Planner.

### Isi komponen
- Label: Budget Planner
- Budget terpakai / total budget
- Persentase penggunaan
- Jumlah kategori overbudget
- Mini progress bar
- CTA: Lihat budget

### Perilaku
- Klik widget membawa user ke halaman Budget Planner.
- Jika total budget belum diisi, widget tetap tampil dengan state edukatif seperti “Atur budget pertamamu”.
- Jika belum ada item, widget menampilkan CTA lembut untuk mulai mencatat pengeluaran.

### Prioritas visual
Angka terpakai dan sisa budget harus lebih dominan daripada elemen dekoratif. Progress bar harus terlihat sekilas tanpa memakan ruang berlebihan.

## Halaman Budget Planner
### Header
Komponen header:
- Page title: Budget Planner
- Subtitle singkat, misalnya: “Pantau rencana dan realisasi budget pernikahanmu.”
- Aksi sekunder: Kelola kategori
- Aksi primer: Tambah item

### Hierarki informasi
Fold pertama harus langsung memperlihatkan kondisi budget tanpa perlu scroll panjang. Di mobile, susunan elemen harus mengikuti urutan: title, summary cards, progress bar, CTA, lalu breakdown.

## Summary Cards
### Kartu yang ditampilkan
Minimal tampil 4 kartu utama:
- Total budget target
- Total planned
- Total actual spent
- Sisa budget

Kartu tambahan opsional bila ruang cukup:
- Selisih planned vs actual
- Jumlah kategori overbudget

### Aturan desain
- Gunakan angka besar dan mudah discan.
- Label harus singkat dan familiar.
- Variasi warna harus lembut; jangan gunakan merah menyala sebagai warna dominan.
- Pada mobile, kartu bisa ditampilkan dalam horizontal scroll atau grid 2 kolom, selama tetap mudah dibaca.

### Status visual
- Aman: tone netral atau hijau lembut.
- Near limit: tone amber lembut.
- Overbudget: tone coral / merah lembut, bukan merah agresif.

## Progress Budget Total
### Fungsi
Menunjukkan rasio actual spent terhadap total budget target.

### Komponen
- Label progres, misalnya “Budget terpakai”
- Progress bar
- Persentase penggunaan
- Teks penjelas di bawah bar, misalnya “Rp x dari Rp y sudah digunakan”

### Behavior
- Jika total budget belum diisi, progress bar diganti state informatif yang meminta user mengisi budget target.
- Jika actual melebihi total budget, bar tetap penuh dan menampilkan badge overbudget serta nominal selisih.

## Toggle Breakdown View
### Mode yang tersedia
- By Category, default
- By Item List

### Pola interaksi
Gunakan segmented control atau tab switch sederhana. Default harus ke By Category karena paling mudah dipahami user baru.

## Category Cards
### Tujuan
Membantu user memahami performa setiap kategori secara cepat.

### Isi category card
- Nama kategori
- Planned total
- Actual total
- Sisa kategori
- Progress visual kategori
- Status badge: Normal / Mendekati limit / Overbudget
- CTA: Lihat detail

### State kategori
- Normal jika actual masih jauh di bawah planned.
- Mendekati limit jika actual mendekati planned.
- Overbudget jika actual melebihi planned.

### Rekomendasi rule visual
- Gunakan indikator warna dan badge secara ringan.
- Jangan hanya mengandalkan warna; sertakan label teks status.
- Kategori overbudget harus terlihat jelas, tetapi tetap terasa suportif.

### Interaction
- Tap pada card membuka filtered item list untuk kategori tersebut, atau expand detail jika desain memilih pola accordion.
- Long list kategori di mobile sebaiknya berupa vertical cards, bukan tabel.

## Item List
### Tujuan
Memberi detail pengeluaran secara praktis dan mudah di-scan.

### Informasi per item
- Nama item
- Kategori
- Vendor / catatan singkat bila ada
- Planned amount
- Actual amount
- Status pembayaran
- Tanggal pembayaran
- Aksi edit
- Aksi more menu

### Desktop pattern
Gunakan table ringan dengan kolom yang tetap jelas dan tidak terlalu rapat.

### Mobile pattern
Gunakan stacked cards, bukan tabel horizontal penuh. Urutan informasi di card:
1. Nama item
2. Kategori + status pembayaran
3. Planned dan actual amount
4. Tanggal pembayaran
5. Aksi edit / more

### Status pembayaran
Gunakan chip/badge:
- Belum bayar
- DP
- Lunas

## Search, Filter, dan Sort
### Search
- Diletakkan di atas item list.
- Placeholder: “Cari item atau vendor”
- Search harus bekerja untuk nama item dan vendor/catatan.

### Filter
Filter minimum:
- Semua kategori
- Overbudget
- Belum dibayar
- DP
- Lunas
- Ada actual amount
- Belum ada actual amount

### Sort
Sort minimum:
- Nominal terbesar
- Nominal terkecil
- Tanggal terbaru
- Kategori
- Status pembayaran

### Pola mobile
Filter dan sort digabung dalam bottom sheet agar hemat ruang. Gunakan tombol ringkas seperti “Filter” dan “Urutkan” di atas list.

## Form Tambah/Edit Item
### Tujuan
Memungkinkan user menambah atau memperbarui pengeluaran dengan cepat tanpa friction tinggi.

### Field form
- Nama item, wajib
- Kategori, wajib
- Vendor / catatan singkat, opsional
- Planned amount
- Actual amount
- Status pembayaran
- Tanggal pembayaran, opsional
- Kaitan invitation tertentu atau wedding plan global bila diperlukan sistem

### Prioritas input
Field paling penting harus muncul lebih dulu: nama item, kategori, planned amount, actual amount, status pembayaran.

### Pola interaksi
- Di mobile, gunakan modal full-screen atau bottom sheet tinggi agar fokus.
- Simpan tombol CTA sticky di bawah.
- Sediakan secondary action: Batal.
- Validasi ditampilkan inline, bukan alert global yang mengganggu.

### Format input nominal
- User boleh mengetik angka polos.
- UI menampilkan preview atau formatting Rupiah secara real-time/semi real-time.
- Hindari format yang membuat cursor sulit dikontrol saat mengetik di mobile.

## Kelola Kategori
### Fitur minimum
- Lihat daftar kategori default dan custom
- Tambah kategori custom
- Edit nama kategori custom
- Archive kategori

### UX notes
- Kategori default tidak perlu dihapus permanen di MVP.
- Gunakan penjelasan singkat bahwa archive akan menyembunyikan kategori dari list aktif.
- Jika kategori punya item aktif, tampilkan konfirmasi yang jelas sebelum archive.

## First-Time Experience
### Tujuan onboarding
Membantu user mulai tanpa rasa kosong atau bingung.

### Alur ideal
1. User membuka Budget Planner pertama kali.
2. Muncul intro singkat yang menjelaskan manfaat fitur.
3. User diminta mengisi total budget target.
4. Sistem otomatis membuat kategori default.
5. User diarahkan ke CTA tambah pengeluaran pertama.

### Prinsip onboarding
- Ringkas, tidak lebih dari 1–2 langkah utama.
- Bisa dilewati.
- Gunakan copy yang suportif, bukan instruktif berlebihan.

## Empty States
### Belum set budget
Tampilkan pesan edukatif bahwa fitur tetap bisa dipakai, tetapi ringkasan akan lebih akurat jika total budget diisi.

### Belum ada item
Gunakan copy yang menenangkan, contohnya:
“Mulai catat pengeluaran pertamamu agar budget pernikahan tetap terkontrol.”

### Tidak ada hasil pencarian
Gunakan empty state netral seperti:
“Belum ada hasil yang cocok. Coba kata kunci atau filter lain.”

### Belum ada kategori custom
Tampilkan penjelasan bahwa kategori default sudah siap dipakai dan user bisa menambah kategori khusus bila diperlukan.

## Feedback States
### Success
- Setelah item berhasil ditambah atau diubah, tampilkan toast ringan.
- Contoh: “Pengeluaran berhasil disimpan.”

### Error
- Gunakan bahasa yang jelas dan tidak teknis.
- Contoh: “Data belum bisa disimpan. Coba lagi sebentar.”

### Confirmation
Untuk delete/archive, gunakan dialog yang menjelaskan dampak tindakan.
Contoh: “Arsipkan item ini? Data tidak dihitung dalam ringkasan aktif.”

## Microcopy Guidelines
### Tone of voice
- Hangat
- Tenang
- Tidak menghakimi
- Praktis
- Ramah untuk user non-finance

### Hindari
- Istilah akuntansi yang terlalu formal
- Copy yang terasa menakut-nakuti, seperti “Anda gagal mengontrol budget”
- Pesan error yang kaku dan teknis

### Contoh microcopy
- “Pantau pengeluaran pernikahanmu dengan lebih tenang.”
- “Lihat kategori yang mulai mendekati batas budget.”
- “Tambah pengeluaran baru agar totalmu tetap akurat.”

## Visual Design Direction
### Karakter visual
- Elegan
- Hangat
- Modern
- Ringan
- Selaras dengan brand wedding TheDay

### Warna
- Basis warna netral terang
- Aksen warm muted untuk elemen penting
- Hijau lembut untuk status aman
- Amber lembut untuk near limit
- Coral / soft red untuk overbudget

### Tipografi
- Gunakan hierarki tegas pada angka utama.
- Pastikan label dan nominal tetap terbaca jelas di layar kecil.
- Hindari ukuran font terlalu kecil pada metadata item.

### Ikonografi
Gunakan ikon sederhana dan konsisten, misalnya dompet, tag, calendar, warning lembut, dan filter. Ikon hanya membantu pemindaian, bukan elemen utama.

## Komponen Inti
Komponen design system yang perlu disiapkan:
- Summary card
- Progress bar
- Category card
- Item card
- Table row
- Status badge
- Payment chip
- Search field
- Filter pill
- Bottom sheet
- Modal form
- Toast
- Empty state block
- Confirmation dialog
- Sticky CTA button

## Responsiveness
### Mobile
- Prioritas utama.
- Semua CTA penting harus mudah dijangkau ibu jari.
- Gunakan single-column layout.
- Hindari tabel lebar.
- Filter, sort, dan form sebaiknya berbentuk sheet/modal.

### Tablet / Desktop
- Summary cards bisa tampil lebih lebar.
- Category breakdown dan item list dapat ditampilkan dalam layout dua area bila ruang memungkinkan.
- Tabel item boleh digunakan, tetapi tetap pertahankan keterbacaan dan whitespace.

## Accessibility
- Kontras teks dan angka utama harus cukup tinggi.
- Jangan hanya mengandalkan warna untuk status; gunakan label teks.
- Tap target minimal harus nyaman untuk mobile.
- Format nominal dan label harus mudah dibaca screen reader bila memungkinkan.
- Error message harus terhubung jelas dengan field terkait.

## Aturan Perhitungan yang Harus Tercermin di UI
- Total planned = jumlah planned dari item aktif.
- Total actual = jumlah actual dari item aktif.
- Sisa budget = total budget - total actual.
- Overbudget total jika total actual > total budget.
- Overbudget kategori jika total actual kategori > total planned kategori.
- Item archived tidak ikut dihitung dalam summary aktif.
- Actual kosong dapat dipresentasikan sebagai “Belum dicatat” di UI, walau secara kalkulasi dapat diperlakukan sebagai 0.

## Rekomendasi Struktur Section di Halaman Utama
### Mobile
- Header
- Summary horizontal cards / grid
- Progress total
- CTA tambah item
- Toggle by category / by item
- Category cards
- Search + filter row
- Item list

### Desktop
- Header + actions
- Summary cards row
- Progress total
- Breakdown controls
- Category cards grid
- Search/filter/sort row
- Item table

## Wireframe Low-Fidelity Teks
### Mobile main page
- App bar: Budget Planner | Kelola Kategori
- Subtitle pendek
- 2x2 summary cards
- Progress bar total budget
- Tombol utama: Tambah Item
- Segmented control: Kategori | Item
- Card kategori berulang
- Search bar
- Tombol Filter dan Sort
- Item cards berulang

### Mobile add item
- Header: Tambah Pengeluaran
- Input nama item
- Select kategori
- Input planned amount
- Input actual amount
- Select status pembayaran
- Input tanggal pembayaran
- Input vendor/catatan
- Sticky button: Simpan

### Desktop main page
- Header kiri: title + subtitle
- Header kanan: Kelola kategori, Tambah item
- Row summary cards
- Full-width progress bar
- Grid category cards
- Search/filter/sort controls
- Table item list

## Prioritas Implementasi Desain
Urutan prioritas UI/UX:
1. Main page Budget Planner
2. Add/Edit item flow
3. Overview widget
4. Manage categories
5. Empty, loading, error, confirmation states
6. Responsive polish desktop

## Handoff Notes untuk Developer
- Fokuskan performa pada mobile karena halaman akan sering dibuka saat user di lapangan atau bertemu vendor.
- Pastikan format Rupiah konsisten di card, form, table, badge, dan toast.
- Jika data kosong, jangan tampilkan layar yang terasa kosong total; selalu beri arahan tindakan berikutnya.
- Gunakan state yang lembut untuk overbudget agar tetap informatif tanpa menambah stres user.
- Jangan membuat layout terasa seperti software akuntansi; pertahankan feel sebagai wedding planning companion.

## Deliverables Desain yang Disarankan
- High-fidelity mobile screen untuk main page
- High-fidelity mobile screen untuk add/edit item
- High-fidelity mobile screen untuk filter sheet
- High-fidelity desktop main page
- Component spec untuk summary card, category card, item row/card, badge, dan progress bar
- Copy deck untuk empty, error, success, dan onboarding state
