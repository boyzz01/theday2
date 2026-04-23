# Migrasi Payment: Midtrans → Mayar.id

**Date:** 2026-04-23
**Branch:** editorv2
**Scope:** Full cut-over dari Midtrans Snap ke Mayar.id Invoice API. Tidak ada dual-gateway, tidak ada fallback Midtrans.

---

## Context

TheDay saat ini menggunakan Midtrans Snap (popup modal) untuk dua jenis pembayaran:
1. **Subscription Premium** — Rp X / 30 hari
2. **Addon Undangan** — Rp 15.000 / undangan (max 10 per order)

Semua transaksi Midtrans yang ada sudah settled (tidak ada yang pending), sehingga full cut-over aman dilakukan tanpa masa transisi.

---

## Architecture Overview

### Flow Baru

```
User klik Bayar
  → POST /subscriptions/checkout (atau /addons/checkout)
  → MayarService::createInvoice()  →  Mayar API POST /invoice/create
  ←  { payment_url, mayar_invoice_id }
  → simpan mayar_invoice_id ke transactions.payment_gateway_id
  → return { payment_url } ke frontend
  → window.location.href = payment_url
  → [User bayar di halaman Mayar]
  → Mayar redirect → GET /payment/return?txn={transaction_id}
  → Mayar webhook → POST /webhooks/mayar
       → verifikasi via GET Mayar API
       → update transaction + aktivasi premium/addon
```

### File Changes

| File | Action |
|---|---|
| `config/midtrans.php` | Hapus |
| `config/mayar.php` | **Baru** |
| `app/Services/MayarService.php` | **Baru** |
| `app/Http/Controllers/Dashboard/SubscriptionController.php` | Ganti Midtrans → MayarService |
| `app/Http/Controllers/Dashboard/AddonController.php` | Ganti Midtrans → MayarService |
| `app/Http/Controllers/WebhookController.php` | Ganti `midtrans()` → `mayar()` |
| `app/Http/Controllers/PaymentReturnController.php` | **Baru** |
| `app/Enums/PaymentMethod.php` | Tambah `Mayar`, keep `Midtrans` (historis) |
| `resources/js/Pages/Dashboard/Paket.vue` | Hapus Snap.js, ganti redirect |
| `resources/js/Pages/PaymentReturn.vue` | **Baru** — return page dengan polling |
| `routes/web.php` | Ganti webhook route, tambah return route |
| `bootstrap/app.php` | Ganti CSRF exclusion Midtrans → Mayar |
| `composer.json` | Hapus `midtrans/midtrans-php` |

---

## Section 1: Config

**`config/mayar.php`:**
```php
return [
    'api_key'       => env('MAYAR_API_KEY', ''),
    'is_production' => env('MAYAR_IS_PRODUCTION', false),
    'base_url'      => env('MAYAR_IS_PRODUCTION', false)
        ? 'https://api.mayar.id/hl/v1'
        : 'https://api.mayar.club/hl/v1',
];
```

**`.env` variables baru:**
```
MAYAR_API_KEY=
MAYAR_IS_PRODUCTION=false
```

---

## Section 2: MayarService

**`app/Services/MayarService.php`**

Dua method publik:

### `createInvoice(Transaction $transaction, User $user, string $itemName): array`

- `POST {base_url}/invoice/create`
- Header: `Authorization: Bearer {api_key}`
- Body:
  ```json
  {
    "name": "{user->name}",
    "email": "{user->email}",
    "mobile": "{user->phone ?? ''}",
    "redirectUrl": "{url('/payment/return')}?txn={transaction->id}",
    "expiredAt": "{now + 24 jam ISO8601 UTC}",
    "items": [
      {
        "quantity": 1,
        "rate": {transaction->amount},
        "description": "{itemName}"
      }
    ]
  }
  ```
- Return: `['payment_url' => $data['link'], 'mayar_invoice_id' => $data['id']]`
- Throw exception on API error (ditangkap controller, return 500)

### `getInvoice(string $mayarInvoiceId): array`

- `GET {base_url}/invoice/{mayarInvoiceId}`
- Header: `Authorization: Bearer {api_key}`
- Return: raw `data` array dari response Mayar
- Digunakan webhook controller untuk verifikasi

---

## Section 3: Checkout Controllers

### SubscriptionController::checkout

Perubahan dari Midtrans Snap ke Mayar:

```diff
- use Midtrans\Config as MidtransConfig;
- use Midtrans\Snap;
+ use App\Services\MayarService;

  // constructor inject MayarService

  // dalam checkout():
- $this->configureMidtrans();
- $snapToken = Snap::getSnapToken([...]);
- $transaction->update(['payment_gateway_id' => $transaction->id]);
- return response()->json(['snap_token' => $snapToken]);
+ $result = $this->mayarService->createInvoice($transaction, $user, 'Paket Premium TheDay (30 hari)');
+ $transaction->update(['payment_gateway_id' => $result['mayar_invoice_id']]);
+ return response()->json(['payment_url' => $result['payment_url']]);

- private function configureMidtrans(): void { ... }  // hapus
```

Props yang dikirim ke Inertia (`midtransClientKey`, `snapUrl`) juga dihapus.

### AddonController::checkout

Perubahan identik dengan SubscriptionController. Item name: `"Tambah {$quantity} Undangan (Add-on)"`.

`payment_method` di kedua controller: `PaymentMethod::Midtrans` → `PaymentMethod::Mayar`.

---

## Section 4: Webhook

**Route:** `POST /webhooks/mayar` (ganti `/webhooks/midtrans`)
**CSRF:** Exclude di `bootstrap/app.php`

### WebhookController::mayar()

```
1. Baca payload: event, data.id, data.transactionStatus
2. Jika event != "payment.received" → return 200 (ignore)
3. Cari Transaction::where('payment_gateway_id', $data['id'])->first()
   → null: Log::warning, return 404
4. Idempotency: jika $transaction->isPaid() → return 200
5. Verifikasi via MayarService::getInvoice($data['id'])
   → status bukan 'paid': Log::info, return 200 (agar Mayar tidak retry)
6. DB::transaction:
   a. $transaction->update(['status' => Paid, 'paid_at' => now(), 'gateway_response' => $payload])
   b. $transaction->isAddonPurchase() ? activateAddon() : activatePremium()
7. Return 200
```

`activatePremium()` dan `activateAddon()` tidak berubah dari implementasi Midtrans.

**Catatan keamanan:** Mayar tidak mendokumentasikan webhook signature verification. Verifikasi dilakukan di step 5 dengan memanggil Mayar API langsung — jika seseorang spoof webhook, step 5 akan gagal karena invoice ID tidak valid.

---

## Section 5: PaymentReturnController

**Route:** `GET /payment/return` (authenticated, CSRF protected)

**`PaymentReturnController::show(Request $request)`:**
- Cari `Transaction::find($request->txn)` milik `$request->user()`
- Abort 403 jika bukan milik user tersebut
- Render Inertia `PaymentReturn` dengan `['status' => $transaction->status->value, 'transaction_id' => $transaction->id]`

**`resources/js/Pages/PaymentReturn.vue`:**
- Tampilkan pesan sesuai status: `paid` / `pending` / `failed`
- Jika `pending`: polling `GET /api/transactions/{id}/status` setiap 3 detik, max 10x (30 detik)
- Auto-stop polling saat status jadi `paid` atau `failed`
- Setelah max poll: tampilkan tombol "Cek Ulang" manual
- Tombol "Kembali ke Dashboard" selalu tersedia

**Route API polling:** `GET /api/transactions/{transaction}/status` — return `{ status: '...' }`, lightweight, authenticated.

---

## Section 6: Frontend (Paket.vue)

Hapus:
- Props `midtransClientKey`, `snapUrl`
- `snapReady` ref
- `onMounted`/`onUnmounted` Snap.js script loading
- `window.snap.pay()` call

Ganti checkout logic:
```js
const handleCheckout = async () => {
    checkoutState.value = 'loading';
    try {
        const { data } = await axios.post(route('dashboard.subscriptions.checkout'));
        window.location.href = data.payment_url;
    } catch (err) {
        checkoutState.value = 'error';
        checkoutError.value = err?.response?.data?.error ?? 'Terjadi kesalahan.';
    }
};
```

State `pending` dan `success` tidak lagi dihandle di Paket.vue — user diredirect ke return page.

---

## Section 7: PaymentMethod Enum

```php
enum PaymentMethod: string
{
    case Mayar    = 'mayar';
    case Midtrans = 'midtrans'; // keep untuk data historis
    case Xendit   = 'xendit';   // keep, belum dipakai

    public function label(): string
    {
        return match($this) {
            self::Mayar    => 'Mayar',
            self::Midtrans => 'Midtrans',
            self::Xendit   => 'Xendit',
        };
    }
}
```

Tidak perlu DB migration — record lama tetap valid dengan value `'midtrans'`.

---

## Error Handling

| Scenario | Handling |
|---|---|
| Mayar API down saat checkout | Exception → controller return 500 + log error |
| Mayar invoice ID tidak ditemukan di webhook | Log warning, return 404 |
| Mayar API return non-paid saat verifikasi webhook | Log info, return 200 (Mayar tidak retry) |
| Duplikat webhook (idempotency) | Check `isPaid()` di awal, return 200 langsung |
| User akses return page bukan miliknya | Abort 403 |

---

## Out of Scope

- Refund flow (tidak ada di Midtrans sekarang, tidak ditambahkan)
- Multi-gateway / gateway fallback
- Mayar subscription recurring (cron-based — bukan scope ini)
- Admin panel untuk melihat transaksi Mayar
