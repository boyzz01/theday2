# Prompt — TheDay Legal Pages Spec (Kebijakan & Privacy)

You are implementing the **legal pages** for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Target market: Indonesian couples aged 24-32.
- The platform collects user data (name, email, phone number, photos, invitation content).
- Payment is processed via Midtrans.
- Storage uses S3-compatible service (DigitalOcean Spaces).
- Legal pages are required for trust, compliance, and payment gateway requirements.
- All legal content must be written in **Bahasa Indonesia** as the primary language.
- Tone must be consistent with TheDay brand: warm, clear, approachable — not overly legalistic or cold.

---

## Pages to Implement

1. **Kebijakan Privasi** (Privacy Policy)
2. **Syarat & Ketentuan** (Terms of Service)
3. **Kebijakan Penggunaan Cookie** (Cookie Policy) — optional, can be combined with Privacy Policy

---

## Page 1: Kebijakan Privasi

### URL
`/kebijakan-privasi`

### Purpose
Explain to users what data TheDay collects, how it is used, stored, and protected.

### Required sections

#### 1. Pendahuluan
- Who TheDay is
- Commitment to user privacy
- When this policy applies (using the platform, visiting the website)

#### 2. Data yang Kami Kumpulkan
List clearly:
- Data akun: nama, alamat email, password (hashed), nomor HP opsional
- Data undangan: nama mempelai, tanggal acara, lokasi, foto, musik, konten undangan
- Data tamu: nama tamu, nomor WhatsApp yang diinput user
- Data penggunaan: halaman yang dikunjungi, fitur yang digunakan, waktu akses
- Data teknis: alamat IP, jenis browser, perangkat
- Data pembayaran: diproses oleh Midtrans, TheDay tidak menyimpan data kartu kredit

#### 3. Bagaimana Kami Menggunakan Data
- Menyediakan layanan undangan digital
- Memproses pembayaran
- Mengirim notifikasi layanan (bukan spam marketing)
- Meningkatkan kualitas produk
- Menjaga keamanan akun

#### 4. Berbagi Data dengan Pihak Ketiga
List clearly:
- Midtrans — pemrosesan pembayaran
- DigitalOcean Spaces — penyimpanan file dan foto
- Pusher/Soketi — fitur real-time
- Email provider (Resend/Postmark) — pengiriman email transaksional
- TheDay tidak menjual data pengguna kepada pihak ketiga

#### 5. Penyimpanan dan Keamanan Data
- Data disimpan di server yang berlokasi di [lokasi server]
- Enkripsi data sensitif
- Akses terbatas hanya untuk tim yang berwenang
- Retensi data: undangan aktif selama 30 hari setelah tanggal acara, data akun selama akun aktif

#### 6. Hak Pengguna
- Mengakses data pribadi
- Memperbarui atau mengoreksi data
- Menghapus akun dan data
- Mengajukan keberatan terhadap penggunaan data

#### 7. Cookie
- Jenis cookie yang digunakan (sesi, preferensi, analitik)
- Cara menonaktifkan cookie

#### 8. Perubahan Kebijakan
- TheDay dapat memperbarui kebijakan ini
- Pengguna akan diberitahu via email jika ada perubahan material
- Tanggal berlaku kebijakan harus dicantumkan

#### 9. Kontak
- Email: hello@theday.id
- Untuk pertanyaan terkait privasi

---

## Page 2: Syarat & Ketentuan

### URL
`/syarat-ketentuan`

### Purpose
Define the rules of using TheDay, rights and responsibilities of both parties.

### Required sections

#### 1. Pendahuluan
- Dengan menggunakan TheDay, pengguna menyetujui syarat ini
- Usia minimum pengguna: 17 tahun
- Berlaku sejak tanggal yang tercantum

#### 2. Akun Pengguna
- Pengguna bertanggung jawab atas kerahasiaan password
- Satu akun per pengguna
- TheDay berhak menangguhkan akun yang melanggar ketentuan

#### 3. Layanan yang Disediakan
- Deskripsi singkat produk TheDay
- Perbedaan paket Free, Silver, Gold
- Masa aktif undangan
- TheDay berhak mengubah fitur dengan pemberitahuan

#### 4. Pembayaran dan Langganan
- Harga dapat berubah dengan pemberitahuan sebelumnya
- Pembayaran diproses via Midtrans
- Paket berlaku selama 30 hari sejak pembayaran
- Garansi uang kembali 7 hari
- Tidak ada perpanjangan otomatis (one-time payment)

#### 5. Konten Pengguna
- Pengguna bertanggung jawab atas konten yang diunggah
- Dilarang mengunggah konten yang melanggar hukum, pornografi, SARA, atau hak cipta orang lain
- TheDay berhak menghapus konten yang melanggar tanpa pemberitahuan

#### 6. Hak Kekayaan Intelektual
- Template desain milik TheDay
- Konten yang dibuat pengguna (teks, foto) tetap milik pengguna
- Pengguna memberi TheDay lisensi terbatas untuk menampilkan konten dalam layanan

#### 7. Pembatasan Tanggung Jawab
- TheDay tidak bertanggung jawab atas kerugian tidak langsung
- TheDay tidak menjamin ketersediaan layanan 100% (uptime best effort)
- TheDay tidak bertanggung jawab atas konten yang dikirimkan tamu via buku tamu

#### 8. Penghentian Layanan
- Pengguna dapat menghapus akun kapan saja
- TheDay dapat menghentikan layanan dengan pemberitahuan 30 hari sebelumnya
- Data pengguna akan dihapus setelah periode retensi

#### 9. Hukum yang Berlaku
- Syarat ini diatur berdasarkan hukum Republik Indonesia
- Penyelesaian sengketa melalui musyawarah, atau pengadilan di Jakarta

#### 10. Perubahan Syarat
- TheDay berhak memperbarui syarat ini
- Pengguna akan diberitahu via email atau notifikasi in-app
- Penggunaan layanan setelah perubahan dianggap sebagai persetujuan

#### 11. Kontak
- Email: hello@theday.id

---

## Page 3: Kebijakan Cookie (optional, can be combined into Privacy Policy)

### URL
`/kebijakan-cookie`

### Required sections
- Apa itu cookie
- Cookie yang digunakan TheDay:
  - Cookie sesi (wajib untuk login)
  - Cookie preferensi (bahasa, tema)
  - Cookie analitik (penggunaan fitur, opsional)
- Cara menonaktifkan cookie
- Dampak jika cookie dinonaktifkan

---

## Technical Implementation

### Routes
```
GET /kebijakan-privasi
GET /syarat-ketentuan
GET /kebijakan-cookie  (optional)
```

### Controller
- `LegalController` with methods:
  - `privacyPolicy()`
  - `termsOfService()`
  - `cookiePolicy()` optional

### View rendering
- Render as Inertia page
- Or render as plain Blade view without Inertia if simpler
- Content can be stored as:
  - Static Blade/Vue component with hardcoded content
  - Or markdown files rendered dynamically
  - Recommended: static Vue/Blade component for simplicity

### Layout
- Use public layout (same as landing page, not dashboard layout)
- Include header with TheDay logo and navigation
- Include footer

---

## Page Design Requirements

### Layout
- Max content width: 720px centered
- Comfortable reading line length
- Generous vertical spacing between sections
- Sticky table of contents sidebar on desktop (optional but nice)

### Typography
- Section headers: DM Sans medium or semibold
- Body: DM Sans regular, 16px base, 1.7 line height
- TheDay brand font consistent with product

### Colors
- Background: `#FFFCF7` (warm cream)
- Text: `#2C2417` (dark brown)
- Section dividers: subtle warm gray
- Links: `#C8A26B` (primary gold)

### Mobile
- Single column
- Comfortable padding
- Section anchors accessible from top navigation or collapsed TOC

---

## Footer Links
These pages must be linked in the footer of:
- Landing page
- Dashboard layout footer
- Public invitation page footer (small text)

Footer link labels:
- Kebijakan Privasi
- Syarat & Ketentuan
- optional: Kebijakan Cookie

---

## Cookie Consent Banner (optional for MVP)
If implementing a cookie consent banner:
- Show on first visit only
- Simple accept/decline
- Store preference in localStorage
- Do not block usage if declined
- Can be deferred to post-launch

---

## Content Guidelines
When writing the actual legal content:

### Tone
- Friendly and approachable, not cold legal language
- Use "kamu" for the user (consistent with TheDay brand voice)
- Use simple sentences, avoid jargon
- Where legal terms are necessary, add a brief plain language explanation

### Do
- Be honest and specific about what data is collected
- Be clear about user rights
- Be transparent about third-party services used

### Don't
- Use vague language to hide data practices
- Copy-paste generic template without adapting to TheDay's actual practices
- Write in overly formal Indonesian that feels distant from the brand

---

## Last Updated Date
Each page must show a "Terakhir diperbarui: [tanggal]" line at the top.

---

## SEO
- Page title: "Kebijakan Privasi — TheDay"
- Meta description relevant to each page
- These pages should be indexable (not noindex)

---

## Deliverables
Produce:
1. `LegalController` with routes for privacy policy and terms of service
2. Vue or Blade view for Kebijakan Privasi with full content
3. Vue or Blade view for Syarat & Ketentuan with full content
4. Optional: Kebijakan Cookie page
5. Public layout integration
6. Footer links to legal pages
7. Content written in Bahasa Indonesia, warm tone, TheDay brand voice
8. Responsive mobile-first design with correct typography and colors

---

## Non-Goals
Do not implement:
- Cookie consent management platform (CMP) integration in this phase
- Multi-language version (English) in this phase
- Legal CMS for editing content without code changes

---

## Acceptance Criteria
Implementation is successful when:
1. Both pages are publicly accessible at their respective URLs.
2. Content covers all required sections.
3. Language is Bahasa Indonesia, warm and clear.
4. Design is consistent with TheDay brand.
5. Pages are linked in the footer.
6. Pages are readable and comfortable on mobile.
7. "Terakhir diperbarui" date is shown.
