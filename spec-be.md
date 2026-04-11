# Spec Backend Laravel — Budget Planner TheDay

Dokumen ini menerjemahkan `spec-budget-planner.md` menjadi spesifikasi implementasi backend Laravel 11 yang siap dikerjakan, sekaligus memastikan kontrak data dan alur backend mendukung UI/UX pada `specUI-2.md`.[file:1][file:2]

## Tujuan Backend

Backend Budget Planner harus mendukung pengalaman yang sederhana, cepat, mobile-friendly, dan terasa menenangkan, bukan seperti aplikasi finance yang berat.[file:1][file:2]
Backend perlu mengutamakan akurasi perhitungan, konsistensi data, format Rupiah, dan response yang efisien untuk halaman overview, halaman utama Budget Planner, form tambah/edit item, filter, sort, dan manage kategori.[file:1][file:2]

## Prinsip Arsitektur

- Stack utama: Laravel 11 + Vue 3 + Inertia, sehingga implementasi awal disarankan memakai web route, controller, form request, service/action, dan Inertia props, bukan public REST API penuh.[file:1]
- Semua nominal disimpan sebagai integer Rupiah tanpa desimal untuk menghindari error floating point.[file:1]
- Summary dan breakdown harus dihitung dari item aktif saja; item archived tidak ikut summary aktif.[file:1][file:2]
- UI perlu menampilkan `actual_amount` kosong sebagai “belum dicatat”, sehingga backend harus membedakan `null` vs `0` pada level penyimpanan/serialization.[file:1][file:2]
- Respons backend harus mendukung mobile-first UI: payload ringkas, agregasi siap pakai, dan filter yang tidak memaksa client menghitung ulang terlalu banyak.[file:2]

## Ruang Lingkup Backend MVP

Backend MVP harus mencakup inisialisasi planner, update total budget, CRUD + archive kategori, CRUD + archive item, summary budget, category breakdown, item listing dengan search/filter/sort, dan widget overview dashboard.[file:1][file:2]
Backend MVP belum perlu mendukung multi-currency, upload bukti transaksi, approval workflow, split family payment, integrasi vendor payment, maupun collaboration real-time.[file:1]

## Domain Model

### Entitas utama

- `WeddingBudget`: root aggregate untuk planner per user/per invitation context.[file:1]
- `WeddingBudgetCategory`: kategori bawaan atau custom di dalam sebuah budget planner.[file:1]
- `WeddingBudgetItem`: item planned/actual spending yang menjadi sumber seluruh summary.[file:1]

### Relasi

- Satu user memiliki satu budget planner utama untuk wedding plan mereka.[file:1]
- Satu `wedding_budgets` memiliki banyak `wedding_budget_categories`.[file:1]
- Satu `wedding_budgets` memiliki banyak `wedding_budget_items`.[file:1]
- Satu `wedding_budget_items.category_id` mengarah ke satu kategori aktif/terarsip.[file:1]
- `invitation_id` boleh nullable pada budget dan item agar tetap fleksibel untuk konteks global wedding plan atau invitation tertentu.[file:1][file:2]

## Skema Database

### Tabel `wedding_budgets`

Kolom dasar yang direkomendasikan dari spec produk.[file:1]

```php
Schema::create('wedding_budgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
    $table->unsignedBigInteger('total_budget')->nullable();
    $table->string('currency', 3)->default('IDR');
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['user_id']);
    $table->index(['invitation_id']);
});
```

Catatan implementasi:
- Unique `user_id` sesuai rule “setiap user memiliki satu budget planner utama”.[file:1]
- `total_budget` nullable karena user boleh skip saat first-time setup.[file:1][file:2]
- `currency` tetap disimpan walau MVP hanya IDR, agar evolusi premium ke depan lebih aman.[file:1]

### Tabel `wedding_budget_categories`

```php
Schema::create('wedding_budget_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('budget_id')->constrained('wedding_budgets')->cascadeOnDelete();
    $table->string('name');
    $table->enum('type', ['system', 'custom'])->default('system');
    $table->unsignedInteger('sort_order')->nullable();
    $table->boolean('is_archived')->default(false);
    $table->timestamps();

    $table->index(['budget_id', 'is_archived']);
    $table->index(['budget_id', 'type']);
});
```

Tambahan rekomendasi backend:
- Pertimbangkan kolom `slug` nullable internal untuk mapping kategori sistem lintas seeding/upgrade, misalnya `venue`, `catering`, `dekorasi`, `busana`.[file:1]
- Untuk kategori bawaan, edit nama bisa dibatasi di level policy/service jika ingin struktur tetap stabil pada MVP.[file:1][file:2]

### Tabel `wedding_budget_items`

```php
Schema::create('wedding_budget_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('budget_id')->constrained('wedding_budgets')->cascadeOnDelete();
    $table->foreignId('category_id')->constrained('wedding_budget_categories')->restrictOnDelete();
    $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
    $table->string('title');
    $table->string('vendor_name')->nullable();
    $table->text('notes')->nullable();
    $table->unsignedBigInteger('planned_amount')->default(0);
    $table->unsignedBigInteger('actual_amount')->nullable();
    $table->enum('payment_status', ['unpaid', 'dp', 'paid'])->default('unpaid');
    $table->date('payment_date')->nullable();
    $table->boolean('is_archived')->default(false);
    $table->softDeletes();
    $table->timestamps();

    $table->index(['budget_id', 'category_id']);
    $table->index(['budget_id', 'payment_status']);
    $table->index(['budget_id', 'is_archived']);
    $table->index(['payment_date']);
    $table->index(['title']);
    $table->index(['vendor_name']);
});
```

Keputusan penting:
- Saya merekomendasikan `actual_amount` nullable, bukan default `0`, karena UI secara eksplisit perlu membedakan “belum dicatat” dari nilai nol.[file:1][file:2]
- `softDeletes()` tetap dipakai untuk audit ringan, tetapi perilaku UI harian cukup memakai archive agar item tidak hilang total.[file:1][file:2]

## Seeder Kategori Default

Saat user pertama kali mengaktifkan Budget Planner, sistem harus membuat kategori default agar user tidak mulai dari layar kosong.[file:1][file:2]
Kategori yang perlu dibuat minimal: Venue, Catering, Dekorasi, Busana, Makeup & Beauty, Dokumentasi, Hiburan, Undangan, Souvenir, Administrasi, Transportasi, dan Lainnya.[file:1]

Contoh source of truth sebaiknya disimpan sebagai config:

```php
return [
    ['slug' => 'venue', 'name' => 'Venue', 'sort_order' => 10],
    ['slug' => 'catering', 'name' => 'Catering', 'sort_order' => 20],
    ['slug' => 'dekorasi', 'name' => 'Dekorasi', 'sort_order' => 30],
    ['slug' => 'busana', 'name' => 'Busana', 'sort_order' => 40],
    ['slug' => 'makeup-beauty', 'name' => 'Makeup & Beauty', 'sort_order' => 50],
    ['slug' => 'dokumentasi', 'name' => 'Dokumentasi', 'sort_order' => 60],
    ['slug' => 'hiburan', 'name' => 'Hiburan', 'sort_order' => 70],
    ['slug' => 'undangan', 'name' => 'Undangan', 'sort_order' => 80],
    ['slug' => 'souvenir', 'name' => 'Souvenir', 'sort_order' => 90],
    ['slug' => 'administrasi', 'name' => 'Administrasi', 'sort_order' => 100],
    ['slug' => 'transportasi', 'name' => 'Transportasi', 'sort_order' => 110],
    ['slug' => 'lainnya', 'name' => 'Lainnya', 'sort_order' => 120],
];
```

## Eloquent Model

### `WeddingBudget`

```php
class WeddingBudget extends Model
{
    protected $fillable = [
        'user_id',
        'invitation_id',
        'total_budget',
        'currency',
        'notes',
    ];

    public function user(): BelongsTo {}
    public function invitation(): BelongsTo {}
    public function categories(): HasMany {}
    public function activeCategories(): HasMany {}
    public function items(): HasMany {}
    public function activeItems(): HasMany {}
}
```

### `WeddingBudgetCategory`

```php
class WeddingBudgetCategory extends Model
{
    protected $fillable = [
        'budget_id', 'name', 'type', 'sort_order', 'is_archived', 'slug'
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function budget(): BelongsTo {}
    public function items(): HasMany {}
    public function activeItems(): HasMany {}
}
```

### `WeddingBudgetItem`

```php
class WeddingBudgetItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'budget_id',
        'category_id',
        'invitation_id',
        'title',
        'vendor_name',
        'notes',
        'planned_amount',
        'actual_amount',
        'payment_status',
        'payment_date',
        'is_archived',
    ];

    protected $casts = [
        'planned_amount' => 'integer',
        'actual_amount' => 'integer',
        'payment_date' => 'date',
        'is_archived' => 'boolean',
    ];

    public function budget(): BelongsTo {}
    public function category(): BelongsTo {}
}
```

## Enum dan Konstanta Domain

Gunakan enum PHP native agar status lebih aman dan mudah dipakai di form/filter.[file:1][file:2]

```php
enum BudgetPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case DP = 'dp';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum bayar',
            self::DP => 'DP',
            self::Paid => 'Lunas',
        };
    }
}
```

```php
enum BudgetCategoryType: string
{
    case System = 'system';
    case Custom = 'custom';
}
```

## Business Rules Implementasi

### Budget planner

- Jika user belum punya planner, endpoint initialize harus membuat `wedding_budgets` dan kategori default dalam satu transaction.[file:1][file:2]
- User boleh memakai fitur walau `total_budget` belum diisi.[file:1][file:2]
- Jika `total_budget` null, summary tetap berjalan untuk planned/actual, tetapi field `remaining_budget`, `usage_percentage`, dan `is_total_overbudget` harus mengembalikan state nullable yang aman bagi UI.[file:1][file:2]

### Category

- Kategori system boleh dipakai langsung begitu planner dibuat.[file:1]
- Kategori custom bisa ditambah bebas untuk premium-ready architecture, walau batasan paket bisa diterapkan nanti di policy/service.[file:1]
- Archive category tidak boleh menghapus item historis; category cukup ditandai `is_archived = true`.[file:1][file:2]
- Jika category masih punya item aktif, archive harus lewat confirmation dan bisa ditolak bila aturan bisnis mengharuskan cleanup dulu.[file:2]

### Item

- `title` wajib, `category_id` wajib, nominal harus angka positif atau nol.[file:1][file:2]
- `planned_amount` dan `actual_amount` independen.[file:1]
- `actual_amount = null` berarti “belum dicatat”; pada kalkulasi total diperlakukan sebagai 0, tetapi pada serializer UI harus tetap null agar frontend bisa merender label yang sesuai.[file:1][file:2]
- `payment_status` default `unpaid`.[file:1]
- Delete untuk MVP sebaiknya diterjemahkan menjadi archive pada flow normal; hard delete hanya untuk admin/internal tooling.[file:1][file:2]

## Rumus Kalkulasi Domain

Semua kalkulasi di bawah harus mengabaikan item yang `is_archived = true` dan item yang `deleted_at != null`.[file:1][file:2]

### Summary total

- `total_planned = SUM(planned_amount)`.[file:1][file:2]
- `total_actual = SUM(COALESCE(actual_amount, 0))`.[file:1][file:2]
- `remaining_budget = total_budget - total_actual` jika `total_budget` terisi, selain itu `null`.[file:1][file:2]
- `planned_vs_actual_gap = total_planned - total_actual`.[file:1][file:2]
- `is_total_overbudget = total_budget !== null && total_actual > total_budget`.[file:1]
- `overbudget_amount = max(total_actual - total_budget, 0)` jika `total_budget` terisi.[file:1][file:2]
- `usage_percentage = min(round((total_actual / total_budget) * 100, 2), 100)` untuk progress bar jika `total_budget > 0`; jika actual melebihi budget, progress UI tetap 100% tetapi metadata overbudget harus tetap muncul.[file:2]

### Summary kategori

- `category_planned_total = SUM(planned_amount)`.[file:1]
- `category_actual_total = SUM(COALESCE(actual_amount, 0))`.[file:1]
- `category_remaining = category_planned_total - category_actual_total`.[file:1][file:2]
- `is_category_overbudget = category_actual_total > category_planned_total && category_planned_total > 0`.[file:1][file:2]
- `category_usage_percentage = 0` jika planned 0; selain itu `min(round((actual/planned)*100, 2), 100)`.[file:2]

### Status kategori untuk UI

Agar UI bisa menampilkan status `Normal / Mendekati limit / Overbudget`, backend sebaiknya mengirim status siap pakai.[file:2]

```php
if ($planned <= 0 && $actual <= 0) {
    $status = 'normal';
} elseif ($planned > 0 && $actual > $planned) {
    $status = 'overbudget';
} elseif ($planned > 0 && ($actual / $planned) >= 0.8) {
    $status = 'near_limit';
} else {
    $status = 'normal';
}
```

## Query Strategy

Untuk halaman utama, backend idealnya mengirim satu payload Inertia yang sudah berisi `budget`, `summary`, `categoryBreakdown`, `items`, `filters`, `options`, dan `uiFlags`, sehingga frontend tidak perlu menghitung ulang banyak state untuk summary cards, progress total, category cards, search/filter/sort, item list, dan empty states.[file:1][file:2]
Widget overview dashboard cukup memerlukan payload kecil berisi total budget, total actual, remaining budget, usage percentage, jumlah kategori overbudget, dan state CTA.[file:1][file:2]

## Routing Laravel

```php
Route::middleware(['auth', 'verified'])
    ->prefix('dashboard/budget-planner')
    ->name('budget-planner.')
    ->group(function () {
        Route::get('/', [BudgetPlannerPageController::class, 'index'])->name('index');
        Route::post('/initialize', [InitializeBudgetPlannerController::class, 'store'])->name('initialize');
        Route::patch('/budget', [UpdateBudgetController::class, 'update'])->name('budget.update');

        Route::get('/categories', [BudgetCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [BudgetCategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [BudgetCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [BudgetCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/items', [BudgetItemController::class, 'index'])->name('items.index');
        Route::post('/items', [BudgetItemController::class, 'store'])->name('items.store');
        Route::patch('/items/{item}', [BudgetItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [BudgetItemController::class, 'destroy'])->name('items.destroy');
    });
```

## Controller dan Service Layer

Disarankan memakai controller tipis + service/action class agar logic domain tetap rapi dan mudah dites.[file:1]
Action yang direkomendasikan: `InitializeWeddingBudgetAction`, `UpdateWeddingBudgetAction`, `CreateBudgetCategoryAction`, `UpdateBudgetCategoryAction`, `ArchiveBudgetCategoryAction`, `CreateBudgetItemAction`, `UpdateBudgetItemAction`, `ArchiveBudgetItemAction`, `BuildBudgetSummaryAction`, `BuildCategoryBreakdownAction`, `GetBudgetItemsTableAction`, dan `BuildBudgetOverviewWidgetAction`.[file:1][file:2]

## Form Request Validation

### `UpdateBudgetRequest`

```php
public function rules(): array
{
    return [
        'total_budget' => ['nullable', 'integer', 'min:0'],
        'notes' => ['nullable', 'string'],
    ];
}
```

### `StoreBudgetCategoryRequest`

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:100'],
    ];
}
```

### `StoreBudgetItemRequest`

```php
public function rules(): array
{
    return [
        'category_id' => ['required', 'integer', Rule::exists('wedding_budget_categories', 'id')],
        'title' => ['required', 'string', 'max:150'],
        'vendor_name' => ['nullable', 'string', 'max:150'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'planned_amount' => ['nullable', 'integer', 'min:0'],
        'actual_amount' => ['nullable', 'integer', 'min:0'],
        'payment_status' => ['required', Rule::in(['unpaid', 'dp', 'paid'])],
        'payment_date' => ['nullable', 'date'],
        'invitation_id' => ['nullable', 'integer', 'exists:invitations,id'],
    ];
}
```

Validasi tambahan perlu memastikan category/item benar-benar milik budget planner user yang sedang login, sesuai kebutuhan ownership dan keamanan data.[file:1]

## Authorization Policy

Semua akses harus dibatasi pada owner planner.[file:1]
Aturan minimum: user hanya boleh mengakses budget, category, dan item yang parent `budget.user_id` sama dengan auth user.[file:1]

## Search, Filter, Sort Backend

Search minimal harus mencakup `title`, `vendor_name`, dan `notes`, karena UI meminta pencarian item atau vendor/catatan.[file:1][file:2]
Filter minimum: `category_id`, `payment_status`, `has_actual`, `overbudget`, dan default `archived = false`.[file:1][file:2]
Sort minimum: nominal terbesar, nominal terkecil, tanggal terbaru, kategori, dan status pembayaran.[file:1][file:2]

## Resource Contract

### `BudgetSummaryResource`

```json
{
  "total_budget": 150000000,
  "total_planned": 142000000,
  "total_actual": 98000000,
  "remaining_budget": 52000000,
  "planned_vs_actual_gap": 44000000,
  "usage_percentage": 65.33,
  "is_total_overbudget": false,
  "overbudget_amount": 0,
  "overbudget_categories_count": 2,
  "formatted": {
    "total_budget": "Rp 150.000.000",
    "total_planned": "Rp 142.000.000",
    "total_actual": "Rp 98.000.000",
    "remaining_budget": "Rp 52.000.000",
    "planned_vs_actual_gap": "Rp 44.000.000"
  }
}
```

### `BudgetCategoryResource`

```json
{
  "id": 12,
  "name": "Catering",
  "type": "system",
  "is_archived": false,
  "planned_total": 40000000,
  "actual_total": 38000000,
  "remaining": 2000000,
  "usage_percentage": 95,
  "status": "near_limit",
  "status_label": "Mendekati limit",
  "items_count": 4
}
```

### `BudgetItemResource`

```json
{
  "id": 77,
  "title": "DP Catering Akad",
  "category": {
    "id": 12,
    "name": "Catering"
  },
  "vendor_name": "Rasa Bahagia Catering",
  "planned_amount": 15000000,
  "actual_amount": 10000000,
  "formatted_planned_amount": "Rp 15.000.000",
  "formatted_actual_amount": "Rp 10.000.000",
  "actual_amount_display": "Rp 10.000.000",
  "payment_status": "dp",
  "payment_status_label": "DP",
  "payment_date": "2026-04-05",
  "payment_date_label": "5 Apr 2026"
}
```

Jika `actual_amount` null, backend harus mengirim `actual_amount_display: "Belum dicatat"` agar cocok dengan kebutuhan UI mobile dan desktop.[file:1][file:2]

## Formatter Rupiah

Buat helper khusus format Rupiah agar card, form preview, table, badge, dan widget overview konsisten.[file:1][file:2]

```php
final class RupiahFormatter
{
    public static function format(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
```

## State UI yang Harus Didukung Backend

Backend harus menyediakan flag dan data untuk first-time state, empty state tanpa budget, empty search result, overbudget state, dan widget overview state, karena semua state ini disebut eksplisit di dokumen produk dan UI.[file:1][file:2]
Jika `total_budget` null, backend tetap kirim summary planned/actual, tetapi `has_budget = false`, `remaining_budget = null`, dan `usage_percentage = null` agar progress area bisa menampilkan state edukatif.[file:1][file:2]

## Overview Widget Support

Widget overview harus mendukung 3 state utama: belum inisialisasi, planner ada tapi belum ada item, dan planner aktif dengan angka terpakai/total budget, persentase penggunaan, jumlah kategori overbudget, serta CTA “Lihat budget”.[file:1][file:2]
State ini penting karena overview widget menjadi entry point resmi ke Budget Planner dari dashboard utama.[file:1][file:2]

## Soft Delete vs Archive

Spec menyebut user dapat menghapus atau mengarsipkan item, tetapi item archived tidak dihitung di ringkasan aktif.[file:1]
Rekomendasi implementasi: aksi delete pada UI user biasa diterjemahkan menjadi archive (`is_archived = true`), sedangkan `softDeletes()` dipakai sebagai safety net internal dan bukan flow harian user.[file:1][file:2]

## Testing Strategy

Feature test minimum perlu mencakup inisialisasi planner, pembuatan kategori default, update budget, create/update/archive item, kalkulasi summary, kalkulasi category breakdown, search/filter/sort, dan pembatasan akses antar-user.[file:1][file:2]
Unit/service test minimum perlu mencakup `InitializeWeddingBudgetAction`, `BuildBudgetSummaryAction`, `BuildCategoryBreakdownAction`, dan `RupiahFormatter`.[file:1][file:2]

## Struktur Folder Laravel

```text
app/
├── Actions/BudgetPlanner/
├── Enums/
├── Http/Controllers/Dashboard/BudgetPlanner/
├── Http/Requests/BudgetPlanner/
├── Http/Resources/BudgetPlanner/
├── Models/
├── Policies/
└── Support/Formatters/

database/
├── migrations/
└── seeders/
```

Struktur ini menjaga domain Budget Planner tetap modular dan mudah dikembangkan ke fitur premium seperti export report, reminder vendor, dan shared budget di fase berikutnya.[file:1]

## Urutan Implementasi Backend

1. Buat migration dan model untuk tiga tabel utama.[file:1]
2. Buat config/seeder kategori default.[file:1]
3. Implement endpoint initialize planner + onboarding flow backend.[file:1][file:2]
4. Implement summary dan category breakdown builder.[file:1][file:2]
5. Implement CRUD + archive kategori.[file:1][file:2]
6. Implement CRUD + archive item.[file:1][file:2]
7. Implement search, filter, sort, dan pagination item list.[file:1][file:2]
8. Implement payload widget overview dashboard.[file:1][file:2]
9. Tambahkan policy, test, dan handling state kosong/overbudget/null.[file:1][file:2]

## Acceptance Criteria Backend

Backend dianggap selesai jika user bisa membuka Budget Planner, initialize planner, mendapatkan kategori default, mengubah total budget, mengelola item dan kategori, melihat summary dan breakdown yang akurat, memakai search/filter/sort, serta menerima widget overview yang lengkap untuk dashboard utama.[file:1][file:2]
Selain itu, seluruh akses harus aman berdasarkan ownership, data harus konsisten saat login kembali, dan kontrak response harus cukup untuk merender UI yang hangat, ringan, dan mobile-friendly sesuai positioning TheDay.[file:1][file:2]
