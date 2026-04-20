# TheDay — Pricing & Invitation Quota Spec

> Dokumen ini menjelaskan struktur harga, kuota undangan, dan business rules untuk sistem add-on undangan di TheDay.

---

## Tier Overview

| | Free | Premium |
|---|---|---|
| Harga | Rp 0 | Rp 35.000 / 3 bulan (90 hari) |
| Undangan aktif | Max 1 | Max 2 (default) |
| Undangan tambahan | ❌ Tidak bisa | ✅ +Rp 15.000 / undangan |
| Template | Free saja | Semua template |
| Foto gallery | Max 5 | Unlimited |
| Musik | Library default | Upload sendiri |
| Watermark | Selalu muncul | Tidak ada |
| Custom slug | ❌ | ✅ |
| Analytics | ❌ | ✅ |
| Password protection | ❌ | ✅ |
| Custom domain | ❌ | ✅ |
| Slug format | `theday.id/i/nama-x2m4` | `theday.id/nama` |

---

## Add-on: Undangan Tambahan

- **Harga:** Rp 15.000 / 1 undangan tambahan
- **Syarat:** Hanya bisa dibeli oleh user **Premium aktif**
- **Masa aktif add-on:** Mengikuti masa aktif subscription Premium
  - Jika subscription expired → add-on ikut expired
  - Jika renew → add-on **tidak otomatis diperpanjang**, harus beli ulang
- **Jumlah:** Bisa beli lebih dari 1 add-on sekaligus
- **Contoh:** Premium default (2 undangan) + beli 3 add-on = total 5 undangan aktif

---

## Business Rules

### User Free

- Hanya bisa membuat **1 undangan** dengan fitur free
- **Tidak bisa** membeli add-on undangan tambahan
- Jika mencoba publish undangan ke-2, sistem menampilkan:
  > *"Kamu sudah mencapai batas undangan gratis. Upgrade ke Premium untuk membuat lebih banyak undangan."*
- Slug otomatis readable + suffix pendek: `theday.id/i/ardi-novia-x2m4`

### User Premium

- Default quota: **2 undangan aktif**
- Bisa tambah kuota dengan beli add-on **Rp 15.000 / undangan**
- Jika mencoba publish undangan melebihi kuota, sistem menampilkan:
  > *"Kuota undangan kamu penuh. Tambah 1 undangan hanya Rp 15.000."*
- Slug bersih tanpa suffix: `theday.id/ardi-novia`

### Saat Premium Expired (Grace Period)

- Kuota undangan **dibekukan** — tidak bisa publish undangan baru
- Undangan yang sudah published tetap live (sesuai grace period 30 hari)
- Add-on yang sudah dibeli ikut expired bersama subscription
- User tidak bisa beli add-on baru selama tidak premium aktif

### Saat Grace Period Habis → Downgrade ke Free

- Kuota turun ke **1 undangan**
- Undangan yang melewati limit free (ke-2 dan seterusnya) → otomatis `archived`
- Urutan arsip: undangan yang paling lama tidak diedit (`updated_at` terlama)
- Undangan ke-1 (paling baru diupdate) → tetap published dengan fitur free
- Add-on hangus, tidak ada refund

---

## Database Schema

### Tabel `invitation_addons`

```sql
CREATE TABLE invitation_addons (
    id              CHAR(36) PRIMARY KEY,
    user_id         CHAR(36) NOT NULL,
    subscription_id CHAR(36) NOT NULL,
    quantity        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    price_per_unit  INT NOT NULL DEFAULT 15000,
    total_price     INT NOT NULL,
    paid_at         TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,   -- sama dengan subscription expires_at
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);
```

### Kalkulasi Kuota di Model

```php
// User model atau Subscription model
public function invitationQuota(): int
{
    $subscription = $this->subscription;

    // Free user
    if (!$subscription || $subscription->plan === 'free') {
        return 1;
    }

    // Premium expired
    if (!$subscription->isActive()) {
        return 0; // tidak bisa publish baru
    }

    // Premium aktif: default 2 + add-on aktif
    $baseQuota = 2;
    $addonQuota = $subscription->activeAddons()->sum('quantity');

    return $baseQuota + $addonQuota;
}

public function canPublishInvitation(): bool
{
    $quota = $this->invitationQuota();
    $published = $this->invitations()->where('status', 'published')->count();

    return $published < $quota;
}
```

### Relasi di Model `Subscription`

```php
public function activeAddons()
{
    return $this->hasMany(InvitationAddon::class)
        ->whereNotNull('paid_at')
        ->where('expires_at', '>', now());
}
```

---

## Flow Pembelian Add-on

```
User klik "Tambah Undangan" di dashboard
        │
        ▼
Cek apakah Premium aktif?
        │
   Tidak ──► Tampilkan prompt upgrade ke Premium
        │
       Ya
        │
        ▼
Tampilkan modal: "Tambah 1 undangan — Rp 15.000"
        │
        ▼
User konfirmasi → redirect ke Midtrans payment
        │
        ▼
Payment berhasil → buat record di `invitation_addons`
expires_at = subscription.expires_at
        │
        ▼
Kuota undangan user bertambah 1
```

---

## Kuota Quota Summary

```
FREE USER
─────────────────────────────────────────
Quota default    : 1 undangan
Add-on           : ❌ Tidak tersedia
Slug format      : theday.id/i/nama-xxxx

PREMIUM USER (aktif)
─────────────────────────────────────────
Quota default    : 2 undangan
Add-on           : +1 per Rp 15.000
                   (maks sesuai kebutuhan)
Slug format      : theday.id/nama

PREMIUM USER (grace period)
─────────────────────────────────────────
Quota publish baru : 0 (tidak bisa publish)
Undangan existing  : tetap live s/d grace habis
Add-on             : tidak bisa beli baru

SETELAH GRACE HABIS (downgrade ke free)
─────────────────────────────────────────
Quota             : 1 undangan
Undangan ke-2+    : otomatis archived
Add-on            : hangus
```

---

## Catatan Implementasi

- Cek kuota dilakukan di **controller sebelum publish**, bukan hanya di frontend
- Add-on **tidak bisa ditransfer** ke subscription berikutnya
- Saat renew premium, tampilkan reminder:
  > *"Subscription kamu diperpanjang. Undangan tambahan yang sebelumnya perlu dibeli ulang."*
- Jika user punya 3 undangan published (2 default + 1 add-on) lalu tidak renew:
  - Grace: semua 3 undangan tetap live
  - Setelah grace: hanya 1 yang tetap published (paling baru diupdate), 2 lainnya archived
