# Couple Profile — Design Spec
_2026-04-26_

## Problem

Setiap undangan menyimpan data pasangan sendiri-sendiri di `invitation_details`. User yang membuat lebih dari satu undangan harus menginput ulang nama, panggilan, instagram, dan nama orang tua di setiap undangan baru.

## Goal

Data pasangan cukup diisi sekali — saat simpan step 1 undangan manapun, data tersimpan ke profil bersama. Undangan berikutnya otomatis ter-isi dari profil itu. Tidak ada halaman profile terpisah; semua terjadi transparan di flow undangan yang sudah ada.

---

## Data Model

### Tabel baru: `couple_profiles`

| Kolom               | Tipe         | Keterangan              |
|---------------------|--------------|-------------------------|
| `id`                | UUID, PK     |                         |
| `user_id`           | UUID, FK, unique | relasi ke `users`   |
| `groom_name`        | string, nullable |                     |
| `groom_nickname`    | string, nullable |                     |
| `groom_instagram`   | string, nullable |                     |
| `groom_parent_names`| string, nullable |                     |
| `bride_name`        | string, nullable |                     |
| `bride_nickname`    | string, nullable |                     |
| `bride_instagram`   | string, nullable |                     |
| `bride_parent_names`| string, nullable |                     |
| `created_at`        | timestamp    |                         |
| `updated_at`        | timestamp    |                         |

Foto (groom_photo_url, bride_photo_url) **tidak** ikut — tetap per-undangan di `invitation_details`.

### Model

- `CoupleProfile` → `belongsTo(User::class)`
- `User` → `hasOne(CoupleProfile::class)`

---

## Backend Logic

### 1. Saat simpan step 1

**Endpoint:** `POST /api/invitations/{invitation}/details`
**Controller:** `Api\InvitationController@storeDetails`

Setelah `invitation_details` di-upsert seperti biasa, tambahkan upsert ke `couple_profiles`:

```php
auth()->user()->coupleProfile()->updateOrCreate(
    ['user_id' => auth()->id()],
    $coupleFields  // 8 field teks, tanpa foto
);
```

Hanya field yang tidak null yang di-update (agar tidak menghapus data lama kalau user hanya edit sebagian).

### 2. Saat load halaman edit undangan

**Controller:** `Dashboard\InvitationController@edit`

Logika pre-fill:

```
if invitation_details semua couple-fields kosong/null
    AND user punya couple_profile
→ gunakan data couple_profile sebagai details yang dikirim ke frontend
else
→ gunakan invitation_details seperti biasa
```

"Kosong" didefinisikan: `groom_name`, `bride_name`, `groom_nickname`, `bride_nickname` semuanya null atau empty string.

Tidak ada endpoint baru. Pre-fill terjadi di `edit()` sebelum data dikirim ke Inertia.

---

## Frontend

Tidak ada perubahan. Data yang dikirim controller sudah berisi pre-fill → form step 1 tampil terisi otomatis. `saveStep1()` di composable tidak berubah.

---

## Out of Scope

- Halaman "Couple Profile" terpisah
- Sinkronisasi dua arah (edit undangan → update semua undangan lain)
- Foto pasangan di couple_profile
- API endpoint baru untuk couple_profile

---

## Files yang Berubah

| File | Perubahan |
|------|-----------|
| `database/migrations/...create_couple_profiles_table.php` | Tabel baru |
| `app/Models/CoupleProfile.php` | Model baru |
| `app/Models/User.php` | Tambah `hasOne(CoupleProfile::class)` |
| `app/Http/Controllers/Api/InvitationController.php` | Upsert couple_profile di `storeDetails()` |
| `app/Http/Controllers/Dashboard/InvitationController.php` | Pre-fill logic di `edit()` |
