# Prompt — TheDay Halaman Paket & Langganan (2-Tier: Free & Premium)

You are implementing the **Paket & Langganan** (Subscription & Upgrade) page for TheDay with a simplified 2-tier pricing model: Free and Premium.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Pricing is 2 tiers only: Free (Rp 0) and Premium (Rp 149.000 / 30 hari).
- Payment is processed via Midtrans Snap (GoPay, OVO, Dana, QRIS, VA bank, credit card).
- No recurring auto-billing. Premium is a one-time 30-day payment.
- Garansi uang kembali 7 hari.
- Target users: Indonesian couples aged 24-32.
- All UI copy in Bahasa Indonesia, tone: warm, clear, approachable.
- Brand colors: Primary Gold #C8A26B, Background #FFFCF7, Text #2C2417.

---

## Page URL
`/dashboard/paket`

---

## Page Purpose
1. Show user their current active plan and status.
2. Allow Free users to upgrade to Premium.
3. Allow Premium users to see expiry and renew.
4. Show clear feature comparison between Free and Premium.
5. Trigger Midtrans Snap payment flow.
6. Show payment history / invoice list.

---

## Page Layout

### Section 1: Current Plan Status
Show at the top of the page:

#### If user is on Free:
- Card with label: "Paket Aktif"
- Plan name: "Gratis"
- Description: "Kamu sedang menggunakan paket gratis dengan fitur terbatas."
- Highlight current limits:
  - 1 undangan aktif
  - 5 foto per undangan
  - Template gratis saja
  - Watermark TheDay
- CTA button: "Upgrade ke Premium →"

#### If user is on Premium:
- Card with label: "Paket Aktif"
- Plan name: "Premium "
- Show expiry: "Aktif hingga 15 Mei 2026"
- Show days remaining: "Sisa 29 hari"
- Color: warm gold highlight
- CTA button: "Perpanjang Premium →"
- If expiry <= 7 days: show warning badge "Segera berakhir"

---

### Section 2: Pricing Cards

Show two cards side by side (desktop) or stacked (mobile).

#### Card 1: Gratis
- Badge: current plan badge if user is on Free
- Price: Rp 0
- Tagline: "Coba tanpa risiko"
- Feature list:
  - 1 undangan aktif
  - 5 foto gallery
  - Template gratis
  - Musik default
  - Watermark "Dibuat dengan TheDay"
- CTA: disabled "Paket Kamu" if user is already Free
- Style: subtle, not highlighted

#### Card 2: Premium
- Badge: "Paling Populer" pill on top
- Price: Rp 149.000
- Price period: / 30 hari
- Tagline: "Semua yang kamu butuhkan untuk undangan impian"
- Feature list (all checkmarked):
  - Unlimited undangan aktif
  - Unlimited foto gallery
  - Semua template (gratis + premium)
  - Upload musik sendiri
  - Tanpa watermark
  - Custom URL slug
  - Password protection
  - Analytics kunjungan
  - Prioritas dukungan
- CTA: "Upgrade Sekarang →" if user is Free
- CTA: "Perpanjang" if user is Premium and expiry < 14 days
- CTA: "Sudah Premium ✓" disabled if user is Premium and expiry > 14 days
- Style: highlighted with gold border, elevated shadow
- Below CTA: "🔒 Pembayaran aman via Midtrans. Garansi uang kembali 7 hari."

---

### Section 3: Feature Comparison Table
Show a clean comparison table below the pricing cards.

| Fitur | Gratis | Premium |
|---|---|---|
| Undangan aktif | 1 | Unlimited |
| Foto per undangan | 5 | Unlimited |
| Template premium | ✗ | ✓ |
| Upload musik sendiri | ✗ | ✓ |
| Tanpa watermark | ✗ | ✓ |
| Custom URL slug | ✗ | ✓ |
| Password protection | ✗ | ✓ |
| Analytics kunjungan | ✗ | ✓ |
| Prioritas dukungan | ✗ | ✓ |

Use checkmark icon (green ✓) and cross icon (gray ✗) instead of plain text.

---

### Section 4: Payment Method Info
Short block below comparison table:

"Kami mendukung berbagai metode pembayaran:"
- GoPay, OVO, Dana, QRIS, Transfer Bank, Kartu Kredit

Show payment method icons in a row.

---

### Section 5: FAQ Pembayaran
Simple accordion, 5 items:

1. **Apakah Premium otomatis diperpanjang?**
   Tidak. Premium adalah pembayaran satu kali untuk 30 hari. Tidak ada auto-renewal. Kamu bisa perpanjang manual kapan saja.

2. **Bagaimana cara pembayaran?**
   Kamu bisa bayar via GoPay, OVO, Dana, QRIS, transfer bank virtual, atau kartu kredit melalui Midtrans — aman dan terenkripsi.

3. **Ada garansi uang kembali?**
   Ada. Jika tidak puas dalam 7 hari pertama, kami kembalikan 100% pembayaranmu. Hubungi hello@theday.id.

4. **Kalau Premium habis, undangan saya hilang?**
   Tidak. Undangan kamu tetap ada dan bisa diakses. Namun beberapa fitur Premium tidak aktif lagi sampai kamu perpanjang.

5. **Bisa upgrade di tengah jalan?**
   Bisa kapan saja. Kamu langsung dapat akses Premium setelah pembayaran berhasil.

---

### Section 6: Riwayat Pembayaran
Show a simple table of past transactions at the bottom.

Table columns:
- Tanggal
- Paket
- Harga
- Metode
- Status (Berhasil / Pending / Gagal)
- Aksi (Download Invoice)

If no transactions yet:
- Empty state: "Belum ada riwayat pembayaran."

---

## Payment Flow (Upgrade/Renew)

### Trigger
User clicks "Upgrade Sekarang" or "Perpanjang Premium"

### Step 1: Create order
- `POST /dashboard/subscriptions/checkout`
- Backend creates an order record
- Calls Midtrans Snap API to generate payment token
- Returns Snap token to frontend

### Step 2: Open Midtrans Snap
- Frontend calls `window.snap.pay(token, {...})`
- User completes payment in Snap popup
- Midtrans redirects or triggers callback

### Step 3: Handle result
On payment success:
- Midtrans sends webhook to `POST /webhooks/midtrans`
- Backend validates signature
- Activates Premium plan for the user
- Sets expiry = now + 30 days
- Creates invoice record
- Sends payment confirmation email

On payment pending:
- Show pending state
- Recheck on page reload

On payment failed/cancelled:
- Show friendly error
- Allow retry

### Snap popup callbacks in frontend:
```js
onSuccess: (result) => {
  // show success state, refresh plan status
},
onPending: (result) => {
  // show pending notice
},
onError: (result) => {
  // show error, allow retry
},
onClose: () => {
  // user closed popup, no action needed
}
```

---

## Expiry & Renewal Logic

### When Premium expires
- Plan reverts to Free
- Invitation still accessible but:
  - watermark reappears
  - premium features locked
  - gallery limited to 5 photos shown (rest hidden, not deleted)
  - premium templates still render existing invitation but locked for new selection
- User gets email reminder:
  - 7 days before expiry
  - 1 day before expiry
  - On expiry day

### Renewal
- User renews from the Paket & Langganan page
- New expiry = old expiry + 30 days (if renewed before expiry)
- New expiry = now + 30 days (if renewed after expiry)

---

## Invoice

### Invoice record fields
- invoice_number (auto-generated, e.g. INV-20260416-001)
- user_id
- plan (premium)
- amount
- payment_method
- midtrans_order_id
- midtrans_transaction_id
- status (paid, pending, failed)
- paid_at
- expires_at (plan expiry)

### Invoice download
- Simple PDF invoice
- Logo TheDay
- Invoice number, date, user name, email
- Item: Premium 30 hari — Rp 149.000
- Payment method and status
- Footer: TheDay, hello@theday.id

---

## Data Model

### `plans` table
- id
- slug: `free` | `premium`
- name: `Gratis` | `Premium`
- price: 0 | 149000
- duration_days: 30
- features_json
- is_active: boolean

### `subscriptions` table
- id
- user_id
- plan_id
- started_at
- expires_at
- status: `active` | `expired` | `cancelled`

### `invoices` table
- id
- user_id
- subscription_id
- invoice_number
- amount
- payment_method
- midtrans_order_id
- midtrans_transaction_id
- status: `paid` | `pending` | `failed`
- paid_at
- created_at

---

## Backend Requirements

### Controller
`SubscriptionController`
- `index()` — render Paket page with current plan, invoices
- `checkout()` — create order + Midtrans token
- `webhook()` — handle Midtrans webhook callback

### Midtrans Integration
- Use Midtrans Snap PHP SDK
- Snap token generation
- Webhook signature verification
- Handle `settlement`, `pending`, `deny`, `cancel`, `expire` notification types

### Email Notifications
- Payment success email with invoice attachment
- 7-day expiry reminder
- 1-day expiry reminder
- Expiry notification email

Use Laravel Queue for all email sending.

---

## UI Components

- `PaketPage` — main page
- `CurrentPlanCard` — shows active plan status
- `PricingCard` — Free and Premium cards
- `FeatureComparisonTable`
- `PaymentMethodIcons`
- `UpgradeFaqAccordion`
- `PaymentHistoryTable`
- `InvoiceDownloadButton`
- `UpgradeSuccessState` — shown after payment success
- `PlanExpiryWarningBanner` — shown in dashboard when expiry <= 7 days

---

## Plan Expiry Warning Banner
Show a dismissible warning banner in the main dashboard layout when:
- User is on Premium AND expiry <= 7 days

Banner content:
- "⏰ Paket Premiummu berakhir dalam X hari. Perpanjang sekarang agar tidak terputus."
- CTA: "Perpanjang →"

---

## Edge Cases

### 1. User upgrades while Midtrans popup is already open
- Block opening second Snap instance
- Disable button after first click

### 2. Webhook arrives before user sees success page
- Plan is activated server-side immediately
- Frontend refreshes plan status on page load or after Snap callback

### 3. Double payment (user pays twice accidentally)
- Detect via Midtrans order ID uniqueness
- Second payment rejected or refunded
- Only one Premium period added

### 4. Payment success but webhook delayed
- Show pending state
- Auto-refresh every 10 seconds for 2 minutes
- Fallback: user can refresh page manually

### 5. User closes Snap before paying
- No order confirmed, no plan change
- Button re-enables after popup closes

### 6. Premium expires during active invitation
- Invitation stays live
- Watermark reappears
- Premium features locked
- Data not deleted

### 7. User is on Premium and clicks Upgrade again
- Show "Perpanjang" flow instead
- Clearly label it as extension

### 8. Invoice PDF generation fails
- Log error
- Show retry button
- Do not block user from using the platform

---

## Deliverables
Produce:
1. `SubscriptionController` with index, checkout, webhook methods
2. Midtrans Snap integration with webhook handler
3. `plans`, `subscriptions`, `invoices` table migrations
4. `PaketPage.vue` full implementation
5. `CurrentPlanCard.vue`
6. `PricingCard.vue` (Free and Premium)
7. `FeatureComparisonTable.vue`
8. `PaymentHistoryTable.vue` with invoice download
9. `PlanExpiryWarningBanner.vue` in dashboard layout
10. Email notifications: payment success, expiry reminders (7 days, 1 day, on expiry)
11. Queue-based email sending
12. Invoice PDF generation
13. Edge case handling per spec

---

## Non-Goals
Do not implement:
- Auto-renewal / recurring billing
- Promo codes / discount system
- Multiple plan purchases at once
- Referral credit system
(all deferred to post-launch)

---

## Acceptance Criteria
Implementation is successful when:
1. Free users can see their current limits and upgrade to Premium.
2. Midtrans Snap opens and processes payment correctly.
3. After payment, Premium is activated within seconds.
4. Premium expiry is shown correctly and counts down.
5. Renewal extends expiry by 30 days.
6. Payment history shows all transactions with downloadable invoice.
7. Expiry warning banner appears in dashboard when <= 7 days.
8. Emails sent for payment confirmation and expiry reminders.
9. When Premium expires, features gracefully lock without data loss.
10. Page is beautiful, warm, and consistent with TheDay brand.
