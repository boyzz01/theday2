# Prompt — TheDay Halaman Kontak (Contact Page) Spec

You are implementing the **Halaman Kontak** (Contact Page) for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Target users: Indonesian couples aged 24-32.
- Brand tone: warm, approachable, modern, friendly.
- All copy must be in Bahasa Indonesia using "kamu" (not "Anda").
- This is a public page, accessible without login.
- The page must feel helpful and reassuring, not cold or corporate.

---

## Page URL
`/kontak`

---

## Page Purpose
- Give users a clear way to reach TheDay for support, questions, or feedback.
- Reduce friction for users who need help before or after purchasing.
- Build trust by showing TheDay is reachable and responsive.

---

## Page Layout

### Desktop
- Two-column layout
- Left column: contact information, response time, social media links
- Right column: contact form

### Mobile
- Single column
- Contact info first
- Form below

---

## Section 1: Header
- Page title: "Hubungi Kami"
- Subtitle: "Ada pertanyaan atau butuh bantuan? Kami siap membantu kamu."
- Warm, short, reassuring tone.

---

## Section 2: Contact Info Block

### Primary contact
- **WhatsApp:** Link to wa.me/[nomor WA Business TheDay]
  - Label: "Chat via WhatsApp"
  - Description: "Cara tercepat menghubungi kami. Biasanya kami balas dalam beberapa jam."
  - Button style: WhatsApp green button with WA icon

- **Email:** hello@theday.id
  - Label: "Kirim Email"
  - Description: "Untuk pertanyaan lebih detail. Kami balas dalam 1×24 jam pada hari kerja."

### Response time notice
Show a small notice:
- "Jam layanan: Senin–Sabtu, 09.00–21.00 WIB"
- "Di luar jam layanan, kami akan membalas secepatnya."

### Social media links
- Instagram: @theday.id
- TikTok: @theday.id
- Show as icon + handle, clickable

---

## Section 3: Contact Form

### Purpose
Allow users to send a message directly from the website.
Messages are delivered to the TheDay team email or support inbox.

### Form fields
- `name` — required, label: "Nama kamu", placeholder: "Nama lengkap"
- `email` — required, label: "Alamat email", placeholder: "email@example.com"
- `subject` — required, dropdown select, label: "Topik"
  - Options:
    - Pertanyaan umum
    - Masalah teknis
    - Pembayaran & langganan
    - Saran & masukan
    - Kerjasama & partnership
    - Lainnya
- `message` — required, textarea, label: "Pesan", placeholder: "Tuliskan pertanyaan atau pesanmu di sini..."
- Submit button: "Kirim Pesan →"

### Validation
- `name` required, min 2 chars, max 100 chars
- `email` required, valid email format
- `subject` required, must be one of allowed options
- `message` required, min 10 chars, max 2000 chars
- Show inline validation errors on blur and on submit

### After submit
- Show success state replacing the form:
  - Icon: checkmark or envelope
  - Title: "Pesan kamu sudah terkirim! 🤍"
  - Body: "Terima kasih sudah menghubungi kami. Kami akan membalas ke [email] secepatnya."
  - CTA: "Kembali ke Beranda"
- Do not reload the page, use Vue reactive state

### Email delivery
- On form submission, send email to hello@theday.id
- Email subject: "[Kontak TheDay] {subject} — dari {name}"
- Email body includes: name, email, subject, message, timestamp
- Use Resend or Postmark for transactional email delivery
- Optionally send auto-reply confirmation email to the sender

### Spam protection
- Implement honeypot field (hidden input that bots fill but humans don't)
- Optionally add simple rate limiting: max 3 submissions per IP per hour

---

## Section 4: FAQ Snippet (optional but recommended)

Show 4–5 most common questions directly on the contact page.
This helps users self-serve before sending a message.

Suggested FAQ items:
1. **Apakah TheDay benar-benar gratis?**
   Ya, paket Free memungkinkan kamu membuat 1 undangan tanpa biaya.

2. **Bagaimana cara berbagi undangan ke tamu?**
   Setelah publish, kamu dapat link unik yang bisa langsung dibagikan via WhatsApp.

3. **Bisa ganti template setelah undangan dibuat?**
   Bisa, kamu bisa edit undangan kapan saja selama masa aktif.

4. **Apakah ada garansi uang kembali?**
   Ada. Kami memberikan garansi 7 hari untuk semua paket berbayar.

5. **Tamu perlu install aplikasi?**
   Tidak. Tamu cukup klik link dan buka langsung di browser HP mereka.

### FAQ section design
- Simple accordion style
- Each item has question + answer
- Collapsed by default, expand on click
- Below FAQ: "Tidak menemukan jawaban? Hubungi kami langsung."

---

## Page Design Requirements

### Layout
- Max content width: 1100px centered
- Comfortable whitespace between sections
- Warm, clean aesthetic

### Typography
- Heading: Cormorant Garamond or DM Sans semibold
- Body: DM Sans regular, 16px, 1.7 line height

### Colors
- Background: `#FFFCF7`
- Text: `#2C2417`
- Primary button: `#C8A26B` (gold)
- WhatsApp button: `#25D366` (WA green)
- Links: `#C8A26B`
- Form border: warm gray
- Form focus ring: `#C8A26B`

### Form input style
- Rounded corners, consistent with TheDay UI
- Clear label above each field
- Subtle border, warm focus state
- Error state: red border + inline message below field

### Mobile
- Full width inputs
- Comfortable tap targets
- Submit button full width on mobile

---

## Technical Implementation

### Route
```
GET /kontak
```

### Controller
`ContactController`
- `index()` — render contact page
- `store()` — handle form submission

### Request validation
`ContactRequest` with rules for all fields

### Email sending
- Use `Mail::to('hello@theday.id')->send(new ContactFormMail(...))`
- Create `ContactFormMail` Mailable class
- Blade email template for team notification
- Optional: `ContactAutoReplyMail` for user confirmation

### Vue component
`ContactPage.vue`
- Reactive form state
- Inline validation
- Success state after submit
- FAQ accordion component

---

## Footer Integration
Link this page in the footer:
- Label: "Kontak"
- Place alongside: Kebijakan Privasi, Syarat & Ketentuan

---

## SEO
- Page title: "Kontak — TheDay"
- Meta description: "Ada pertanyaan tentang undangan digital TheDay? Hubungi kami via WhatsApp atau email. Kami siap membantu."
- Page should be indexable

---

## Edge Cases

### 1. User submits form multiple times
- Disable submit button after first successful submission
- Show success state only once until page reload

### 2. Email delivery fails
- Log the error server-side
- Still show success to user (do not expose technical errors)
- Optionally queue the email with Laravel Queue for retry

### 3. User uses invalid email format
- Block submission with inline validation
- Show: "Format email tidak valid"

### 4. Very long message
- Allow up to 2000 chars
- Show character counter below textarea

### 5. Mobile keyboard pushes form up
- Ensure form fields remain accessible when keyboard is open on mobile

### 6. Form submitted with honeypot filled (bot)
- Silently reject, return success response to not alert the bot

---

## Deliverables
Produce:
1. Route `GET /kontak` and `POST /kontak`
2. `ContactController` with index and store methods
3. `ContactRequest` validation class
4. `ContactFormMail` Mailable class with Blade email template
5. Optional `ContactAutoReplyMail` for user confirmation
6. `ContactPage.vue` with form, FAQ accordion, contact info
7. Success state after form submission
8. Spam protection via honeypot
9. Footer link integration
10. Mobile-first responsive design consistent with TheDay brand

---

## Non-Goals
Do not implement:
- Live chat widget
- Ticketing system
- Multi-language contact form
- File attachment in contact form

---

## Acceptance Criteria
Implementation is successful when:
1. Page is accessible at /kontak without login.
2. Contact info (WA, email, jam layanan) is clearly shown.
3. Form submits successfully and sends email to hello@theday.id.
4. Success state appears after submission.
5. Validation works correctly for all fields.
6. FAQ section is shown and expandable.
7. Page is mobile-friendly and comfortable to use.
8. Design is consistent with TheDay brand: warm, premium, approachable.
9. Honeypot spam protection is in place.
10. Page is linked in the footer.
