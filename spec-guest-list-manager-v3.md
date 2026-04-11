# Spec Fitur Guest List Manager — TheDay

## Ringkasan
Fitur **Guest List Manager** adalah modul dashboard untuk membantu user mengelola daftar tamu, status pengiriman undangan, RSVP, dan distribusi undangan via WhatsApp dalam satu alur yang praktis dan mobile-friendly. Fitur ini sangat selaras dengan positioning TheDay sebagai platform undangan digital yang berpusat pada satu link undangan yang dibagikan lewat WhatsApp dan dipantau secara real-time [file:1].

Nilai utama fitur ini adalah mengubah proses “buat undangan lalu kirim satu-satu secara manual” menjadi workflow yang lebih rapi: user dapat menyimpan daftar tamu, memberi kategori, memantau status kirim dan RSVP, lalu mengirim undangan melalui template pesan WhatsApp yang bisa diubah sendiri oleh user, termasuk menyisipkan URL undangan mereka secara dinamis [file:1].

## Tujuan Produk
- Membantu user menyusun dan mengelola daftar tamu secara terstruktur.
- Menghubungkan modul undangan, WhatsApp sharing, dan RSVP dalam satu workflow [file:1].
- Mengurangi proses manual saat mengirim undangan satu per satu.
- Meningkatkan nilai praktis dashboard TheDay untuk pasangan modern yang aktif memakai WhatsApp [file:1].
- Menjadi fondasi untuk fitur lanjutan seperti reminder, broadcast center, seating plan, dan analytics tamu.

## Prinsip Desain
- **Fast and practical**: fitur harus terasa lebih cepat daripada mengelola tamu di spreadsheet.
- **WhatsApp-native**: alur harus dibangun dengan asumsi bahwa kanal utama distribusi adalah WhatsApp [file:1].
- **Mobile-first**: create, edit, filter, dan kirim tamu harus nyaman dari HP [file:1].
- **Low-friction**: kirim undangan ke tamu harus bisa dilakukan dalam sedikit langkah.
- **Personal but scalable**: tetap mendukung sapaan personal, tetapi cukup efisien untuk ratusan tamu.

## Ruang Lingkup MVP
Versi MVP mencakup:
- Menambah tamu satu per satu.
- Import tamu sederhana dari paste list atau CSV sederhana.
- Edit dan hapus tamu.
- Menyimpan nomor WhatsApp tamu.
- Menyimpan kategori/grup tamu.
- Menandai status kirim undangan.
- Menyimpan status RSVP per tamu.
- Membuat dan mengedit template pesan WhatsApp.
- Menyisipkan placeholder dinamis dalam template pesan, termasuk URL undangan personal per tamu.
- Mengirim undangan ke WhatsApp per tamu melalui deep link WhatsApp.
- Menyalin teks pesan undangan secara manual tanpa membuka WhatsApp.
- Filter dan search tamu.
- Ringkasan jumlah tamu, terkirim, belum terkirim, hadir, tidak hadir, dan belum respon.

Versi MVP **tidak** mencakup:
- Pengiriman WhatsApp otomatis via official API.
- Broadcast mass send langsung dari server.
- Sinkronisasi kontak dari ponsel.
- Multi-template pesan berdasarkan banyak skenario kompleks.
- A/B testing template pesan.
- Reminder otomatis terjadwal.
- Tagging tingkat lanjut atau CRM automation.

## User Persona Utama
### Persona
Pasangan modern Indonesia usia 24–32 tahun yang terbiasa berkomunikasi via WhatsApp dan ingin proses penyebaran undangan lebih cepat, rapi, dan tidak ribet [file:1].

### Pain Points yang Diselesaikan
- Sulit melacak siapa saja yang sudah dikirimi undangan.
- Sulit memantau siapa yang sudah RSVP dan siapa yang belum [file:1].
- Kirim undangan lewat WhatsApp satu per satu terasa repetitif.
- Pesan undangan sering diketik ulang manual.
- Link undangan mudah salah copy-paste saat dikirim ke banyak tamu.

## Use Cases Utama
1. User membuat daftar tamu beserta nomor WhatsApp.
2. User mengelompokkan tamu, misalnya keluarga, teman, kantor, tetangga.
3. User menulis template pesan undangan WhatsApp yang personal.
4. User mengirim undangan ke satu tamu dengan satu klik dari dashboard.
5. User membuka WhatsApp dengan pesan yang sudah terisi otomatis, termasuk URL undangan.
6. User menyalin teks undangan jika ingin kirim manual lewat chat pribadi, grup keluarga, atau aplikasi lain.
7. User menandai tamu yang sudah dikirimi.
8. User memantau siapa yang sudah RSVP, belum RSVP, hadir, atau tidak hadir.

## Posisi di Dashboard
Sidebar dashboard direkomendasikan:
- Overview
- Undangan
- **Guest List**
- RSVP
- Checklist Pernikahan
- Budget Planner
- Paket & Billing
- Pengaturan

Guest List Manager sebaiknya ditempatkan dekat modul RSVP karena keduanya sangat berkaitan langsung secara operasional [file:1].

## Struktur Informasi
### Halaman Guest List
Komponen utama:
- Header halaman
- Summary cards
- Search dan filter
- Aksi tambah tamu
- Aksi import tamu
- Aksi template pesan WhatsApp
- Tabel/list tamu
- Bulk action sederhana

### Summary Area
Tampilkan metrik utama:
- Total tamu
- Sudah dikirim
- Belum dikirim
- Hadir
- Tidak hadir
- Belum respon

### Tampilan Data
Sediakan dua mode tampilan:
- **Table view** untuk desktop
- **Card/list view** untuk mobile

## Data Tamu
### Field Tamu MVP
- Nama tamu
- Nomor WhatsApp
- Kategori tamu
- Sapaan / panggilan opsional
- Catatan opsional
- Status kirim undangan
- Status RSVP
- Tanggal kirim terakhir opsional
- Invitation terkait

### Kategori Bawaan yang Direkomendasikan
| Kategori | Contoh |
|---|---|
| Keluarga | Orang tua, saudara, keluarga besar |
| Teman | Teman sekolah, kuliah, komunitas |
| Kantor | Rekan kerja, atasan, partner |
| Tetangga | Tetangga rumah atau lingkungan |
| Vendor / Relasi | Jika perlu mengirim info acara |
| Lainnya | Untuk kebutuhan custom |

## Fitur Detail
### 1. Add Guest
User dapat menambah tamu satu per satu dengan field minimum:
- Nama tamu
- Nomor WhatsApp
- Kategori
- Sapaan opsional
- Catatan opsional

### 2. Import Guest
User dapat menambahkan banyak tamu melalui:
- Paste list sederhana
- Upload CSV sederhana

Format CSV minimum:
- name
- phone
- category
- greeting optional
- note optional

Untuk MVP, cukup sediakan import dengan validasi dasar dan preview sebelum simpan.

### 3. Edit / Delete Guest
User dapat mengedit data tamu kapan saja dan dapat menghapus tamu jika diperlukan.

### 4. Search, Filter, Sort
Minimal fitur pencarian dan filter:
- Search nama atau nomor WhatsApp
- Filter kategori
- Filter status kirim
- Filter status RSVP
- Filter invitation terkait

Sort minimal:
- Terbaru ditambahkan
- Nama A–Z
- Belum dikirim dulu
- Belum RSVP dulu

### 5. Status Tracking
Status yang perlu didukung:

#### Status Kirim
- not_sent
- sent
- failed optional untuk fase lanjut

#### Status RSVP
- pending
- attending
- not_attending
- maybe optional jika ingin disiapkan

Untuk MVP, cukup gunakan `pending`, `attending`, `not_attending`.

### 6. WhatsApp Message Template
Ini adalah fitur inti tambahan yang sangat penting.

User dapat:
- Membuat template pesan WhatsApp default.
- Mengedit isi template kapan saja.
- Melihat preview hasil template sebelum kirim.
- Menggunakan placeholder dinamis yang otomatis diganti saat kirim.

### Placeholder yang Direkomendasikan
| Placeholder | Fungsi |
|---|---|
| `{{guest_name}}` | Nama tamu |
| `{{greeting}}` | Sapaan personal, mis. Bapak/Ibu/Kak |
| `{{bride_name}}` | Nama mempelai wanita |
| `{{groom_name}}` | Nama mempelai pria |
| `{{event_date}}` | Tanggal acara utama |
| `{{event_time}}` | Jam acara |
| `{{event_location}}` | Lokasi acara |
| `{{invitation_url}}` | URL undangan user |
| `{{custom_slug}}` | Slug undangan jika ada |

Minimal MVP wajib mendukung `{{guest_name}}` dan `{{invitation_url}}`.

### Contoh Template Pesan
```text
Halo {{guest_name}},

Dengan penuh kebahagiaan, kami mengundang kamu untuk hadir di hari spesial kami.

Buka undangannya di sini:
{{invitation_url}}

Terima kasih atas doa dan kehadirannya.
```

### 7. WhatsApp Send Flow
MVP menggunakan **deep link WhatsApp**, bukan pengiriman otomatis dari server.

Flow:
1. User klik tombol “Kirim WA” pada tamu.
2. Sistem generate pesan berdasarkan template.
3. Placeholder diganti dengan data tamu dan data undangan.
4. Sistem membuka URL WhatsApp seperti `https://wa.me/{phone}?text={encoded_message}`.
5. User melanjutkan pengiriman langsung di aplikasi WhatsApp / WhatsApp Web.
6. Setelah berhasil membuka link, sistem dapat menandai status sebagai `sent` dengan konfirmasi user.

### 8. Copy Message Flow
Selain tombol kirim WhatsApp, user juga harus bisa menyalin isi pesan yang sudah digenerate. Fitur ini berguna jika user ingin:
- mengirim manual lewat WhatsApp biasa
- mengedit pesan sedikit sebelum kirim
- mengirim ke grup keluarga
- menyimpan pesan untuk dipakai ulang di aplikasi lain

Flow:
1. User klik tombol “Copy Text” pada tamu.
2. Sistem generate pesan final berdasarkan template.
3. Placeholder diganti dengan data tamu dan URL undangan personal.
4. Teks final disalin ke clipboard.
5. Sistem menampilkan feedback seperti “Teks undangan berhasil disalin”.

Catatan produk:
- Aksi copy text **tidak otomatis** menandai status `sent`.
- Jika diinginkan, setelah copy user bisa diberi aksi tambahan “Tandai sebagai sudah dikirim”.

### 9. Bulk Send Assistance
Untuk MVP, bulk send tidak perlu otomatis penuh, tetapi boleh mendukung:
- Pilih beberapa tamu
- Klik “Kirim satu per satu”
- Sistem membuka flow kirim berurutan atau daftar queue sederhana

Catatan: jangan janjikan otomatisasi penuh bila belum pakai official WhatsApp API.

### 10. Invitation URL Injection
Setiap template pesan harus bisa menyisipkan URL undangan user secara otomatis dalam bentuk **URL personal per tamu**. Jika user memiliki lebih dari satu undangan aktif, sistem harus meminta user memilih invitation mana yang dipakai sebagai sumber URL [file:1].

Format yang dipilih untuk TheDay adalah path-based personalized URL, misalnya `theday.id/ardi-novi/bapak-andi`, bukan query string seperti `?to=bapak-andi`, karena format path terasa lebih clean, premium, dan personal untuk tamu [file:1].

Sistem harus membentuk URL dari kombinasi:
- invitation public slug, misalnya `ardi-novi`
- guest slug human-readable yang unik per invitation, misalnya `bapak-andi`

Contoh hasil final:
- Undangan umum: `theday.id/ardi-novi`
- Undangan personal: `theday.id/ardi-novi/bapak-andi`

Jika fitur custom URL slug tersedia, gunakan URL final yang aktif. Jika belum, gunakan URL default invitation.

### 11. Personalized Guest Slug
Setiap tamu pada Guest List harus memiliki `guest_slug` yang dibentuk dari nama tamu dan digunakan untuk URL personal. Slug ini harus:
- human-readable
- unik dalam lingkup satu invitation
- otomatis dibuat saat tamu ditambahkan
- bisa diregenerate jika nama tamu diubah, tergantung keputusan produk

Contoh normalisasi:
- `Bapak Andi` → `bapak-andi`
- `Keluarga Pak Rudi` → `keluarga-pak-rudi`
- jika bentrok: `bapak-andi-2`

Guest slug ini menjadi identitas utama untuk personal URL pada MVP, sehingga URL tetap cantik tanpa token yang terlihat.

### 12. Open Tracking via Personal URL
Karena setiap tamu memiliki personal URL, sistem dapat melacak apakah undangan sudah dibuka oleh tamu tertentu ketika URL tersebut diakses. Saat route personal invitation diakses, sistem dapat mencatat event buka pertama dan terakhir untuk guest terkait.

Status tambahan yang direkomendasikan pada Guest List:
- not_sent
- sent
- opened
- rsvped (derived state atau summary state)

Untuk MVP, status `opened` sangat direkomendasikan karena memberi user visibilitas lebih baik terhadap progres distribusi undangan [file:1].

## Relasi dengan Modul Lain
Guest List Manager harus terhubung langsung dengan modul TheDay yang sudah ada atau direncanakan [file:1].

### Integrasi yang Direkomendasikan
- Saat user publish undangan, sistem menawarkan langkah lanjut: “Tambahkan daftar tamu”.
- Status RSVP per tamu sinkron dengan sistem RSVP undangan [file:1].
- Analytics dashboard dapat menampilkan metrik berbasis tamu terkirim vs RSVP masuk pada fase lanjut [file:1].
- Reminder center di masa depan dapat memakai daftar tamu dan template pesan yang sama.

## UX Flow
### First-Time Experience
1. User membuka menu Guest List.
2. Sistem menampilkan intro singkat manfaat fitur.
3. User diminta menambah tamu pertama atau import daftar tamu.
4. Setelah ada tamu, sistem meminta user menyetel template pesan WhatsApp.
5. User preview template dan mencoba kirim ke satu tamu.

### Daily Use Flow
1. User masuk ke Guest List.
2. User filter tamu yang belum dikirim.
3. User klik kirim WhatsApp.
4. Setelah pengiriman, status diperbarui.
5. User kembali untuk memantau RSVP dan mengirim sisanya.

## UI Requirements
### Overview Card
Di halaman overview dashboard tampilkan widget:
- Total tamu
- Belum dikirim
- Belum RSVP
- CTA “Kelola tamu”

### Halaman Guest List
Komponen minimum:
- Page title
- Subtitle singkat
- Summary cards
- Search bar
- Filter bar/sheet
- Tombol tambah tamu
- Tombol import
- Tombol edit template WA
- List/table tamu
- Bulk action area

### Guest Row / Card
Menampilkan:
- Nama tamu
- Nomor WhatsApp
- Kategori
- Status kirim
- Status RSVP
- Tombol kirim WA
- Tombol copy text
- Tombol edit
- More menu

### Template Editor UI
Komponen minimum:
- Textarea template pesan
- Daftar placeholder yang bisa diklik untuk insert
- Preview hasil pesan
- Pilihan invitation sumber URL
- Tombol simpan template
- Tombol reset ke template default
- Tombol copy preview text

### Empty State
Jika belum ada tamu:
- Tampilkan pesan hangat
- Copy contoh: “Tambahkan tamu pertamamu lalu kirim undangan lebih rapi lewat WhatsApp.”

## Data Model Usulan
### Table: guest_lists
- id
- user_id
- invitation_id nullable
- name
- guest_slug
- phone_number
- category nullable
- greeting nullable
- note nullable
- send_status enum(not_sent, sent, opened)
- rsvp_status enum(pending, attending, not_attending)
- first_opened_at nullable
- last_opened_at nullable
- last_sent_at nullable
- created_at
- updated_at
- deleted_at nullable

### Table: whatsapp_message_templates
- id
- user_id
- invitation_id nullable
- name
- content
- is_default boolean
- created_at
- updated_at

### Table: guest_message_logs
Opsional untuk MVP ringan, tapi direkomendasikan jika ingin audit kirim.
- id
- guest_id
- template_id nullable
- invitation_id nullable
- generated_message
- generated_url
- status enum(opened, confirmed_sent, cancelled)
- created_at

## Business Rules
- Setiap tamu harus memiliki nama dan nomor WhatsApp valid.
- Setiap tamu harus memiliki `guest_slug` unik dalam satu invitation.
- Nomor WhatsApp harus dinormalisasi ke format internasional atau format lokal yang konsisten.
- Setiap user minimal memiliki satu template pesan default.
- Placeholder yang tidak punya data pengganti harus fallback aman, bukan menghasilkan string rusak.
- `{{invitation_url}}` wajib selalu mengarah ke undangan aktif yang dipilih user dalam format personal path URL, misalnya `theday.id/ardi-novi/bapak-andi`.
- Status kirim tidak otomatis dianggap `sent` hanya karena link dibuka, kecuali user mengonfirmasi atau sistem menerapkan auto-mark sesuai keputusan produk.
- RSVP dari public invitation harus dapat meng-update tamu terkait jika sistem memakai identitas tamu yang cocok.

## Normalisasi Nomor WhatsApp
Nomor telepon Indonesia sebaiknya dinormalisasi agar deep link WhatsApp konsisten.

Contoh aturan:
- `08xxxxxxxxxx` → `628xxxxxxxxxx`
- `+628xxxxxxxxxx` → `628xxxxxxxxxx`
- Spasi, dash, dan karakter non-digit dibersihkan

Jika nomor tidak valid, sistem harus menolak simpan atau menandai error saat import.

## API / Backend Scope
Minimal endpoint/action yang diperlukan:
- GET guest list
- POST create guest
- PATCH update guest
- DELETE guest
- POST import guests
- GET guest summary
- GET whatsapp template
- POST/PATCH save whatsapp template
- POST generate whatsapp message
- POST copy/generated message optional for analytics
- POST mark as sent optional
- GET personalized invitation route by invitation slug + guest slug
- POST/PUT track invitation opened for guest

Karena stack TheDay memakai Laravel 11 + Vue 3 + Inertia, implementasi awal bisa memakai controller + server props tanpa public API terpisah [file:1].

## Validasi Template Pesan
- Sistem harus mengenali placeholder yang valid.
- Placeholder tidak dikenal harus diberi warning.
- Panjang pesan harus tetap nyaman untuk WhatsApp.
- Preview harus memperlihatkan hasil final dengan data contoh atau data tamu terpilih.
- URL undangan harus otomatis di-encode dengan benar saat digabung ke deep link WhatsApp.

## Metrics yang Perlu Dilacak
Fitur ini sebaiknya melacak:
- Jumlah user yang membuka Guest List Manager
- Jumlah tamu per user
- Persentase user yang membuat template pesan WA
- Jumlah klik kirim WA
- Persentase tamu dengan status sent
- Persentase RSVP berdasarkan tamu yang dikirimi
- CTR dari overview card ke Guest List

Metrik ini mendukung activation, engagement, dan retensi yang memang menjadi fokus TheDay [file:1].

## Free vs Premium
### Free
- Tambah/edit/hapus tamu
- Template pesan WA default yang bisa diedit
- Kirim WA per tamu via deep link
- Tracking status kirim dasar
- Tracking RSVP dasar

### Premium
- Import CSV lebih besar
- Multi-template pesan
- Bulk send assistance lebih nyaman
- Reminder template H-7 / H-1
- Kategori tamu lebih fleksibel
- Analytics tamu lebih detail

MVP dapat dimulai dari versi free karena nilai utamanya sudah sangat terasa.

## Acceptance Criteria MVP
Fitur dianggap selesai jika:
- User dapat membuka halaman Guest List dari dashboard.
- User dapat menambah, edit, hapus, dan import tamu sederhana.
- User dapat menyimpan nomor WhatsApp tamu.
- Setiap tamu memiliki personal guest slug yang unik dalam satu invitation.
- Template pesan WA mendukung placeholder minimal `{{guest_name}}` dan `{{invitation_url}}`.
- `{{invitation_url}}` menghasilkan personal URL dengan format seperti `theday.id/ardi-novi/bapak-andi`.
- User dapat klik tombol kirim dan diarahkan ke WhatsApp dengan pesan terisi otomatis.
- User dapat klik tombol copy text dan menyalin pesan final ke clipboard.
- Sistem dapat menandai bahwa personal URL tamu telah dibuka.
- User dapat melihat status kirim, opened, dan RSVP tamu.
- UI nyaman dipakai di mobile dan desktop.

## Out of Scope
- WhatsApp API resmi untuk auto-send penuh
- Scheduled automation
- Sync contact phonebook
- CRM tagging kompleks
- AI-generated personalized messages
- Broadcast mass send server-side

## Catatan Implementasi untuk AI Developer
- Fokuskan fitur ini sebagai alat bantu distribusi undangan, bukan CRM kompleks.
- Prioritaskan UX mobile karena user kemungkinan besar mengirim dari HP [file:1].
- Gunakan copy yang hangat dan sopan sesuai brand TheDay [file:1].
- Buat template editor sangat sederhana dan langsung bisa dipahami.
- Pastikan normalisasi nomor telepon Indonesia ditangani dengan baik.
- Jangan overclaim otomatisasi WhatsApp jika implementasi masih deep link/manual.

## Rekomendasi Tahap Pengerjaan
1. Buat model dan migration guest list.
2. Buat halaman list + create/edit/delete tamu.
3. Implement search, filter, dan summary.
4. Buat template editor WhatsApp.
5. Implement generator placeholder + preview.
6. Implement tombol kirim via deep link WhatsApp.
7. Tambahkan update status kirim.
8. Sinkronkan dengan RSVP data dan overview card.

## Definition of Done
Fitur siap dianggap production-ready jika:
- Workflow tambah tamu sampai kirim WA berjalan lancar.
- Template pesan dapat diedit dan placeholder bekerja akurat.
- URL undangan selalu tersisip dengan benar.
- Tracking status kirim dan RSVP konsisten.
- Pengalaman mobile terasa cepat, sederhana, dan tidak membingungkan.
- Fitur memperkuat value utama TheDay sebagai platform undangan digital yang praktis, shareable, dan WhatsApp-first [file:1].
