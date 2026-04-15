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

// ─── View state ───────────────────────────────────────────────────────────────

const activeView       = ref('category');
const expandedCats     = ref(new Set());
const searchQuery      = ref(props.filters?.search ?? '');
const filterStatus     = ref(props.filters?.payment_status ?? 'all');
const filterCategory   = ref(props.filters?.category_id ?? '');
const sortBy           = ref(props.filters?.sort ?? 'newest');
const showFilterSheet  = ref(false);
const showAddItem      = ref(false);
const showEditItem     = ref(false);
const showManageCats   = ref(false);
const showSetBudget    = ref(false);
const showConfirmArchive = ref(false);
const selectedCatId    = ref(null); // pre-select category for add modal

const editingItem   = ref(null);
const archivingItem = ref(null);
const toast         = ref(null);
let toastTimer      = null;

// ─── Form state ───────────────────────────────────────────────────────────────

const budgetForm = ref({
    total_budget: props.budget?.total_budget ?? '',
    notes:        props.budget?.notes ?? '',
});

const blankItemForm = () => ({
    title:          '',
    category_id:    '',
    vendor_name:    '',
    planned_amount: '',
    actual_amount:  '',
    dp_amount:      '',
    dp_paid:        false,
    final_amount:   '',
    final_paid:     false,
    due_date:       '',
    payment_status: 'unpaid',
    payment_date:   '',
    notes:          '',
    use_dp_tracking: false,
});

const itemForm   = ref(blankItemForm());
const itemErrors = ref({});
const categoryForm = ref({ name: '' });

// ─── Category colors (brand palette) ─────────────────────────────────────────

const CATEGORY_COLORS = {
    'Venue':           '#C8A26B',
    'Catering':        '#B5C4A8',
    'Dekorasi':        '#D4A5A5',
    'Busana':          '#A8B8C4',
    'Dokumentasi':     '#C4B8A8',
    'Undangan':        '#B8C4A8',
    'Hiburan':         '#D4B8A8',
    'Transportasi':    '#A8C4B8',
    'Perhiasan':       '#C8B8A8',
    'Lainnya':         '#D4C4A8',
    'Makeup & Beauty': '#E8C4B8',
    'Souvenir':        '#B8D4C8',
    'Administrasi':    '#C8C4B8',
};

const FALLBACK_COLORS = [
    '#C8A26B','#B5C4A8','#D4A5A5','#A8B8C4',
    '#C4B8A8','#B8C4A8','#D4B8A8','#A8C4B8',
];

function catColor(cat, idx) {
    return cat.color || CATEGORY_COLORS[cat.name] || FALLBACK_COLORS[idx % FALLBACK_COLORS.length];
}

// ─── Status config ────────────────────────────────────────────────────────────

const statusConfig = {
    aman:      { label: 'Aman',           bg: 'bg-emerald-100', text: 'text-emerald-700', dot: '#4CAF50', bar: '#34D399' },
    mendekati: { label: 'Mendekati',      bg: 'bg-amber-100',   text: 'text-amber-700',   dot: '#F59E0B', bar: '#F59E0B' },
    melebihi:  { label: 'Melebihi',       bg: 'bg-rose-100',    text: 'text-rose-700',    dot: '#EF4444', bar: '#F87171' },
    no_data:   { label: 'Belum ada data', bg: 'bg-stone-100',   text: 'text-stone-500',   dot: '#9CA3AF', bar: '#D1D5DB' },
};

function statusCfg(status) {
    return statusConfig[status] ?? statusConfig.no_data;
}

// ─── Donut chart ──────────────────────────────────────────────────────────────

const selectedSlice = ref(null);

const donutSlices = computed(() => {
    const breakdown = props.categoryBreakdown ?? [];
    const hasBudget = props.summary?.has_budget;
    const useActual = hasBudget; // use terpakai when budget is set, else planned

    const total = breakdown.reduce((s, c) => s + (useActual ? c.actual_total : c.planned_total), 0);
    if (total === 0) return [];

    // Limit to 8 slices, group rest as "Lainnya"
    const sorted = [...breakdown].sort((a, b) => {
        const av = useActual ? a.actual_total : a.planned_total;
        const bv = useActual ? b.actual_total : b.planned_total;
        return bv - av;
    });

    let slices = [];
    let othersTotal = 0;
    sorted.forEach((cat, i) => {
        const val = useActual ? cat.actual_total : cat.planned_total;
        if (val === 0) return;
        if (i < 7) {
            slices.push({ id: cat.id, name: cat.name, value: val, color: catColor(cat, i) });
        } else {
            othersTotal += val;
        }
    });
    if (othersTotal > 0) {
        slices.push({ id: '__others__', name: 'Lainnya', value: othersTotal, color: '#D4C4A8' });
    }

    let cum = 0;
    return slices.map(s => {
        const pct = (s.value / total) * 100;
        const result = { ...s, pct, offset: cum, total };
        cum += pct;
        return result;
    });
});

const donutCenterLabel = computed(() => {
    const s = props.summary;
    if (!s) return { primary: 'Budget', amount: 'Rp 0', secondary: null };

    if (selectedSlice.value) {
        const sl = donutSlices.value.find(x => x.id === selectedSlice.value);
        if (sl) {
            return {
                primary:   sl.name,
                amount:    formatRupiah(sl.value),
                secondary: (sl.pct).toFixed(1) + '%',
            };
        }
    }

    if (s.has_budget) {
        return {
            primary:   'Terpakai',
            amount:    s.formatted.total_actual,
            secondary: `dari ${s.formatted.total_budget}`,
            overbudget: s.is_total_overbudget,
        };
    }
    return {
        primary:   'Total Planned',
        amount:    s.formatted.total_planned,
        secondary: null,
    };
});

function selectSlice(id) {
    selectedSlice.value = selectedSlice.value === id ? null : id;
}

// ─── Computed ─────────────────────────────────────────────────────────────────

const hasBudget      = computed(() => props.summary?.has_budget);
const isFirstTime    = computed(() =>
    !props.summary?.has_budget &&
    (props.items ?? []).length === 0 &&
    (props.categoryBreakdown ?? []).every(c => c.items_count === 0)
);

const progressPct   = computed(() => props.summary?.usage_percentage ?? 0);
const progressColor = computed(() => {
    if (props.summary?.is_total_overbudget) return '#F87171';
    if ((progressPct.value ?? 0) >= 80) return '#F59E0B';
    return '#D4A373';
});

const paymentStatusOptions = [
    { value: 'unpaid', label: 'Belum Bayar' },
    { value: 'dp',     label: 'DP Terbayar' },
    { value: 'paid',   label: 'Lunas' },
];

const paymentBadge = {
    unpaid: 'bg-stone-100 text-stone-600',
    dp:     'bg-amber-100 text-amber-700',
    paid:   'bg-emerald-100 text-emerald-700',
};

const paymentLabel = {
    unpaid: 'Belum Bayar',
    dp:     'DP Terbayar',
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

function compactAmount(val) {
    if (val === null || val === undefined) return '—';
    if (val >= 1_000_000_000) return 'Rp ' + (val / 1_000_000_000).toFixed(1).replace('.0','') + 'M';
    if (val >= 1_000_000)     return 'Rp ' + (val / 1_000_000).toFixed(1).replace('.0','') + 'jt';
    if (val >= 1_000)         return 'Rp ' + (val / 1_000).toFixed(0) + 'rb';
    return 'Rp ' + val;
}

function showToast(message, type = 'success') {
    clearTimeout(toastTimer);
    toast.value = { message, type };
    toastTimer = setTimeout(() => { toast.value = null; }, 4000);
}

// ─── Category expand ──────────────────────────────────────────────────────────

function toggleCat(id) {
    const s = new Set(expandedCats.value);
    s.has(id) ? s.delete(id) : s.add(id);
    expandedCats.value = s;
}

// ─── Search / filter reload ───────────────────────────────────────────────────

let searchDebounce = null;
watch(searchQuery, () => {
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
            search:         searchQuery.value || undefined,
            category_id:    filterCategory.value || undefined,
            payment_status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
            sort:           sortBy.value !== 'newest' ? sortBy.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

function clearFilters() {
    searchQuery.value    = '';
    filterStatus.value   = 'all';
    filterCategory.value = '';
    sortBy.value         = 'newest';
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
        showSetBudget.value = false;
        showToast('Total budget berhasil disimpan.');
        router.reload({ preserveScroll: true });
    } catch {
        showToast('Data belum bisa disimpan. Coba lagi.', 'error');
    }
}

function openSetBudget() {
    budgetForm.value.total_budget = props.budget?.total_budget ?? '';
    budgetForm.value.notes        = props.budget?.notes ?? '';
    showSetBudget.value           = true;
}

// ─── Item CRUD ────────────────────────────────────────────────────────────────

function openAddItem(catId = null) {
    const form = blankItemForm();
    if (catId) form.category_id = catId;
    itemForm.value   = form;
    itemErrors.value = {};
    editingItem.value = null;
    showAddItem.value  = true;
}

function openEditItem(item) {
    editingItem.value = item;
    itemForm.value = {
        title:           item.title,
        category_id:     item.category?.id ?? '',
        vendor_name:     item.vendor_name ?? '',
        planned_amount:  item.planned_amount ?? '',
        actual_amount:   item.actual_amount ?? '',
        dp_amount:       item.dp_amount ?? '',
        dp_paid:         item.dp_paid ?? false,
        final_amount:    item.final_amount ?? '',
        final_paid:      item.final_paid ?? false,
        due_date:        item.due_date ?? '',
        payment_status:  item.payment_status ?? 'unpaid',
        payment_date:    item.payment_date ?? '',
        notes:           item.notes ?? '',
        use_dp_tracking: !!(item.dp_amount || item.final_amount),
    };
    itemErrors.value = {};
    showEditItem.value = true;
}

async function saveItem() {
    itemErrors.value = {};
    const f = itemForm.value;

    const payload = {
        title:          f.title,
        category_id:    f.category_id,
        vendor_name:    f.vendor_name || null,
        notes:          f.notes || null,
        planned_amount: parseRupiah(f.planned_amount) ?? 0,
        due_date:       f.due_date || null,
    };

    if (f.use_dp_tracking) {
        payload.dp_amount    = parseRupiah(f.dp_amount) ?? null;
        payload.dp_paid      = f.dp_paid;
        payload.final_amount = parseRupiah(f.final_amount) ?? null;
        payload.final_paid   = f.final_paid;
        payload.actual_amount = null;
        // sync payment_status from dp/final
        if (f.final_paid)     payload.payment_status = 'paid';
        else if (f.dp_paid)   payload.payment_status = 'dp';
        else                  payload.payment_status = 'unpaid';
    } else {
        payload.actual_amount  = f.actual_amount !== '' ? (parseRupiah(f.actual_amount) ?? null) : null;
        payload.payment_status = f.payment_status;
        payload.payment_date   = f.payment_date || null;
        payload.dp_amount      = null;
        payload.final_amount   = null;
        payload.dp_paid        = false;
        payload.final_paid     = false;
    }

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
            showToast('Data belum bisa disimpan. Coba lagi.', 'error');
        }
    }
}

async function togglePayment(item, field) {
    const newVal = !item[field];
    try {
        await window.axios.patch(route('dashboard.budget-planner.items.payment', item.id), {
            [field]: newVal,
        });
        showToast(newVal ? 'Pembayaran ditandai lunas.' : 'Pembayaran dibatalkan.');
        router.reload({ preserveScroll: true });
    } catch {
        showToast('Gagal memperbarui pembayaran.', 'error');
    }
}

function confirmArchiveItem(item) {
    archivingItem.value      = item;
    showConfirmArchive.value = true;
}

async function archiveItem() {
    if (!archivingItem.value) return;
    try {
        await window.axios.delete(route('dashboard.budget-planner.items.destroy', archivingItem.value.id));
        showConfirmArchive.value = false;
        archivingItem.value      = null;
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
        showToast(err.response?.data?.message ?? 'Gagal mengarsipkan kategori.', 'error');
    }
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-base font-semibold text-stone-800 truncate">Budget Planner</h1>
        </template>

        <div class="max-w-5xl mx-auto pb-28">

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
                    role="alert" aria-live="polite"
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
                    <h2 class="text-xl font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Budget Planner</h2>
                    <p class="text-sm text-stone-500 mt-0.5">Pantau rencana dan realisasi budget pernikahanmu.</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="showManageCats = true"
                        class="flex items-center gap-1.5 px-3 py-2 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/>
                        </svg>
                        Kelola Kategori
                    </button>
                    <button @click="openAddItem()"
                        class="hidden sm:flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-xl transition-opacity hover:opacity-90"
                        style="background-color: #D4A373">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Item
                    </button>
                </div>
            </div>

            <!-- ── Onboarding Card ─────────────────────────────────────────── -->
            <div v-if="isFirstTime" class="bg-white border border-amber-100 rounded-2xl p-6 shadow-sm mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                         style="background-color: #FFF3E0">
                        <svg class="w-6 h-6" style="color: #D4A373" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-stone-800">Mulai rencanakan budget pernikahanmu</h3>
                        <p class="text-xs text-stone-500 mt-0.5">Set total budget dulu agar kamu bisa pantau pengeluaran dengan akurat.</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="openAddItem()"
                            class="px-3 py-2 text-xs font-medium text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">
                            Tambah Item Langsung
                        </button>
                        <button @click="openSetBudget"
                            class="px-4 py-2 text-xs font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                            style="background-color: #D4A373">
                            Atur Total Budget
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── No budget notice (has items but no total budget) ────────── -->
            <div v-else-if="!hasBudget && (items?.length > 0 || categoryBreakdown?.some(c => c.items_count > 0))"
                 class="flex items-center gap-3 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 mb-4 text-xs">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="text-amber-700">Total budget belum diatur. Sisa budget tidak dapat dihitung.</span>
                <button @click="openSetBudget" class="ml-auto font-semibold text-amber-700 underline whitespace-nowrap">Atur sekarang</button>
            </div>

            <!-- ── Donut Chart + Summary ───────────────────────────────────── -->
            <div class="bg-white border border-stone-100 rounded-2xl shadow-sm p-4 mb-4">
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">

                    <!-- Donut chart -->
                    <div class="flex flex-col items-center flex-shrink-0">
                        <div class="relative w-44 h-44">
                            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                                <!-- Background ring -->
                                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F5F0EB" stroke-width="3.8"/>
                                <!-- Slices -->
                                <circle
                                    v-for="(slice, i) in donutSlices" :key="slice.id"
                                    cx="18" cy="18" r="15.9155"
                                    fill="none"
                                    :stroke="slice.color"
                                    stroke-width="3.8"
                                    :stroke-dasharray="`${slice.pct} ${100 - slice.pct}`"
                                    :stroke-dashoffset="25 - slice.offset"
                                    :class="['cursor-pointer transition-all duration-150', selectedSlice === slice.id ? 'opacity-100' : (selectedSlice ? 'opacity-50' : 'opacity-100')]"
                                    @click.prevent="selectSlice(slice.id)"
                                />
                            </svg>
                            <!-- Center label -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-2">
                                <template v-if="donutSlices.length === 0">
                                    <span class="text-xs text-stone-400">Atur budget dulu</span>
                                </template>
                                <template v-else>
                                    <span class="text-xs text-stone-400 leading-tight">{{ donutCenterLabel.primary }}</span>
                                    <span
                                        class="text-sm font-bold leading-tight mt-0.5"
                                        :class="donutCenterLabel.overbudget ? 'text-rose-600' : 'text-stone-800'"
                                    >{{ donutCenterLabel.amount }}</span>
                                    <span v-if="donutCenterLabel.secondary" class="text-xs text-stone-400 leading-tight mt-0.5 truncate w-full px-1">
                                        {{ donutCenterLabel.secondary }}
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Legend (mobile: scrollable row) -->
                        <div class="flex flex-wrap justify-center gap-x-3 gap-y-1 mt-3 max-w-xs">
                            <button
                                v-for="(slice, i) in donutSlices" :key="slice.id"
                                @click="selectSlice(slice.id)"
                                class="flex items-center gap-1 text-xs text-stone-600 hover:text-stone-800"
                            >
                                <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: slice.color }"/>
                                <span class="truncate max-w-[80px]">{{ slice.name }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Summary stats -->
                    <div class="flex-1 grid grid-cols-2 gap-3">
                        <!-- Total Budget -->
                        <div class="bg-stone-50 rounded-xl p-3">
                            <p class="text-xs text-stone-400 font-medium">Total Budget</p>
                            <p class="text-base font-bold text-stone-800 mt-0.5 leading-tight">
                                {{ summary.has_budget ? summary.formatted.total_budget : '—' }}
                            </p>
                            <button @click="openSetBudget" class="mt-1 text-xs font-medium transition-colors" style="color: #D4A373">
                                {{ summary.has_budget ? 'Ubah' : 'Atur budget' }}
                            </button>
                        </div>

                        <!-- Total Planned -->
                        <div class="bg-stone-50 rounded-xl p-3">
                            <p class="text-xs text-stone-400 font-medium">Total Planned</p>
                            <p class="text-base font-bold text-stone-800 mt-0.5 leading-tight">
                                {{ summary.formatted.total_planned }}
                            </p>
                            <p class="mt-1 text-xs text-stone-400">Dari semua item</p>
                        </div>

                        <!-- Terpakai -->
                        <div class="bg-stone-50 rounded-xl p-3">
                            <p class="text-xs text-stone-400 font-medium">Terpakai</p>
                            <p class="text-base font-bold mt-0.5 leading-tight"
                               :class="summary.is_total_overbudget ? 'text-rose-600' : 'text-stone-800'">
                                {{ summary.formatted.total_actual }}
                            </p>
                            <p v-if="summary.is_total_overbudget" class="mt-1 text-xs text-rose-500 font-medium">
                                Melebihi budget
                            </p>
                            <p v-else class="mt-1 text-xs text-stone-400">Sudah dicatat</p>
                        </div>

                        <!-- Sisa -->
                        <div class="bg-stone-50 rounded-xl p-3">
                            <p class="text-xs text-stone-400 font-medium">Sisa Budget</p>
                            <p class="text-base font-bold mt-0.5 leading-tight"
                               :class="summary.remaining_budget < 0 ? 'text-rose-600' : 'text-stone-800'">
                                {{ summary.has_budget ? summary.formatted.remaining_budget : '—' }}
                            </p>
                            <p v-if="summary.overbudget_categories_count > 0" class="mt-1 text-xs text-amber-600">
                                {{ summary.overbudget_categories_count }} kategori melebihi
                            </p>
                            <p v-else class="mt-1 text-xs text-stone-400">
                                {{ summary.has_budget ? 'Dana tersisa' : 'Atur budget dulu' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress bar (only when budget set) -->
                <div v-if="hasBudget" class="mt-4 pt-4 border-t border-stone-100">
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-xs text-stone-500">Budget terpakai</p>
                        <div class="flex items-center gap-2">
                            <span v-if="summary.is_total_overbudget"
                                  class="text-xs font-semibold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">Overbudget</span>
                            <span class="text-xs font-bold text-stone-700">{{ progressPct }}%</span>
                        </div>
                    </div>
                    <div class="h-2.5 bg-stone-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                             :style="{ width: progressPct + '%', backgroundColor: progressColor }"/>
                    </div>
                </div>
            </div>

            <!-- ── View Toggle ─────────────────────────────────────────────── -->
            <div class="flex items-center gap-1 bg-stone-100 rounded-xl p-1 mb-4 w-fit">
                <button
                    @click="activeView = 'category'"
                    :class="['px-4 py-1.5 text-sm font-medium rounded-lg transition-all', activeView === 'category' ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500 hover:text-stone-700']"
                >Kategori</button>
                <button
                    @click="activeView = 'item'"
                    :class="['px-4 py-1.5 text-sm font-medium rounded-lg transition-all', activeView === 'item' ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500 hover:text-stone-700']"
                >Daftar Item</button>
            </div>

            <!-- ═══════════════ CATEGORY VIEW ═══════════════════════════════ -->
            <template v-if="activeView === 'category'">
                <div v-if="!categoryBreakdown?.length" class="bg-white border border-stone-100 rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-12 h-12 bg-stone-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-stone-600">Kategori default sudah siap</p>
                    <p class="text-xs text-stone-400 mt-1">Mulai tambah pengeluaran untuk melihat breakdown kategori.</p>
                    <button @click="openAddItem()" class="mt-3 text-sm font-medium" style="color: #D4A373">Tambah pengeluaran pertama →</button>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="(cat, catIdx) in categoryBreakdown" :key="cat.id"
                        class="bg-white border border-stone-100 rounded-2xl shadow-sm overflow-hidden"
                    >
                        <!-- Category header (always visible) -->
                        <button
                            class="w-full px-4 pt-4 pb-3 flex items-start gap-3 text-left"
                            @click="toggleCat(cat.id)"
                        >
                            <!-- Color dot -->
                            <span class="mt-0.5 w-3 h-3 rounded-full flex-shrink-0"
                                  :style="{ backgroundColor: catColor(cat, catIdx) }"/>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-semibold text-stone-800">{{ cat.name }}</h3>
                                    <!-- Status badge -->
                                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-full flex items-center gap-1', statusCfg(cat.status).bg, statusCfg(cat.status).text]">
                                        <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: statusCfg(cat.status).dot }"/>
                                        {{ statusCfg(cat.status).label }}
                                    </span>
                                </div>

                                <!-- Compact amount row (mobile) -->
                                <p class="text-xs text-stone-400 mt-0.5 sm:hidden">
                                    {{ cat.formatted.actual_total }} / {{ cat.formatted.planned_total }}
                                </p>

                                <!-- Mini progress bar -->
                                <div class="h-1 bg-stone-100 rounded-full overflow-hidden mt-2">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :style="{ width: cat.usage_percentage + '%', backgroundColor: statusCfg(cat.status).bar }"/>
                                </div>

                                <!-- Desktop amount row -->
                                <div class="hidden sm:grid grid-cols-3 gap-2 text-xs mt-2">
                                    <div>
                                        <p class="text-stone-400">Planned</p>
                                        <p class="font-semibold text-stone-700">{{ cat.formatted.planned_total }}</p>
                                    </div>
                                    <div>
                                        <p class="text-stone-400">Terpakai</p>
                                        <p class="font-semibold" :class="cat.status === 'melebihi' ? 'text-rose-600' : 'text-stone-700'">
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

                            <!-- Expand chevron + item count -->
                            <div class="flex items-center gap-2 flex-shrink-0 mt-0.5">
                                <span class="text-xs text-stone-400">{{ cat.items_count }}</span>
                                <svg class="w-4 h-4 text-stone-400 transition-transform duration-200"
                                     :class="expandedCats.has(cat.id) ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        <!-- Inline item list (expanded) -->
                        <Transition name="expand">
                            <div v-if="expandedCats.has(cat.id)" class="border-t border-stone-100">
                                <!-- Empty state for category -->
                                <div v-if="!cat.items?.length" class="px-4 py-5 text-center">
                                    <p class="text-xs text-stone-400">Belum ada item di kategori ini.</p>
                                    <button @click="openAddItem(cat.id)" class="mt-2 text-xs font-medium" style="color: #D4A373">
                                        + Tambah item
                                    </button>
                                </div>

                                <!-- Item rows -->
                                <div v-else class="divide-y divide-stone-50">
                                    <div v-for="item in cat.items" :key="item.id" class="px-4 py-3">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <p class="text-sm font-medium text-stone-800 truncate">{{ item.title }}</p>
                                                    <!-- Payment badge -->
                                                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', paymentBadge[item.payment_status]]">
                                                        {{ paymentLabel[item.payment_status] }}
                                                    </span>
                                                    <!-- Due date warning -->
                                                    <span v-if="item.due_date_warning === 'overdue'"
                                                          class="text-xs font-medium px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                                                        Overdue
                                                    </span>
                                                    <span v-else-if="item.due_date_warning === 'soon'"
                                                          class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                                        Segera
                                                    </span>
                                                </div>

                                                <p v-if="item.vendor_name" class="text-xs text-stone-400 mt-0.5">{{ item.vendor_name }}</p>

                                                <!-- Amounts -->
                                                <div class="flex items-center gap-3 text-xs mt-1.5 flex-wrap">
                                                    <span class="text-stone-400">
                                                        Planned: <span class="text-stone-600 font-medium">{{ item.formatted.planned_amount }}</span>
                                                    </span>
                                                    <span class="text-stone-400">
                                                        Terpakai: <span :class="['font-medium', item.sisa < 0 ? 'text-rose-600' : 'text-stone-600']">{{ item.formatted.terpakai }}</span>
                                                    </span>
                                                    <span class="text-stone-400">
                                                        Sisa: <span :class="['font-medium', item.sisa < 0 ? 'text-rose-600' : 'text-stone-600']">
                                                            {{ item.sisa < 0 ? '-' : '' }}{{ item.formatted.sisa }}
                                                        </span>
                                                    </span>
                                                </div>

                                                <!-- DP / Pelunasan tracking -->
                                                <div v-if="item.dp_amount !== null || item.final_amount !== null" class="flex items-center gap-3 mt-1.5 text-xs flex-wrap">
                                                    <button
                                                        v-if="item.dp_amount !== null"
                                                        @click="togglePayment(item, 'dp_paid')"
                                                        :class="['flex items-center gap-1 px-2 py-1 rounded-lg border transition-colors',
                                                            item.dp_paid ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'border-stone-200 text-stone-500 hover:bg-stone-50']"
                                                    >
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path v-if="item.dp_paid" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                        </svg>
                                                        DP {{ item.formatted.dp_amount }}
                                                    </button>
                                                    <button
                                                        v-if="item.final_amount !== null"
                                                        @click="togglePayment(item, 'final_paid')"
                                                        :class="['flex items-center gap-1 px-2 py-1 rounded-lg border transition-colors',
                                                            item.final_paid ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'border-stone-200 text-stone-500 hover:bg-stone-50']"
                                                    >
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path v-if="item.final_paid" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                        </svg>
                                                        Pelunasan {{ item.formatted.final_amount }}
                                                    </button>
                                                </div>

                                                <!-- Due date -->
                                                <p v-if="item.due_date_label" class="text-xs text-stone-400 mt-1">
                                                    📅 Jatuh tempo: {{ item.due_date_label }}
                                                </p>
                                            </div>

                                            <!-- Edit / Archive -->
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                <button @click="openEditItem(item)"
                                                    class="p-1.5 text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                                    title="Edit">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button @click="confirmArchiveItem(item)"
                                                    class="p-1.5 text-stone-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
                                                    title="Arsipkan">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add item to this category -->
                                <div class="px-4 py-3 border-t border-stone-50">
                                    <button @click="openAddItem(cat.id)"
                                        class="flex items-center gap-1.5 text-xs font-medium transition-colors"
                                        style="color: #D4A373">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Tambah item ke {{ cat.name }}
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </template>

            <!-- ═══════════════ ITEM LIST VIEW ═══════════════════════════════ -->
            <template v-else>
                <!-- Search + Filter row -->
                <div class="flex gap-2 mb-3">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="searchQuery" type="search" placeholder="Cari item atau vendor"
                            class="w-full pl-9 pr-3 py-2.5 text-sm bg-white border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                            style="--tw-ring-color: #D4A373" />
                    </div>
                    <button @click="showFilterSheet = true"
                        :class="['flex items-center gap-1.5 px-3 py-2.5 text-sm border rounded-xl transition-colors',
                            (filterStatus !== 'all' || filterCategory || sortBy !== 'newest')
                                ? 'bg-amber-50 border-amber-200 text-amber-700 font-medium'
                                : 'bg-white border-stone-200 text-stone-600 hover:bg-stone-50']"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Filter
                    </button>
                </div>

                <!-- Empty state -->
                <div v-if="!items?.length" class="bg-white border border-stone-100 rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-12 h-12 bg-stone-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <template v-if="searchQuery || filterStatus !== 'all' || filterCategory">
                        <p class="text-sm font-medium text-stone-600">Tidak ada hasil yang cocok.</p>
                        <p class="text-xs text-stone-400 mt-1">Coba kata kunci atau filter lain.</p>
                        <button @click="clearFilters" class="mt-3 text-sm font-medium" style="color: #D4A373">Reset filter →</button>
                    </template>
                    <template v-else>
                        <p class="text-sm font-medium text-stone-600">Belum ada pengeluaran tercatat.</p>
                        <p class="text-xs text-stone-400 mt-1">Mulai catat pengeluaran pertamamu.</p>
                        <button @click="openAddItem()" class="mt-3 text-sm font-medium" style="color: #D4A373">Tambah pengeluaran →</button>
                    </template>
                </div>

                <!-- Item cards -->
                <div v-else class="space-y-2">
                    <div v-for="item in items" :key="item.id" class="bg-white border border-stone-100 rounded-2xl p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <p class="text-sm font-semibold text-stone-800 truncate">{{ item.title }}</p>
                                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', paymentBadge[item.payment_status]]">
                                        {{ paymentLabel[item.payment_status] }}
                                    </span>
                                    <span v-if="item.due_date_warning === 'overdue'"
                                          class="text-xs font-medium px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Overdue</span>
                                    <span v-else-if="item.due_date_warning === 'soon'"
                                          class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Segera</span>
                                </div>
                                <p class="text-xs text-stone-400 mb-2">
                                    {{ item.category?.name }}
                                    <span v-if="item.vendor_name"> · {{ item.vendor_name }}</span>
                                </p>
                                <div class="flex items-center gap-4 text-xs flex-wrap">
                                    <span class="text-stone-400">Planned: <span class="font-semibold text-stone-700">{{ item.formatted.planned_amount }}</span></span>
                                    <span class="text-stone-400">Terpakai: <span :class="['font-semibold', item.sisa < 0 ? 'text-rose-600' : 'text-stone-700']">{{ item.formatted.terpakai }}</span></span>
                                </div>
                                <!-- DP / final inline -->
                                <div v-if="item.dp_amount !== null || item.final_amount !== null" class="flex gap-2 mt-1.5 text-xs flex-wrap">
                                    <button v-if="item.dp_amount !== null"
                                        @click="togglePayment(item, 'dp_paid')"
                                        :class="['flex items-center gap-1 px-2 py-1 rounded-lg border transition-colors',
                                            item.dp_paid ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'border-stone-200 text-stone-500 hover:bg-stone-50']">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path v-if="item.dp_paid" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        DP {{ item.formatted.dp_amount }}
                                    </button>
                                    <button v-if="item.final_amount !== null"
                                        @click="togglePayment(item, 'final_paid')"
                                        :class="['flex items-center gap-1 px-2 py-1 rounded-lg border transition-colors',
                                            item.final_paid ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'border-stone-200 text-stone-500 hover:bg-stone-50']">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path v-if="item.final_paid" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        Pelunasan {{ item.formatted.final_amount }}
                                    </button>
                                </div>
                                <p v-if="item.due_date_label" class="text-xs text-stone-400 mt-1">📅 {{ item.due_date_label }}</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button @click="openEditItem(item)"
                                    class="p-2 text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click="confirmArchiveItem(item)"
                                    class="p-2 text-stone-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Arsipkan">
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

        <!-- ── Mobile FAB ──────────────────────────────────────────────────── -->
        <div class="fixed bottom-6 right-4 sm:hidden z-20">
            <button @click="openAddItem()"
                class="flex items-center gap-2 px-5 py-3 text-white text-sm font-semibold rounded-2xl shadow-lg transition-opacity hover:opacity-90"
                style="background-color: #D4A373">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Item
            </button>
        </div>

        <!-- ════════════════ MODALS ════════════════════════════════════════ -->

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
                                type="text" inputmode="numeric" placeholder="Rp 0"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Catatan (opsional)</label>
                            <textarea v-model="budgetForm.notes" rows="2"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                                style="--tw-ring-color: #D4A373" />
                        </div>
                    </div>
                    <div class="flex gap-2 mt-5">
                        <button @click="showSetBudget = false" class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">Batal</button>
                        <button @click="saveBudget" class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90" style="background-color: #D4A373">Simpan</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Add/Edit Item Modal ─────────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="showAddItem || showEditItem" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showAddItem = showEditItem = false"/>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
                        <h3 class="text-base font-semibold text-stone-800">{{ editingItem ? 'Edit Pengeluaran' : 'Tambah Pengeluaran' }}</h3>
                        <button @click="showAddItem = showEditItem = false" class="p-1 text-stone-400 hover:text-stone-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                        <!-- Nama item -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Nama Item <span class="text-rose-400">*</span></label>
                            <input v-model="itemForm.title" type="text" placeholder="Contoh: Gedung Akad"
                                class="w-full px-3 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                :class="itemErrors.title ? 'border-rose-300' : 'border-stone-200'"
                                style="--tw-ring-color: #D4A373" />
                            <p v-if="itemErrors.title" class="mt-1 text-xs text-rose-500">{{ itemErrors.title[0] }}</p>
                        </div>

                        <!-- Kategori -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Kategori <span class="text-rose-400">*</span></label>
                            <select v-model="itemForm.category_id"
                                class="w-full px-3 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white"
                                :class="itemErrors.category_id ? 'border-rose-300' : 'border-stone-200'"
                                style="--tw-ring-color: #D4A373">
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
                                type="text" inputmode="numeric" placeholder="Rp 0"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373" />
                        </div>

                        <!-- Payment tracking mode toggle -->
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-medium text-stone-600">Tracking DP & Pelunasan</label>
                            <button
                                @click="itemForm.use_dp_tracking = !itemForm.use_dp_tracking"
                                :class="['relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                                    itemForm.use_dp_tracking ? 'bg-amber-400' : 'bg-stone-200']"
                                role="switch" :aria-checked="itemForm.use_dp_tracking"
                            >
                                <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200',
                                    itemForm.use_dp_tracking ? 'translate-x-4' : 'translate-x-0']"/>
                            </button>
                        </div>

                        <!-- Simple payment mode -->
                        <template v-if="!itemForm.use_dp_tracking">
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Jumlah Terbayar</label>
                                <input
                                    :value="itemForm.actual_amount !== '' ? formatRupiah(itemForm.actual_amount) : ''"
                                    @input="itemForm.actual_amount = $event.target.value.replace(/\D/g, '')"
                                    type="text" inputmode="numeric" placeholder="Belum ada"
                                    class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #D4A373" />
                                <p class="mt-1 text-xs text-stone-400">Kosongkan jika belum ada pembayaran.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Status Pembayaran</label>
                                <div class="flex gap-2">
                                    <button v-for="opt in paymentStatusOptions" :key="opt.value"
                                        @click="itemForm.payment_status = opt.value"
                                        :class="['flex-1 py-2 text-xs font-medium rounded-xl border transition-colors',
                                            itemForm.payment_status === opt.value ? 'border-transparent text-white' : 'border-stone-200 text-stone-600 hover:bg-stone-50']"
                                        :style="itemForm.payment_status === opt.value ? 'background-color: #D4A373' : ''">
                                        {{ opt.label }}
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Tanggal Pembayaran</label>
                                <input v-model="itemForm.payment_date" type="date"
                                    class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #D4A373" />
                            </div>
                        </template>

                        <!-- DP + Pelunasan tracking mode -->
                        <template v-else>
                            <div class="space-y-3 bg-stone-50 rounded-xl p-3">
                                <p class="text-xs font-medium text-stone-500">DP (Uang Muka)</p>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <input
                                            :value="itemForm.dp_amount !== '' ? formatRupiah(itemForm.dp_amount) : ''"
                                            @input="itemForm.dp_amount = $event.target.value.replace(/\D/g, '')"
                                            type="text" inputmode="numeric" placeholder="Rp 0 (opsional)"
                                            class="w-full px-3 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white"
                                            style="--tw-ring-color: #D4A373" />
                                    </div>
                                    <button
                                        @click="itemForm.dp_paid = !itemForm.dp_paid"
                                        :class="['flex-shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-xl border transition-colors',
                                            itemForm.dp_paid ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'border-stone-200 text-stone-500 bg-white hover:bg-stone-50']">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path v-if="itemForm.dp_paid" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        {{ itemForm.dp_paid ? 'Terbayar' : 'Belum' }}
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3 bg-stone-50 rounded-xl p-3">
                                <p class="text-xs font-medium text-stone-500">Pelunasan</p>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <input
                                            :value="itemForm.final_amount !== '' ? formatRupiah(itemForm.final_amount) : ''"
                                            @input="itemForm.final_amount = $event.target.value.replace(/\D/g, '')"
                                            type="text" inputmode="numeric" placeholder="Rp 0 (opsional)"
                                            class="w-full px-3 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent bg-white"
                                            style="--tw-ring-color: #D4A373" />
                                    </div>
                                    <button
                                        @click="itemForm.final_paid = !itemForm.final_paid"
                                        :class="['flex-shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-xl border transition-colors',
                                            itemForm.final_paid ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'border-stone-200 text-stone-500 bg-white hover:bg-stone-50']">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path v-if="itemForm.final_paid" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        {{ itemForm.final_paid ? 'Terbayar' : 'Belum' }}
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Jatuh tempo -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Jatuh Tempo (opsional)</label>
                            <input v-model="itemForm.due_date" type="date"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373" />
                        </div>

                        <!-- Vendor -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Vendor (opsional)</label>
                            <input v-model="itemForm.vendor_name" type="text" placeholder="Nama vendor"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Catatan (opsional)</label>
                            <textarea v-model="itemForm.notes" rows="2" placeholder="Catatan tambahan"
                                class="w-full px-3 py-2.5 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent resize-none"
                                style="--tw-ring-color: #D4A373" />
                        </div>
                    </div>

                    <div class="px-5 py-4 border-t border-stone-100 flex gap-2">
                        <button @click="showAddItem = showEditItem = false"
                            class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">Batal</button>
                        <button @click="saveItem"
                            class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                            style="background-color: #D4A373">Simpan</button>
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

                    <div class="mb-4">
                        <p class="text-xs font-medium text-stone-500 mb-2">Status Pembayaran</p>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="opt in [{ value: 'all', label: 'Semua' }, ...paymentStatusOptions]" :key="opt.value"
                                @click="filterStatus = opt.value"
                                :class="['px-3 py-1.5 text-xs font-medium rounded-xl border transition-colors',
                                    filterStatus === opt.value ? 'border-transparent text-white' : 'border-stone-200 text-stone-600 hover:bg-stone-50']"
                                :style="filterStatus === opt.value ? 'background-color: #D4A373' : ''">
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="text-xs font-medium text-stone-500 mb-2">Kategori</p>
                        <select v-model="filterCategory" class="w-full px-3 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none bg-white">
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

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
                        <button @click="clearFilters" class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">Reset</button>
                        <button @click="applyFilters" class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90" style="background-color: #D4A373">Terapkan</button>
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
                        <div class="flex gap-2 mb-4">
                            <input v-model="categoryForm.name" type="text" placeholder="Nama kategori baru"
                                @keyup.enter="addCategory"
                                class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: #D4A373" />
                            <button @click="addCategory"
                                class="px-4 py-2 text-sm font-medium text-white rounded-xl transition-opacity hover:opacity-90"
                                style="background-color: #D4A373">Tambah</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="cat in categoryBreakdown" :key="cat.id"
                                class="flex items-center justify-between py-2.5 px-3 bg-stone-50 rounded-xl">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: cat.color || '#D4A373' }"/>
                                    <span class="text-sm text-stone-700 font-medium">{{ cat.name }}</span>
                                    <span v-if="cat.type === 'custom'" class="text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">Custom</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-stone-400">{{ cat.items_count }} item</span>
                                    <button v-if="cat.type === 'custom'" @click="archiveCategory(cat)"
                                        class="p-1.5 text-stone-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-stone-400 mt-3">Kategori default tidak bisa diarsipkan. Kategori custom bisa diarsipkan jika tidak memiliki item aktif.</p>
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
                    <p class="text-sm text-stone-500">"<strong>{{ archivingItem?.title }}</strong>" tidak akan dihitung dalam ringkasan aktif.</p>
                    <div class="flex gap-2 mt-5">
                        <button @click="showConfirmArchive = false; archivingItem = null"
                            class="flex-1 py-2.5 text-sm text-stone-600 border border-stone-200 rounded-xl hover:bg-stone-50 transition-colors">Batal</button>
                        <button @click="archiveItem"
                            class="flex-1 py-2.5 text-sm font-semibold text-white bg-rose-500 rounded-xl hover:bg-rose-600 transition-colors">Arsipkan</button>
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

.expand-enter-active, .expand-leave-active { transition: all 0.25s ease; overflow: hidden; }
.expand-enter-from, .expand-leave-to { opacity: 0; max-height: 0; }
.expand-enter-to, .expand-leave-from { opacity: 1; max-height: 2000px; }
</style>
