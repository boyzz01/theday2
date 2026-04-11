<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    budget:            Object,
    summary:           Object,
    categoryBreakdown: Array,
    items:             Array,
    categories:        Array,
    filters:           Object,
});

// ─── Local state ──────────────────────────────────────────────────────────────

const activeView      = ref('category'); // 'category' | 'item'
const searchQuery     = ref(props.filters?.search ?? '');
const filterStatus    = ref(props.filters?.payment_status ?? 'all');
const filterCategory  = ref(props.filters?.category_id ?? '');
const sortBy          = ref(props.filters?.sort ?? 'newest');
const showFilterSheet = ref(false);
const showAddItem     = ref(false);
const showEditItem    = ref(false);
const showManageCats  = ref(false);
const showSetBudget   = ref(false);
const showConfirmArchive  = ref(false);
const showAddCategory = ref(false);

const editingItem     = ref(null);
const archivingItem   = ref(null);
const toast           = ref(null);
const toastTimer      = ref(null);

// ─── Form state ───────────────────────────────────────────────────────────────

const budgetForm = ref({
    total_budget: props.budget?.total_budget ?? '',
    notes:        props.budget?.notes ?? '',
});

const itemForm = ref({
    title:          '',
    category_id:    '',
    vendor_name:    '',
    planned_amount: '',
    actual_amount:  '',
    payment_status: 'unpaid',
    payment_date:   '',
    notes:          '',
});

const itemErrors = ref({});
const categoryForm = ref({ name: '' });

// ─── Computed ─────────────────────────────────────────────────────────────────

const hasBudget = computed(() => props.summary?.has_budget);

const progressPct = computed(() => {
    if (!hasBudget.value) return 0;
    return props.summary?.usage_percentage ?? 0;
});

const progressColor = computed(() => {
    if (props.summary?.is_total_overbudget) return '#F87171';
    if ((progressPct.value ?? 0) >= 80) return '#F59E0B';
    return '#D4A373';
});

const statusConfig = {
    normal:      { label: 'Normal',          bg: 'bg-emerald-100', text: 'text-emerald-700', bar: '#34D399' },
    near_limit:  { label: 'Mendekati limit', bg: 'bg-amber-100',   text: 'text-amber-700',   bar: '#F59E0B' },
    overbudget:  { label: 'Overbudget',      bg: 'bg-rose-100',    text: 'text-rose-700',     bar: '#F87171' },
};

const paymentStatusOptions = [
    { value: 'unpaid', label: 'Belum bayar' },
    { value: 'dp',     label: 'DP' },
    { value: 'paid',   label: 'Lunas' },
];

const paymentBadge = {
    unpaid: 'bg-stone-100 text-stone-600',
    dp:     'bg-amber-100 text-amber-700',
    paid:   'bg-emerald-100 text-emerald-700',
};

const paymentLabel = {
    unpaid: 'Belum bayar',
    dp:     'DP',
    paid:   'Lunas',
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatRupiah(val) {
    if (val === null || val === undefined || val === '') return '';
    const n = parseInt(String(val).replace(/\D/g, ''), 10) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}

function parseRupiah(val) {
    if (!val) return null;
    const n = parseInt(String(val).replace(/\D/g, ''), 10);
    return isNaN(n) ? null : n;
}

function showToast(message, type = 'success') {
    clearTimeout(toastTimer.value);
    toast.value = { message, type };
    toastTimer.value = setTimeout(() => { toast.value = null; }, 4000);
}

// ─── Search/filter reload ─────────────────────────────────────────────────────

let searchDebounce = null;
watch(searchQuery, (val) => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => reloadItems(), 400);
});

function applyFilters() {
    showFilterSheet.value = false;
    reloadItems();
}

function reloadItems() {
    router.get(
        route('dashboard.budget-planner.index'),
        {
            search:          searchQuery.value || undefined,
            category_id:     filterCategory.value || undefined,
            payment_status:  filterStatus.value !== 'all' ? filterStatus.value : undefined,
            sort:            sortBy.value !== 'newest' ? sortBy.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

function clearFilters() {
    searchQuery.value   = '';
    filterStatus.value  = 'all';
    filterCategory.value = '';
    sortBy.value        = 'newest';
    showFilterSheet.value = false;
    reloadItems();
}

// ─── Budget update ────────────────────────────────────────────────────────────

async function saveBudget() {
    const raw = parseRupiah(budgetForm.value.total_budget);
    try {
        await window.axios.patch(route('dashboard.budget-planner.budget.update'), {
            total_budget: raw,
            notes:        budgetForm.value.notes,
        });
        showBudget.value = false;
        showSetBudget.value = false;
        showToast('Total budget berhasil disimpan.');
        router.reload({ preserveScroll: true });
    } catch {
        showToast('Data belum bisa disimpan. Coba lagi sebentar.', 'error');
    }
}

// ─── Item CRUD ────────────────────────────────────────────────────────────────

function openAddItem() {
    itemForm.value  = { title: '', category_id: '', vendor_name: '', planned_amount: '', actual_amount: '', payment_status: 'unpaid', payment_date: '', notes: '' };
    itemErrors.value = {};
    editingItem.value = null;
    showAddItem.value = true;
}

function openEditItem(item) {
    editingItem.value = item;
    itemForm.value = {
        title:          item.title,
        category_id:    item.category?.id ?? '',
        vendor_name:    item.vendor_name ?? '',
        planned_amount: item.planned_amount ?? '',
        actual_amount:  item.actual_amount ?? '',
        payment_status: item.payment_status,
        payment_date:   item.payment_date ?? '',
        notes:          item.notes ?? '',
    };
    itemErrors.value = {};
    showEditItem.value = true;
}

async function saveItem() {
    itemErrors.value = {};
    const payload = {
        ...itemForm.value,
        planned_amount: parseRupiah(itemForm.value.planned_amount) ?? 0,
        actual_amount:  itemForm.value.actual_amount !== '' ? parseRupiah(itemForm.value.actual_amount) : null,
        payment_date:   itemForm.value.payment_date || null,
        vendor_name:    itemForm.value.vendor_name || null,
        notes:          itemForm.value.notes || null,
    };

    try {
        if (editingItem.value) {
            await window.axios.patch(route('dashboard.budget-planner.items.update', editingItem.value.id), payload);
            showToast('Pengeluaran berhasil diperbarui.');
            showEditItem.value = false;
        } else {
            await window.axios.post(route('dashboard.budget-planner.items.store'), payload);
            showToast('Pengeluaran berhasil disimpan.');
            showAddItem.value = false;
        }
        router.reload({ preserveScroll: true });
    } catch (err) {
        if (err.response?.status === 422) {
            itemErrors.value = err.response.data.errors ?? {};
        } else {
            showToast('Data belum bisa disimpan. Coba lagi sebentar.', 'error');
        }
    }
}

function confirmArchiveItem(item) {
    archivingItem.value = item;
    showConfirmArchive.value = true;
}

async function archiveItem() {
    if (!archivingItem.value) return;
    try {
        await window.axios.delete(route('dashboard.budget-planner.items.destroy', archivingItem.value.id));
        showConfirmArchive.value = false;
        archivingItem.value = null;
        showToast('Pengeluaran berhasil diarsipkan.');
        router.reload({ preserveScroll: true });
    } catch {
        showToast('Gagal mengarsipkan. Coba lagi.', 'error');
    }
}

// ─── Category management ──────────────────────────────────────────────────────

async function addCategory() {
    if (!categoryForm.value.name.trim()) return;
    try {
        await window.axios.post(route('dashboard.budget-planner.categories.store'), { name: categoryForm.value.name });
        categoryForm.value.name = '';
        showToast('Kategori berhasil ditambahkan.');
        router.reload({ preserveScroll: true });
    } catch {
        showToast('Gagal menambah kategori.', 'error');
    }
}

async function archiveCategory(cat) {
    try {
        await window.axios.delete(route('dashboard.budget-planner.categories.destroy', cat.id));
        showToast('Kategori berhasil diarsipkan.');
        router.reload({ preserveScroll: true });
    } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal mengarsipkan kategori.';
        showToast(msg, 'error');
    }
}

// ─── Budget display toggle ─────────────────────────────────────────────────────

const showBudget = ref(false);

function openSetBudget() {
    budgetForm.value.total_budget = props.budget?.total_budget ?? '';
    budgetForm.value.notes = props.budget?.notes ?? '';
    showSetBudget.value = true;
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-base font-semibold text-stone-800 truncate">Budget Planner</h1>
        </template>

        <div class="max-w-5xl mx-auto pb-24">

            <!-- ── Toast ──────────────────────────────────────────────────── -->
            <Transition name="slide-down">
                <div
                    v-if="toast"
                    :class="[
                        'fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-3 rounded-xl shadow-lg text-sm font-medium',
                        toast.type === 'error'
                            ? 'bg-rose-50 text-rose-700 border border-rose-100'
                            : 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                    ]"
                    role="alert"
                    aria-live="polite"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path v-if="toast.type !== 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    {{ toast.message }}
                </div>
            </Transition>

            <!-- ── Page Header ─────────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                        Budget Planner
                    </h2>
                    <p class="text-sm text-stone-500 mt-0.5">Pantau rencana dan realisasi budget pernikahanmu.</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button
                        @click="showManageCats = true"
                        class="flex items-center gap-1.5 px-3 py-2 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/>
                        </svg>
                        Kelola Kategori
                    </button>
                    <button
                        @click="openAddItem"
                        class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-xl transition-opacity hover:opacity-90"
                        style="background-color: #D4A373"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Item
                    </button>
                </div>
            </div>

            <!-- ── Summary Cards ───────────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <!-- Total Budget -->
                <div class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm">
                    <p class="text-xs text-stone-400 font-medium mb-1">Total Budget</p>
                    <p class="text-lg font-bold text-stone-800 leading-tight">
                        {{ summary.has_budget ? summary.formatted.total_budget : '—' }}
                    </p>
                    <button
                        @click="openSetBudget"
                        class="mt-1.5 text-xs font-medium transition-colors"
                        style="color: #D4A373"
                    >
                        {{ summary.has_budget ? 'Ubah' : 'Atur budget' }}
                    </button>
                </div>

                <!-- Total Planned -->
                <div class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm">
                    <p class="text-xs text-stone-400 font-medium mb-1">Total Planned</p>
                    <p class="text-lg font-bold text-stone-800 leading-tight">
                        {{ summary.formatted.total_planned }}
                    </p>
                    <p class="mt-1.5 text-xs text-stone-400">Dari semua item</p>
                </div>

                <!-- Total Terpakai -->
                <div class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm">
                    <p class="text-xs text-stone-400 font-medium mb-1">Terpakai</p>
                    <p
                        class="text-lg font-bold leading-tight"
                        :class="summary.is_total_overbudget ? 'text-rose-600' : 'text-stone-800'"
                    >
                        {{ summary.formatted.total_actual }}
                    </p>
                    <p v-if="summary.is_total_overbudget" class="mt-1.5 text-xs text-rose-500 font-medium">
                        +{{ summary.formatted.overbudget_amount }} melebihi budget
                    </p>
                    <p v-else class="mt-1.5 text-xs text-stone-400">Sudah dicatat</p>
                </div>

                <!-- Sisa Budget -->
                <div class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm">
                    <p class="text-xs text-stone-400 font-medium mb-1">Sisa Budget</p>
                    <p
                        class="text-lg font-bold leading-tight"
                        :class="summary.remaining_budget < 0 ? 'text-rose-600' : 'text-stone-800'"
                    >
                        {{ summary.has_budget ? summary.formatted.remaining_budget : '—' }}
                    </p>
                    <p v-if="summary.overbudget_categories_count > 0" class="mt-1.5 text-xs text-amber-600">
                        {{ summary.overbudget_categories_count }} kategori overbudget
                    </p>
                    <p v-else class="mt-1.5 text-xs text-stone-400">
                        {{ summary.has_budget ? 'Dana tersisa' : 'Atur budget dulu' }}
                    </p>
                </div>
            </div>

            <!-- ── Progress Bar ────────────────────────────────────────────── -->
            <div class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm mb-4">
                <div v-if="hasBudget">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-stone-700">Budget terpakai</p>
                        <div class="flex items-center gap-2">
                            <span v-if="summary.is_total_overbudget"
                                  class="text-xs font-semibold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">
                                Overbudget
                            </span>
                            <span class="text-sm font-bold text-stone-800">{{ progressPct }}%</span>
                        </div>
                    </div>
                    <div class="h-3 bg-stone-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="{ width: progressPct + '%', backgroundColor: progressColor }"
                        />
                    </div>
                    <p class="text-xs text-stone-400 mt-1.5">
                        {{ summary.formatted.total_actual }} dari {{ summary.formatted.total_budget }} sudah digunakan
                    </p>
                </div>
                <div v-else class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-stone-600">Belum ada total budget yang diatur</p>
                        <p class="text-xs text-stone-400 mt-0.5">Fitur tetap bisa dipakai, tapi ringkasan lebih akurat dengan total budget.</p>
                    </div>
                    <button
                        @click="openSetBudget"
                        class="flex-shrink-0 text-sm font-medium px-3 py-1.5 rounded-xl border transition-colors"
                        style="color: #D4A373; border-color: #D4A373"
                    >
                        Atur budget
                    </button>
                </div>
            </div>

            <!-- ── View Toggle ─────────────────────────────────────────────── -->
            <div class="flex items-center gap-1 bg-stone-100 rounded-xl p-1 mb-4 w-fit">
                <button
                    @click="activeView = 'category'"
                    :class="[
                        'px-4 py-1.5 text-sm font-medium rounded-lg transition-all',
                        activeView === 'category'
                            ? 'bg-white text-stone-800 shadow-sm'
                            : 'text-stone-500 hover:text-stone-700',
                    ]"
                >
                    Kategori
                </button>
                <button
                    @click="activeView = 'item'"
                    :class="[
                        'px-4 py-1.5 text-sm font-medium rounded-lg transition-all',
                        activeView === 'item'
                            ? 'bg-white text-stone-800 shadow-sm'
                            : 'text-stone-500 hover:text-stone-700',
                    ]"
                >
                    Daftar Item
                </button>
            </div>

            <!-- ─────────────────── CATEGORY VIEW ─────────────────────────── -->
            <template v-if="activeView === 'category'">
                <div v-if="categoryBreakdown.length === 0" class="bg-white border border-stone-100 rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-12 h-12 bg-stone-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-stone-600">Kategori default sudah siap</p>
                    <p class="text-xs text-stone-400 mt-1">Mulai tambah pengeluaran untuk melihat breakdown kategori.</p>
                    <button @click="openAddItem" class="mt-3 text-sm font-medium" style="color: #D4A373">
                        Tambah pengeluaran pertama →
                    </button>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="cat in categoryBreakdown"
                        :key="cat.id"
                        class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-sm font-semibold text-stone-800 truncate">{{ cat.name }}</h3>
                                    <span
                                        :class="['text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0', statusConfig[cat.status]?.bg, statusConfig[cat.status]?.text]"
                                    >
                                        {{ cat.status_label }}
                                    </span>
                                </div>

                                <!-- Progress bar -->
                                <div class="h-2 bg-stone-100 rounded-full overflow-hidden mb-2">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{ width: cat.usage_percentage + '%', backgroundColor: statusConfig[cat.status]?.bar }"
                                    />
                                </div>

                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div>
                                        <p class="text-stone-400">Planned</p>
                                        <p class="font-semibold text-stone-700">{{ cat.formatted.planned_total }}</p>
                                    </div>
                                    <div>
                                        <p class="text-stone-400">Terpakai</p>
                                        <p class="font-semibold" :class="cat.status === 'overbudget' ? 'text-rose-600' : 'text-stone-700'">
                                            {{ cat.formatted.actual_total }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-stone-400">Sisa</p>
                                        <p class="font-semibold" :class="cat.remaining < 0 ? 'text-rose-600' : 'text-stone-700'">
                                            {{ cat.formatted.remaining }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button
                                @click="filterCategory = cat.id; activeView = 'item'"
                                class="flex-shrink-0 text-xs font-medium text-stone-500 border border-stone-200 px-2.5 py-1.5 rounded-lg hover:bg-stone-50 transition-colors"
                            >
                                Lihat ({{ cat.items_count }})
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ─────────────────── ITEM LIST VIEW ────────────────────────── -->
            <template v-else>
                <!-- Search + Filter row -->
                <div class="flex gap-2 mb-3">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Cari item atau vendor"
                            class="w-full pl-9 pr-3 py-2.5 text-sm bg-white border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                            style="--tw-ring-color: #D4A373"
                        />
                    </div>
                    <button
                        @click="showFilterSheet = true"
                        :class="[
                            'flex items-center gap-1.5 px-3 py-2.5 text-sm border rounded-xl transition-colors',
                            (filterStatus !== 'all' || filterCategory || sortBy !== 'newest')
                                ? 'bg-amber-50 border-amber-200 text-amber-700 font-medium'
                                : 'bg-white border-stone-200 text-stone-600 hover:bg-stone-50',
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Filter
                    </button>
                </div>

                <!-- Empty item state -->
                <div v-if="items.length === 0" class="bg-white border border-stone-100 rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-12 h-12 bg-stone-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <template v-if="searchQuery || filterStatus !== 'all' || filterCategory">
                        <p class="text-sm font-medium text-stone-600">Belum ada hasil yang cocok.</p>
                        <p class="text-xs text-stone-400 mt-1">Coba kata kunci atau filter lain.</p>
                        <button @click="clearFilters" class="mt-3 text-sm font-medium" style="color: #D4A373">
                            Reset filter →
                        </button>
                    </template>
                    <template v-else>
                        <p class="text-sm font-medium text-stone-600">Belum ada pengeluaran tercatat.</p>
                        <p class="text-xs text-stone-400 mt-1">Mulai catat pengeluaran pertamamu agar budget pernikahan tetap terkontrol.</p>
                        <button @click="openAddItem" class="mt-3 text-sm font-medium" style="color: #D4A373">
                            Tambah pengeluaran →
                        </button>
                    </template>
                </div>

                <!-- Item Cards -->
                <div v-else class="space-y-2">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-sm font-semibold text-stone-800 truncate">{{ item.title }}</p>
                                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0', paymentBadge[item.payment_status]]">
                                        {{ paymentLabel[item.payment_status] }}
                                    </span>
                                </div>
                                <p class="text-xs text-stone-400 mb-2">
                                    {{ item.category?.name }}
                                    <span v-if="item.vendor_name"> · {{ item.vendor_name }}</span>
                                </p>
                                <div class="flex items-center gap-4 text-xs">
                                    <div>
                                        <span class="text-stone-400">Planned: </span>
                                        <span class="font-semibold text-stone-700">{{ item.formatted.planned_amount }}</span>
                                    </div>
                                    <div>
                                        <span class="text-stone-400">Actual: </span>
                                        <span
                                            :class="['font-semibold', item.actual_amount === null ? 'text-stone-400 italic' : 'text-stone-700']"
                                        >
                                            {{ item.formatted.actual_amount_display }}
                                        </span>
                                    </div>
                                </div>
                                <p v-if="item.payment_date_label" class="text-xs text-stone-400 mt-1">
                                    📅 {{ item.payment_date_label }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button
                                    @click="openEditItem(item)"
                                    class="p-2 text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                    title="Edit"
                                    aria-label="Edit item"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button
                                    @click="confirmArchiveItem(item)"
                                    class="p-2 text-stone-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
                                    title="Arsipkan"
                                    aria-label="Arsipkan item"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- ── Floating CTA (mobile) ───────────────────────────────────────── -->
        <div class="fixed bottom-6 right-4 lg:hidden z-20">
            <button
                @click="openAddItem"
                class="flex items-center gap-2 px-5 py-3 text-white text-sm font-semibold rounded-2xl shadow-lg transition-opacity hover:opacity-90"
                style="background-color: #D4A373"
                aria-label="Tambah item pengeluaran"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Item
            </button>
        </div>

        <!-- ════════════════ MODALS / SHEETS ═════════════════════════════════ -->

        <!-- ── Set Budget Modal ────────────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="showSetBudget" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showSetBudget = false"/>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h3 class="text-base font-semibold text-stone-800 mb-4">Atur Total Budget</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Total Budget</label>
                            <input
                                :value="formatRupiah(budgetForm.total_budget)"
                                @input="budgetForm.total_budget = $event.target.value.replace(/\D/g, '')"
                                type="text"
                                inputmode="numeric"
                                placeholder="Rp 0"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Catatan (opsional)</label>
                            <textarea
                                v-model="budgetForm.notes"
                                rows="2"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                                style="--tw-ring-color: #D4A373"
                            />
                        </div>
                    </div>

                    <div class="flex gap-2 mt-5">
                        <button @click="showSetBudget = false" class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">
                            Batal
                        </button>
                        <button @click="saveBudget" class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90" style="background-color: #D4A373">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Add/Edit Item Modal ─────────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="showAddItem || showEditItem" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showAddItem = showEditItem = false"/>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
                        <h3 class="text-base font-semibold text-stone-800">
                            {{ editingItem ? 'Edit Pengeluaran' : 'Tambah Pengeluaran' }}
                        </h3>
                        <button @click="showAddItem = showEditItem = false" class="p-1 text-stone-400 hover:text-stone-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Form body -->
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                        <!-- Nama item -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">
                                Nama Item <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="itemForm.title"
                                type="text"
                                placeholder="Contoh: Gedung Akad"
                                class="w-full px-3 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                :class="itemErrors.title ? 'border-rose-300' : 'border-stone-200'"
                                style="--tw-ring-color: #D4A373"
                            />
                            <p v-if="itemErrors.title" class="mt-1 text-xs text-rose-500">{{ itemErrors.title[0] }}</p>
                        </div>

                        <!-- Kategori -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">
                                Kategori <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="itemForm.category_id"
                                class="w-full px-3 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white"
                                :class="itemErrors.category_id ? 'border-rose-300' : 'border-stone-200'"
                                style="--tw-ring-color: #D4A373"
                            >
                                <option value="" disabled>Pilih kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="itemErrors.category_id" class="mt-1 text-xs text-rose-500">{{ itemErrors.category_id[0] }}</p>
                        </div>

                        <!-- Planned amount -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Planned Amount</label>
                            <input
                                :value="itemForm.planned_amount ? formatRupiah(itemForm.planned_amount) : ''"
                                @input="itemForm.planned_amount = $event.target.value.replace(/\D/g, '')"
                                type="text"
                                inputmode="numeric"
                                placeholder="Rp 0"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373"
                            />
                        </div>

                        <!-- Actual amount -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Actual Amount</label>
                            <input
                                :value="itemForm.actual_amount !== '' ? formatRupiah(itemForm.actual_amount) : ''"
                                @input="itemForm.actual_amount = $event.target.value.replace(/\D/g, '')"
                                type="text"
                                inputmode="numeric"
                                placeholder="Belum ada"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373"
                            />
                            <p class="mt-1 text-xs text-stone-400">Biarkan kosong jika belum ada pembayaran.</p>
                        </div>

                        <!-- Status pembayaran -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Status Pembayaran</label>
                            <div class="flex gap-2">
                                <button
                                    v-for="opt in paymentStatusOptions"
                                    :key="opt.value"
                                    @click="itemForm.payment_status = opt.value"
                                    :class="[
                                        'flex-1 py-2 text-xs font-medium rounded-xl border transition-colors',
                                        itemForm.payment_status === opt.value
                                            ? 'border-transparent text-white'
                                            : 'border-stone-200 text-stone-600 hover:bg-stone-50',
                                    ]"
                                    :style="itemForm.payment_status === opt.value ? 'background-color: #D4A373' : ''"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Tanggal -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Tanggal Pembayaran</label>
                            <input
                                v-model="itemForm.payment_date"
                                type="date"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373"
                            />
                        </div>

                        <!-- Vendor -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Vendor / Catatan</label>
                            <input
                                v-model="itemForm.vendor_name"
                                type="text"
                                placeholder="Nama vendor atau catatan singkat"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373"
                            />
                        </div>
                    </div>

                    <!-- Sticky CTA -->
                    <div class="px-5 py-4 border-t border-stone-100 flex gap-2">
                        <button @click="showAddItem = showEditItem = false" class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">
                            Batal
                        </button>
                        <button @click="saveItem" class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90" style="background-color: #D4A373">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Filter Sheet ────────────────────────────────────────────────── -->
        <Transition name="slide-up">
            <div v-if="showFilterSheet" class="fixed inset-0 z-50 flex items-end justify-center">
                <div class="absolute inset-0 bg-black/40" @click="showFilterSheet = false"/>
                <div class="relative bg-white rounded-t-3xl shadow-xl w-full max-w-lg p-5 pb-8">
                    <div class="w-10 h-1 bg-stone-200 rounded-full mx-auto mb-4"/>
                    <h3 class="text-base font-semibold text-stone-800 mb-4">Filter & Urutkan</h3>

                    <!-- Filter status -->
                    <div class="mb-4">
                        <p class="text-xs font-medium text-stone-500 mb-2">Status Pembayaran</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="opt in [{ value: 'all', label: 'Semua' }, ...paymentStatusOptions]"
                                :key="opt.value"
                                @click="filterStatus = opt.value"
                                :class="[
                                    'px-3 py-1.5 text-xs font-medium rounded-xl border transition-colors',
                                    filterStatus === opt.value
                                        ? 'border-transparent text-white'
                                        : 'border-stone-200 text-stone-600 hover:bg-stone-50',
                                ]"
                                :style="filterStatus === opt.value ? 'background-color: #D4A373' : ''"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Filter kategori -->
                    <div class="mb-4">
                        <p class="text-xs font-medium text-stone-500 mb-2">Kategori</p>
                        <select v-model="filterCategory" class="w-full px-3 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none bg-white">
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div class="mb-5">
                        <p class="text-xs font-medium text-stone-500 mb-2">Urutkan</p>
                        <select v-model="sortBy" class="w-full px-3 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none bg-white">
                            <option value="newest">Terbaru</option>
                            <option value="amount_desc">Nominal terbesar</option>
                            <option value="amount_asc">Nominal terkecil</option>
                            <option value="date_desc">Tanggal terbaru</option>
                            <option value="category">Kategori</option>
                            <option value="payment_status">Status pembayaran</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button @click="clearFilters" class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">
                            Reset
                        </button>
                        <button @click="applyFilters" class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90" style="background-color: #D4A373">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Manage Categories Modal ─────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="showManageCats" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showManageCats = false"/>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[80vh] flex flex-col">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
                        <h3 class="text-base font-semibold text-stone-800">Kelola Kategori</h3>
                        <button @click="showManageCats = false" class="p-1 text-stone-400 hover:text-stone-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <!-- Add custom category -->
                        <div class="flex gap-2 mb-4">
                            <input
                                v-model="categoryForm.name"
                                type="text"
                                placeholder="Nama kategori baru"
                                @keyup.enter="addCategory"
                                class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373"
                            />
                            <button @click="addCategory" class="px-4 py-2 text-sm font-medium text-white rounded-xl transition-opacity hover:opacity-90" style="background-color: #D4A373">
                                Tambah
                            </button>
                        </div>

                        <!-- Category list -->
                        <div class="space-y-2">
                            <div
                                v-for="cat in categoryBreakdown"
                                :key="cat.id"
                                class="flex items-center justify-between py-2.5 px-3 bg-stone-50 rounded-xl"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-stone-700 font-medium">{{ cat.name }}</span>
                                    <span v-if="cat.type === 'custom'" class="text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">Custom</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-stone-400">{{ cat.items_count }} item</span>
                                    <button
                                        v-if="cat.type === 'custom'"
                                        @click="archiveCategory(cat)"
                                        class="p-1.5 text-stone-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
                                        :title="`Arsipkan ${cat.name}`"
                                        :aria-label="`Arsipkan ${cat.name}`"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-stone-400 mt-3">
                            Kategori default tidak bisa diarsipkan. Kategori custom bisa diarsipkan jika tidak memiliki item aktif.
                        </p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Confirm Archive Dialog ──────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="showConfirmArchive" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showConfirmArchive = false"/>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
                    <div class="w-12 h-12 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-stone-800 mb-1">Arsipkan item ini?</h3>
                    <p class="text-sm text-stone-500">
                        "<strong>{{ archivingItem?.title }}</strong>" tidak akan dihitung dalam ringkasan aktif.
                    </p>
                    <div class="flex gap-2 mt-5">
                        <button @click="showConfirmArchive = false; archivingItem = null" class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">
                            Batal
                        </button>
                        <button @click="archiveItem" class="flex-1 py-2.5 text-sm font-semibold text-white bg-rose-500 rounded-xl hover:bg-rose-600 transition-colors">
                            Arsipkan
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </DashboardLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-10px); }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(100%); }

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
