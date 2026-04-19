# TheDay — Subscription & Invitation Expiry Spec

> Dokumen ini menjelaskan business rules, database schema, dan implementation guide untuk sistem subscription expiry, grace period, dan slug reservation di TheDay.

---

## Overview

TheDay memiliki dua tier:
- **Free** — Rp 0, fitur terbatas, ada watermark
- **Premium** — Rp 35.000 / 3 bulan (90 hari), full fitur

Masa aktif undangan **tidak bergantung pada tanggal acara** yang diisi user, melainkan pada status subscription. Ini mencegah manipulasi dengan cara mengganti-ganti tanggal acara.

---

## Business Rules

### 1. Masa Aktif Premium

- Subscription aktif selama 90 hari sejak `starts_at`
- `expires_at = starts_at + 90 hari`
- Saat expired, grace period dimulai otomatis
- `grace_until = expires_at + 30 hari`

### 2. Grace Period (30 hari)

Selama grace period:
- Undangan **tetap bisa diakses publik** (tamu masih bisa buka link)
- Editor undangan menjadi **read-only**
- Fitur premium dibekukan:
  - Custom URL slug premium
  - Upload musik sendiri
  - Analytics dashboard
  - Password protection
  - Custom domain
- Watermark "Dibuat dengan TheDay" **muncul kembali**
- Dashboard menampilkan banner peringatan + countdown hari tersisa + tombol Renew

### 3. Setelah Grace Period Habis

- Undangan otomatis berubah status menjadi `archived`
- Link publik (`/slug`) menampilkan halaman **"Undangan ini sudah tidak aktif"** — bukan 404
- Data tidak dihapus

### 4. Slug Reservation

- **Slug tidak pernah dilepas** selama undangan masih ada di database, apapun statusnya (`published`, `archived`, `draft`)
- Slug hanya bisa dipakai ulang oleh orang lain jika:
  - User menghapus undangan secara manual, atau
  - Data dihapus permanen setelah periode retensi (misal 1 tahun setelah archived)
- Saat archived, link `/slug` tetap valid dan menampilkan halaman "tidak aktif", bukan dialihkan ke undangan lain

### 5. Renew

- User bisa renew kapan saja, bahkan setelah grace period habis
- Setelah renew, status undangan kembali ke `published`
- `expires_at` dan `grace_until` di-reset dari tanggal renew

### 6. Tanggal Acara

- Tanggal acara hanya digunakan untuk **ditampilkan di konten undangan**
- Tanggal acara **tidak mempengaruhi** logika expired/grace period sama sekali

---

## Database Schema

### Tabel `subscriptions`

```sql
CREATE TABLE subscriptions (
    id          CHAR(36) PRIMARY KEY,
    user_id     CHAR(36) NOT NULL,
    plan        ENUM('free', 'premium') DEFAULT 'free',
    starts_at   TIMESTAMP NULL,
    expires_at  TIMESTAMP NULL,
    grace_until TIMESTAMP NULL,
    status      ENUM('active', 'grace', 'expired') DEFAULT 'active',
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_subscriptions_user_id (user_id),
    INDEX idx_subscriptions_expires_at (expires_at),
    INDEX idx_subscriptions_grace_until (grace_until)
);
```

### Tabel `invitations` (tambahan kolom)

```sql
ALTER TABLE invitations
    ADD COLUMN status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    ADD INDEX idx_invitations_status (status),
    ADD INDEX idx_invitations_slug (slug);
```

> Pastikan kolom `slug` memiliki constraint `UNIQUE` dan tidak dihapus saat status berubah.

---

## Model: `Subscription`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'plan', 'starts_at', 'expires_at', 'grace_until', 'status',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'expires_at'  => 'datetime',
        'grace_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Subscription masih dalam masa aktif premium */
    public function isActive(): bool
    {
        return $this->plan === 'premium'
            && $this->expires_at !== null
            && now()->lessThanOrEqualTo($this->expires_at);
    }

    /** Subscription expired tapi masih dalam grace period */
    public function isInGracePeriod(): bool
    {
        return $this->plan === 'premium'
            && $this->expires_at !== null
            && now()->greaterThan($this->expires_at)
            && $this->grace_until !== null
            && now()->lessThanOrEqualTo($this->grace_until);
    }

    /** Grace period sudah habis */
    public function isFullyExpired(): bool
    {
        if ($this->plan === 'free') return false;

        return $this->grace_until !== null
            && now()->greaterThan($this->grace_until);
    }

    /** User berhak atas fitur premium (hanya saat active) */
    public function hasPremiumAccess(): bool
    {
        return $this->isActive();
    }

    /** Sisa hari grace period */
    public function graceDaysRemaining(): int
    {
        if (!$this->isInGracePeriod()) return 0;
        return (int) now()->diffInDays($this->grace_until);
    }
}
```

---

## Observer: `SubscriptionObserver`

Otomatis set `grace_until` saat subscription expired.

```php
<?php

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionObserver
{
    public function saving(Subscription $subscription): void
    {
        // Set grace_until otomatis saat expires_at diisi atau diubah
        if ($subscription->isDirty('expires_at') && $subscription->expires_at) {
            $subscription->grace_until = $subscription->expires_at->addDays(30);
        }
    }
}
```

Daftarkan di `AppServiceProvider`:

```php
use App\Models\Subscription;
use App\Observers\SubscriptionObserver;

Subscription::observe(SubscriptionObserver::class);
```

---

## Artisan Command: `invitations:archive-expired`

Jalankan setiap hari untuk mengarsipkan undangan yang grace period-nya sudah habis.

```php
<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveExpiredInvitations extends Command
{
    protected $signature   = 'invitations:archive-expired';
    protected $description = 'Arsipkan undangan milik user yang grace period-nya sudah habis';

    public function handle(): void
    {
        $expiredUserIds = Subscription::where('plan', 'premium')
            ->where('grace_until', '<', now())
            ->pluck('user_id');

        if ($expiredUserIds->isEmpty()) {
            $this->info('Tidak ada undangan yang perlu diarsipkan.');
            return;
        }

        $count = Invitation::whereIn('user_id', $expiredUserIds)
            ->where('status', 'published')
            ->update(['status' => 'archived']);

        $this->info("Berhasil mengarsipkan {$count} undangan.");
    }
}
```

Daftarkan di `routes/console.php` (Laravel 11):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('invitations:archive-expired')->dailyAt('01:00');
```

---

## Middleware

### `CheckInvitationAccess` — untuk route public `/{slug}`

```php
<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use Closure;
use Illuminate\Http\Request;

class CheckInvitationAccess
{
    public function handle(Request $request, Closure $next)
    {
        $invitation = $request->route('invitation'); // model binding

        if ($invitation->status === 'archived') {
            return inertia('Invitation/Inactive', [
                'message' => 'Undangan ini sudah tidak aktif.',
            ]);
        }

        return $next($request);
    }
}
```

### `CheckPremiumFeature` — untuk route editor & fitur premium

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPremiumFeature
{
    public function handle(Request $request, Closure $next)
    {
        $subscription = $request->user()->subscription;

        if (!$subscription || !$subscription->hasPremiumAccess()) {
            return redirect()->route('pricing')
                ->with('warning', 'Fitur ini membutuhkan subscription Premium aktif.');
        }

        return $next($request);
    }
}
```

Daftarkan di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'invitation.access'  => \App\Http\Middleware\CheckInvitationAccess::class,
        'premium.feature'    => \App\Http\Middleware\CheckPremiumFeature::class,
    ]);
})
```

---

## Routes

```php
// Public invitation page
Route::get('/{slug}', InvitationController::class)
    ->name('invitation.show')
    ->middleware('invitation.access');

// Editor — hanya untuk premium aktif
Route::middleware(['auth', 'premium.feature'])->group(function () {
    Route::get('/dashboard/invitations/{invitation}/edit', [EditorController::class, 'edit']);
    Route::put('/dashboard/invitations/{invitation}', [EditorController::class, 'update']);
});
```

---

## Slug Availability Check

Saat user memilih slug, pastikan slug yang sudah dipakai (apapun statusnya) tidak bisa dipakai ulang.

```php
// InvitationController atau SlugController
public function checkSlug(Request $request): JsonResponse
{
    $slug = $request->input('slug');

    // Exclude undangan milik user sendiri jika sedang edit
    $query = Invitation::where('slug', $slug);

    if ($request->has('current_invitation_id')) {
        $query->where('id', '!=', $request->input('current_invitation_id'));
    }

    $isAvailable = $query->doesntExist();

    return response()->json(['available' => $isAvailable]);
}
```

---

## Dashboard Banner (Vue Component)

Tampilkan peringatan di dashboard saat user dalam grace period.

```vue
<!-- resources/js/Components/GracePeriodBanner.vue -->
<template>
  <div
    v-if="showBanner"
    class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center justify-between gap-4"
  >
    <div class="flex items-center gap-3">
      <span class="text-amber-600 text-lg">⚠️</span>
      <div>
        <p class="text-sm font-medium text-amber-800">
          Subscription kamu sudah habis
        </p>
        <p class="text-xs text-amber-700">
          Undangan masih aktif selama
          <strong>{{ daysRemaining }} hari</strong> lagi.
          Setelah itu, undangan akan diarsipkan.
        </p>
      </div>
    </div>
    <a
      :href="route('pricing')"
      class="shrink-0 text-xs font-semibold bg-amber-600 text-white px-3 py-1.5 rounded-md hover:bg-amber-700 transition"
    >
      Perpanjang Sekarang
    </a>
  </div>
</template>

<script setup>
const props = defineProps({
  subscriptionStatus: String, // 'active' | 'grace' | 'expired'
  daysRemaining: Number,
});

const showBanner = computed(() =>
  props.subscriptionStatus === 'grace' && props.daysRemaining > 0
);
</script>
```

Pass data dari controller via Inertia:

```php
// DashboardController
public function index(Request $request): Response
{
    $subscription = $request->user()->subscription;

    return inertia('Dashboard/Index', [
        'subscriptionStatus' => $subscription?->status ?? 'free',
        'graceDaysRemaining' => $subscription?->graceDaysRemaining() ?? 0,
    ]);
}
```

---

## Email Reminder

Kirim reminder saat grace period berjalan. Trigger dari scheduler:

```php
// routes/console.php
Schedule::call(function () {
    $graceSubscriptions = Subscription::where('plan', 'premium')
        ->where('status', 'grace')
        ->get();

    foreach ($graceSubscriptions as $sub) {
        $daysRemaining = $sub->graceDaysRemaining();

        if (in_array($daysRemaining, [30, 15, 2])) {
            $sub->user->notify(new GracePeriodReminderNotification($daysRemaining));
        }
    }
})->dailyAt('08:00');
```

---

## Status Flow Summary

```
[Draft] ──publish──► [Published]
                          │
                    premium expires
                          │
                          ▼
                    [Grace Period] ── renew ──► [Published]
                          │
                    30 hari berlalu
                          │
                          ▼
                      [Archived] ── renew ──► [Published]
                          │
                    user hapus manual
                          │
                          ▼
                       [Deleted]
                     (slug dilepas)
```

---

## Catatan Penting

- **Slug tidak pernah dilepas** kecuali data dihapus permanen
- **Tanggal acara tidak mempengaruhi** logika expired sama sekali
- **Archived bukan deleted** — data tetap ada, hanya tidak ditampilkan ke publik
- **Grace period 30 hari** dihitung dari `expires_at`, bukan dari tanggal acara
- Halaman undangan archived menampilkan pesan "tidak aktif", bukan 404


---

## Free Tier Rules

### Karakteristik User Free

- Tidak punya subscription premium → tidak ada `expires_at`, tidak ada grace period
- Undangan tetap **live selama akun aktif**, tidak ada batas waktu
- Batasan adalah **fitur**, bukan waktu

### Batasan Fitur Free

| Fitur | Free | Premium |
|---|---|---|
| Jumlah undangan aktif | Max 1 | Unlimited |
| Foto gallery | Max 5 | Unlimited |
| Template | Free saja | Semua template |
| Musik | Library default | Upload sendiri |
| Watermark | Selalu muncul | Tidak ada |
| Custom slug premium | ❌ | ✅ |
| Analytics dashboard | ❌ | ✅ |
| Password protection | ❌ | ✅ |
| Custom domain | ❌ | ✅ |

### Kapan Undangan Free Bisa Hilang

- User **menghapus undangan secara manual**
- **Akun user dihapus** → undangan ikut dihapus, slug dilepas
- User **upgrade ke premium lalu expired + grace habis** → lihat Downgrade Logic di bawah

---

## Downgrade Logic (Premium → Free)

Terjadi saat: subscription premium expired **dan** grace period 30 hari sudah habis.

### Aturan Downgrade

1. Status subscription berubah menjadi `free`
2. Semua undangan yang berstatus `published` dievaluasi:
   - Undangan **ke-1** (berdasarkan yang paling baru aktif/diedit) → tetap `published` dengan batasan free
   - Undangan **ke-2 dan seterusnya** → otomatis `archived`
3. Fitur premium pada undangan yang masih published di-downgrade:
   - Watermark "Dibuat dengan TheDay" **muncul kembali**
   - Musik custom → fallback ke **library default**
   - Custom domain → tidak berfungsi, undangan hanya bisa diakses via slug default
   - Analytics → tidak bisa diakses
   - Password protection → dinonaktifkan (undangan jadi public)
   - Custom slug → tetap reserved dan bisa diakses, tapi fitur premium slug dibekukan

### Urutan Arsip Saat Downgrade

Jika user punya lebih dari 1 undangan published, urutan yang diarsipkan:
- Prioritas arsip: undangan yang **paling lama tidak diedit** (`updated_at` paling lama)
- Undangan yang **paling baru diupdate** dipertahankan sebagai satu-satunya undangan free aktif

```php
// Artisan command tambahan: handle downgrade
$user = User::find($userId);
$subscription = $user->subscription;

if ($subscription->isFullyExpired()) {
    $publishedInvitations = $user->invitations()
        ->where('status', 'published')
        ->orderByDesc('updated_at')
        ->get();

    // Pertahankan yang paling baru, arsipkan sisanya
    $publishedInvitations->skip(1)->each(function ($invitation) {
        $invitation->update(['status' => 'archived']);
    });

    // Downgrade fitur pada undangan yang dipertahankan
    $kept = $publishedInvitations->first();
    if ($kept) {
        $kept->update([
            'custom_music'       => null,      // fallback ke default
            'password_protected' => false,
            'custom_domain'      => null,
        ]);
    }

    // Update status subscription ke free
    $subscription->update([
        'plan'        => 'free',
        'status'      => 'active',
        'expires_at'  => null,
        'grace_until' => null,
    ]);
}
```

---

## Status Flow Lengkap (Free + Premium)

```
USER FREE
─────────────────────────────────────────────
[Draft] ──publish──► [Published/Free]
                          │
                    akun dihapus /
                    hapus manual
                          │
                          ▼
                       [Deleted]
                     (slug dilepas)


USER PREMIUM
─────────────────────────────────────────────
[Draft] ──publish──► [Published/Premium]
                          │
                    subscription expires
                          │
                          ▼
                  [Grace Period 30 hari]
                    /             \
                renew           tidak renew
                  │                  │
                  ▼                  ▼
         [Published/Premium]   [Downgrade ke Free]
                              undangan ke-1 → Published/Free
                              undangan ke-2+ → Archived
                                    │
                              hapus manual /
                              akun dihapus
                                    │
                                    ▼
                                [Deleted]
                              (slug dilepas)
```

---

## Ringkasan Perbedaan Free vs Premium

| Aspek | Free | Premium |
|---|---|---|
| Batas waktu undangan | Tidak ada (selama akun aktif) | Terikat subscription + grace 30 hari |
| Grace period | Tidak ada | 30 hari setelah `expires_at` |
| Downgrade logic | N/A | Arsipkan undangan ke-2+ |
| Slug dilepas kapan | Saat hapus manual / akun dihapus | Saat hapus manual / akun dihapus |
| Fitur | Terbatas (lihat tabel fitur) | Full |
