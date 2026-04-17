<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref, computed, reactive, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    weddingPlan: Object,
});

// ── State ──────────────────────────────────────────────────────────────────
const tasks          = ref([]);
const summary        = ref({ total: 0, todo: 0, done: 0, archived: 0, progress: 0, has_event_date: false });
const loading        = ref(true);
const error          = ref(null);
const filterStatus   = ref('');
const filterCat      = ref('');
const filterPriority = ref('');
const sortBy         = ref('');
const eventDate      = ref('');
const savingDate     = ref(false);
const eventDateError = ref('');
const showForm       = ref(false);
const editingTask    = ref(null);
const togglingId     = ref(null);
const moveDoneToBottom = ref(false);

// ── Collapse state (session-persisted) ────────────────────────────────────
const COLLAPSE_KEY = 'checklist_collapsed_v1';
const collapsedGroups = ref(new Set(
    JSON.parse(sessionStorage.getItem(COLLAPSE_KEY) ?? '[]')
));

watch(collapsedGroups, (v) => {
    sessionStorage.setItem(COLLAPSE_KEY, JSON.stringify([...v]));
}, { deep: true });

function toggleGroup(cat) {
    const next = new Set(collapsedGroups.value);
    if (next.has(cat)) next.delete(cat);
    else next.add(cat);
    collapsedGroups.value = next;
}

// ── Bulk selection ─────────────────────────────────────────────────────────
const bulkMode    = ref(false);
const selectedIds = ref(new Set());
const bulking     = ref(false);
let longPressTimer = null;

function startBulkMode(task) {
    bulkMode.value = true;
    selectedIds.value = new Set([task.id]);
}

function cancelBulkMode() {
    bulkMode.value = false;
    selectedIds.value = new Set();
}

function toggleSelect(task) {
    const next = new Set(selectedIds.value);
    if (next.has(task.id)) next.delete(task.id);
    else next.add(task.id);
    selectedIds.value = next;
    if (next.size === 0) cancelBulkMode();
}

function onLongPressStart(task) {
    if (bulkMode.value) return;
    longPressTimer = setTimeout(() => startBulkMode(task), 500);
}

function onLongPressCancel() {
    clearTimeout(longPressTimer);
}

// ── Swipe state ────────────────────────────────────────────────────────────
const swipeMap = reactive({});

function getSwipe(id) {
    if (!swipeMap[id]) swipeMap[id] = { startX: 0, offsetX: 0, open: false, dragging: false };
    return swipeMap[id];
}

function onSwipeStart(task, e) {
    if (bulkMode.value) return;
    const sw = getSwipe(task.id);
    sw.startX  = e.touches[0].clientX;
    sw.dragging = true;
}

function onSwipeMove(task, e) {
    const sw = getSwipe(task.id);
    if (!sw.dragging) return;
    const dx = e.touches[0].clientX - sw.startX;
    if (dx < 0) sw.offsetX = Math.max(dx, -120);
    else if (sw.open) sw.offsetX = Math.min(0, -120 + dx);
}

function onSwipeEnd(task) {
    const sw = getSwipe(task.id);
    sw.dragging = false;
    if (sw.offsetX < -55) {
        sw.offsetX = -120;
        sw.open = true;
    } else {
        sw.offsetX = 0;
        sw.open = false;
    }
}

function closeSwipe(id) {
    const sw = swipeMap[id];
    if (sw) { sw.offsetX = 0; sw.open = false; }
}

function closeAllSwipes() {
    Object.values(swipeMap).forEach(sw => { sw.offsetX = 0; sw.open = false; });
}

// ── Form ───────────────────────────────────────────────────────────────────
const emptyForm = () => ({ title: '', category: 'lainnya', priority: 'medium', due_date: '', description: '' });
const form      = ref(emptyForm());
const formError = ref({});
const saving    = ref(false);
const customCategory = ref('');
const usingCustomCategory = ref(false);

// ── Toast ──────────────────────────────────────────────────────────────────
const toast      = ref(null);
let   toastTimer = null;
function showToast(message, type = 'success') {
    clearTimeout(toastTimer);
    toast.value = { message, type };
    toastTimer = setTimeout(() => { toast.value = null; }, 3500);
}

// ── Categories ─────────────────────────────────────────────────────────────
const CATEGORY_ORDER = [
    'administrasi','venue','vendor','undangan','keuangan',
    'busana','dekorasi','dokumentasi','tamu','acara','lainnya',
];

const categories = [
    { value: 'administrasi', label: 'Administrasi' },
    { value: 'venue',        label: 'Venue' },
    { value: 'vendor',       label: 'Vendor' },
    { value: 'undangan',     label: 'Undangan' },
    { value: 'keuangan',     label: 'Keuangan' },
    { value: 'busana',       label: 'Busana' },
    { value: 'dekorasi',     label: 'Dekorasi' },
    { value: 'dokumentasi',  label: 'Dokumentasi' },
    { value: 'tamu',         label: 'Tamu' },
    { value: 'acara',        label: 'Acara' },
    { value: 'lainnya',      label: 'Lainnya' },
];

const categoryLabel = (val) => {
    const found = categories.find(c => c.value === val);
    return found ? found.label : (val.charAt(0).toUpperCase() + val.slice(1));
};

const priorityConfig = {
    high:   { label: 'Tinggi', dotClass: 'bg-red-500',   textClass: 'text-red-600'   },
    medium: { label: 'Sedang', dotClass: 'bg-[#92A89C]', textClass: 'text-[#73877C]' },
    low:    { label: 'Rendah', dotClass: 'bg-stone-300', textClass: 'text-stone-400' },
};

// ── Computed ───────────────────────────────────────────────────────────────
const activeTasks   = computed(() => tasks.value.filter(t => t.status !== 'archived'));
const archivedTasks = computed(() => tasks.value.filter(t => t.status === 'archived'));

const priorityOrder = { high: 0, medium: 1, low: 2 };

const baseList = computed(() => {
    let list = filterStatus.value === 'archived' ? archivedTasks.value : activeTasks.value;
    if (filterStatus.value && filterStatus.value !== 'archived') {
        list = list.filter(t => t.status === filterStatus.value);
    }
    if (filterCat.value)      list = list.filter(t => t.category === filterCat.value);
    if (filterPriority.value) list = list.filter(t => t.priority === filterPriority.value);
    return list;
});

function sortList(list) {
    let sorted = [...list];
    if (sortBy.value === 'due_date') {
        sorted.sort((a, b) => {
            if (!a.due_date && !b.due_date) return 0;
            if (!a.due_date) return 1;
            if (!b.due_date) return -1;
            return new Date(a.due_date) - new Date(b.due_date);
        });
    } else if (sortBy.value === 'priority') {
        sorted.sort((a, b) => (priorityOrder[a.priority] ?? 1) - (priorityOrder[b.priority] ?? 1));
    }
    if (moveDoneToBottom.value) {
        sorted.sort((a, b) => {
            if (a.status === 'done' && b.status !== 'done') return 1;
            if (a.status !== 'done' && b.status === 'done') return -1;
            return 0;
        });
    }
    return sorted;
}

const groups = computed(() => {
    const list = baseList.value;
    const map  = new Map();
    for (const task of list) {
        if (!map.has(task.category)) map.set(task.category, []);
        map.get(task.category).push(task);
    }

    return [...map.entries()]
        .sort((a, b) => {
            const ai = CATEGORY_ORDER.indexOf(a[0]);
            const bi = CATEGORY_ORDER.indexOf(b[0]);
            if (ai === -1 && bi === -1) return a[0].localeCompare(b[0]);
            if (ai === -1) return 1;
            if (bi === -1) return -1;
            return ai - bi;
        })
        .map(([cat, catTasks]) => {
            const allForCat  = activeTasks.value.filter(t => t.category === cat);
            const doneCount  = allForCat.filter(t => t.status === 'done').length;
            const totalCount = allForCat.length;
            return {
                cat,
                label:    categoryLabel(cat),
                tasks:    sortList(catTasks),
                done:     doneCount,
                total:    totalCount,
                progress: totalCount > 0 ? Math.round((doneCount / totalCount) * 100) : 0,
            };
        });
});

const allDone = computed(() =>
    summary.value.total > 0 && summary.value.todo === 0
);

// ── Bootstrap ──────────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        if (!props.weddingPlan?.initialized) {
            await axios.post(route('dashboard.checklist.initialize'));
        }
        await Promise.all([loadTasks(), loadSummary()]);
    } catch {
        error.value = 'Gagal memuat checklist. Silakan muat ulang halaman.';
    } finally {
        loading.value = false;
    }
});

async function loadTasks() {
    const { data } = await axios.get(route('dashboard.checklist.tasks'));
    tasks.value = data.tasks;
}

async function loadSummary() {
    const { data } = await axios.get(route('dashboard.checklist.summary'));
    summary.value = data;
}

async function saveEventDate() {
    eventDateError.value = '';
    if (!eventDate.value || savingDate.value) return;
    const today = new Date(); today.setHours(0, 0, 0, 0);
    if (new Date(eventDate.value) < today) {
        eventDateError.value = 'Tanggal acara tidak boleh di masa lalu.';
        return;
    }
    savingDate.value = true;
    try {
        await axios.patch(route('dashboard.checklist.event-date'), { event_date: eventDate.value });
        await Promise.all([loadTasks(), loadSummary()]);
    } catch (e) {
        eventDateError.value = e.response?.data?.errors?.event_date?.[0] ?? 'Gagal menyimpan tanggal.';
    } finally {
        savingDate.value = false;
    }
}

// ── Toggle ─────────────────────────────────────────────────────────────────
async function toggle(task) {
    if (togglingId.value || bulkMode.value) return;
    const prev = task.status;
    task.status = task.status === 'done' ? 'todo' : 'done';
    togglingId.value = task.id;
    try {
        const { data } = await axios.patch(route('dashboard.checklist.tasks.toggle', task.id));
        Object.assign(task, data);
        await loadSummary();
    } catch {
        task.status = prev;
    } finally {
        togglingId.value = null;
    }
}

// ── Archive / Restore ──────────────────────────────────────────────────────
async function archiveTask(task) {
    closeSwipe(task.id);
    await axios.patch(route('dashboard.checklist.tasks.archive', task.id));
    await Promise.all([loadTasks(), loadSummary()]);
}

async function restoreTask(task) {
    await axios.patch(route('dashboard.checklist.tasks.restore', task.id));
    filterStatus.value = '';
    await Promise.all([loadTasks(), loadSummary()]);
}

// ── Delete ─────────────────────────────────────────────────────────────────
async function deleteTask(task) {
    closeSwipe(task.id);
    if (!confirm(`Hapus "${task.title}"?`)) return;
    await axios.delete(route('dashboard.checklist.tasks.destroy', task.id));
    await Promise.all([loadTasks(), loadSummary()]);
    showToast('Task dihapus.');
}

// ── Bulk ───────────────────────────────────────────────────────────────────
async function doBulkAction(action) {
    if (selectedIds.value.size === 0 || bulking.value) return;
    bulking.value = true;
    try {
        await axios.post(route('dashboard.checklist.tasks.bulk'), {
            ids: [...selectedIds.value],
            action,
        });
        cancelBulkMode();
        await Promise.all([loadTasks(), loadSummary()]);
        const msg = action === 'done' ? 'Ditandai selesai.' : action === 'archive' ? 'Diarsipkan.' : 'Task dihapus.';
        showToast(msg);
    } catch {
        showToast('Gagal melakukan aksi.', 'error');
    } finally {
        bulking.value = false;
    }
}

// ── Create / Edit ──────────────────────────────────────────────────────────
function openCreate() {
    editingTask.value = null;
    form.value = emptyForm();
    formError.value = {};
    usingCustomCategory.value = false;
    customCategory.value = '';
    showForm.value = true;
}

function openEdit(task) {
    closeSwipe(task.id);
    editingTask.value = task;
    const isKnown = categories.some(c => c.value === task.category);
    usingCustomCategory.value = !isKnown;
    customCategory.value = isKnown ? '' : task.category;
    form.value = {
        title:       task.title,
        category:    isKnown ? task.category : 'lainnya',
        priority:    task.priority,
        due_date:    task.due_date ?? '',
        description: task.description ?? '',
    };
    formError.value = {};
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingTask.value = null;
}

async function saveForm() {
    formError.value = {};
    saving.value = true;
    const effectiveCategory = usingCustomCategory.value
        ? customCategory.value.trim()
        : form.value.category;
    if (!effectiveCategory) {
        formError.value.category = 'Kategori wajib diisi.';
        saving.value = false;
        return;
    }
    try {
        const payload = {
            ...form.value,
            category: effectiveCategory,
            due_date: form.value.due_date || null,
        };
        if (editingTask.value) {
            await axios.patch(route('dashboard.checklist.tasks.update', editingTask.value.id), payload);
        } else {
            await axios.post(route('dashboard.checklist.tasks.store'), payload);
        }
        const isEdit = !!editingTask.value;
        closeForm();
        await Promise.all([loadTasks(), loadSummary()]);
        showToast(isEdit ? 'Task diperbarui.' : 'Task ditambahkan.');
    } catch (e) {
        if (e.response?.status === 422) {
            const errs = e.response.data.errors ?? {};
            formError.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, v[0]]));
        }
    } finally {
        saving.value = false;
    }
}

// ── Helpers ────────────────────────────────────────────────────────────────
function formatDate(d) {
    if (!d) return null;
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
function isDueSoon(d) {
    if (!d) return false;
    const diff = (new Date(d) - new Date()) / 86400000;
    return diff <= 7 && diff >= 0;
}
function isOverdue(d) {
    if (!d) return false;
    return new Date(d) < new Date();
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-base font-semibold text-stone-800">Checklist Pernikahan</h1>
        </template>

        <!-- Toast -->
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
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01"/>
                </svg>
                {{ toast.message }}
            </div>
        </Transition>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-24">
            <div class="w-8 h-8 rounded-full border-2 border-[#92A89C]/50 border-t-[#73877C] animate-spin"/>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex flex-col items-center py-24 gap-3">
            <p class="text-stone-500 text-sm">{{ error }}</p>
            <button @click="() => { error = null; loading = true; onMounted(); }"
                    class="text-sm text-[#73877C] underline">Coba lagi</button>
        </div>

        <template v-else>

            <!-- ── Bulk action bar ────────────────────────────────── -->
            <Transition name="slide-down">
                <div v-if="bulkMode"
                     class="sticky top-0 z-20 mb-4 px-4 py-3 bg-white border border-stone-200 rounded-xl shadow-sm flex items-center gap-2">
                    <span class="text-sm font-medium text-stone-700 flex-1">
                        {{ selectedIds.size }} dipilih
                    </span>
                    <button @click="doBulkAction('done')" :disabled="bulking"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 transition-colors disabled:opacity-50">
                        Selesai
                    </button>
                    <button @click="doBulkAction('archive')" :disabled="bulking"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors disabled:opacity-50">
                        Arsipkan
                    </button>
                    <button @click="doBulkAction('delete')" :disabled="bulking"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-colors disabled:opacity-50">
                        Hapus
                    </button>
                    <button @click="cancelBulkMode"
                            class="ml-1 p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </Transition>

            <!-- ── Summary cards ──────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Progress</p>
                    <p class="text-2xl font-bold text-stone-800">{{ summary.progress }}<span class="text-sm font-normal text-stone-400">%</span></p>
                    <div class="mt-2 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                             style="background-color: #92A89C"
                             :style="{ width: summary.progress + '%' }"/>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Selesai</p>
                    <p class="text-2xl font-bold text-green-600">{{ summary.done }}</p>
                    <p class="text-xs text-stone-400 mt-1">dari {{ summary.total }} task</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Perlu dikerjakan</p>
                    <p class="text-2xl font-bold text-[#73877C]">{{ summary.todo }}</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Diarsipkan</p>
                    <p class="text-2xl font-bold text-stone-400">{{ summary.archived }}</p>
                </div>
            </div>

            <!-- ── All done celebration ───────────────────────────── -->
            <Transition name="slide-down">
                <div v-if="allDone"
                     class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-100 text-sm text-emerald-700 flex items-center gap-2">
                    <span class="text-lg">🎉</span>
                    <span class="font-medium">Semua task selesai! Pernikahan siap dirayakan.</span>
                </div>
            </Transition>

            <!-- ── No event date prompt ───────────────────────────── -->
            <div v-if="!summary.has_event_date"
                 class="mb-4 px-4 py-3 rounded-xl bg-[#92A89C]/10 border border-[#B8C7BF]/50 text-sm text-[#73877C]">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Isi tanggal acara agar tenggat task dihitung otomatis.</span>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="eventDate" type="date"
                           class="flex-1 border rounded-lg px-3 py-1.5 text-sm text-stone-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50"
                           :class="eventDateError ? 'border-red-300' : 'border-[#B8C7BF]'"/>
                    <button @click="saveEventDate" :disabled="!eventDate || savingDate"
                            class="px-4 py-1.5 rounded-lg text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40"
                            style="background-color: #92A89C">
                        {{ savingDate ? 'Menyimpan…' : 'Simpan' }}
                    </button>
                </div>
                <p v-if="eventDateError" class="mt-1.5 text-xs text-red-500">{{ eventDateError }}</p>
            </div>

            <!-- ── Controls row ───────────────────────────────────── -->
            <div class="mb-5 space-y-2">
                <!-- Dropdowns: 2-col grid mobile, single row desktop -->
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                    <select v-model="filterStatus"
                            class="w-full sm:w-auto text-sm border border-stone-200 rounded-lg pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30">
                        <option value="">Semua status</option>
                        <option value="todo">Belum selesai</option>
                        <option value="done">Selesai</option>
                        <option value="archived">Diarsipkan</option>
                    </select>

                    <select v-model="filterCat"
                            class="w-full sm:w-auto text-sm border border-stone-200 rounded-lg pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30">
                        <option value="">Semua kategori</option>
                        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>

                    <select v-model="filterPriority"
                            class="w-full sm:w-auto text-sm border border-stone-200 rounded-lg pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30">
                        <option value="">Semua prioritas</option>
                        <option value="high">Tinggi</option>
                        <option value="medium">Sedang</option>
                        <option value="low">Rendah</option>
                    </select>

                    <select v-model="sortBy"
                            class="w-full sm:w-auto text-sm border border-stone-200 rounded-lg pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30">
                        <option value="">Urutan default</option>
                        <option value="due_date">Tenggat terdekat</option>
                        <option value="priority">Prioritas tertinggi</option>
                    </select>
                </div>

                <!-- Toggle + Add button row -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <label class="flex items-center gap-1.5 text-xs text-stone-500 cursor-pointer select-none">
                            <div class="relative w-8 h-4 flex-shrink-0"
                                 @click="moveDoneToBottom = !moveDoneToBottom">
                                <div class="w-full h-full rounded-full transition-colors"
                                     :class="moveDoneToBottom ? 'bg-[#92A89C]' : 'bg-stone-200'"/>
                                <div class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white shadow transition-transform"
                                     :class="moveDoneToBottom ? 'translate-x-4' : 'translate-x-0'"/>
                            </div>
                            Selesai ke bawah
                        </label>
                        <div class="group relative flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-stone-400 cursor-help" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 px-3 py-2 rounded-lg bg-stone-800 text-white text-xs leading-relaxed
                                        opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 pointer-events-none z-50">
                                Task yang sudah selesai otomatis dipindah ke bagian bawah grup, agar task yang belum selesai lebih mudah dilihat.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-stone-800"/>
                            </div>
                        </div>
                    </div>

                    <button @click="openCreate"
                            class="ml-auto flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90"
                            style="background-color: #92A89C">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Task
                    </button>
                </div>
            </div>

            <!-- ── Empty state (no tasks at all) ─────────────────── -->
            <div v-if="tasks.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center bg-[#92A89C]/10">
                    <svg class="w-8 h-8 text-[#92A89C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-stone-700 font-medium mb-1">Belum ada task</h3>
                <p class="text-stone-400 text-sm mb-4">Mulai persiapan pernikahanmu</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
                    <button @click="openCreate"
                            class="px-4 py-2 rounded-xl text-sm font-medium text-white"
                            style="background-color: #92A89C">
                        Tambah task
                    </button>
                </div>
            </div>

            <!-- ── Empty state (filtered, has tasks) ─────────────── -->
            <div v-else-if="groups.length === 0" class="py-12 text-center">
                <p class="text-stone-400 text-sm">Tidak ada task yang cocok dengan filter ini.</p>
            </div>

            <!-- ── Category groups ────────────────────────────────── -->
            <div v-else class="space-y-1">
                <div v-for="group in groups" :key="group.cat">

                    <!-- Group header -->
                    <button
                        class="w-full flex items-center gap-2.5 py-2.5 px-1 text-left select-none hover:bg-stone-50 rounded-lg transition-colors"
                        @click="toggleGroup(group.cat)"
                    >
                        <!-- Chevron -->
                        <svg class="w-4 h-4 text-stone-400 flex-shrink-0 transition-transform duration-200"
                             :class="collapsedGroups.has(group.cat) ? '' : 'rotate-90'"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>

                        <!-- Category name -->
                        <span class="text-sm font-semibold text-stone-700">{{ group.label }}</span>

                        <!-- Count -->
                        <span class="text-xs text-stone-400">{{ group.done }}/{{ group.total }}</span>

                        <!-- Mini progress bar -->
                        <div class="flex-1 h-1.5 rounded-full bg-stone-100 overflow-hidden max-w-24">
                            <div class="h-full rounded-full transition-all duration-500"
                                 style="background-color: #92A89C"
                                 :style="{ width: group.progress + '%' }"/>
                        </div>

                        <!-- Collapsed count -->
                        <span v-if="collapsedGroups.has(group.cat)"
                              class="text-xs text-stone-400 ml-auto">
                            {{ group.tasks.length }} task
                        </span>
                    </button>

                    <!-- Task list -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div v-show="!collapsedGroups.has(group.cat)" class="space-y-1.5 pb-3 pl-6">

                            <div v-for="task in group.tasks" :key="task.id"
                                 class="relative overflow-hidden rounded-xl"
                                 @click.self="closeSwipe(task.id)">

                                <!-- Swipe action backdrop (mobile) -->
                                <div v-if="task.status !== 'archived'"
                                     class="absolute right-0 top-0 h-full flex items-stretch">
                                    <button @click="toggle(task)"
                                            class="h-full px-4 flex flex-col items-center justify-center gap-0.5 text-xs font-medium text-green-700 bg-green-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Selesai
                                    </button>
                                    <button @click="openEdit(task)"
                                            class="h-full px-4 flex flex-col items-center justify-center gap-0.5 text-xs font-medium text-stone-600 bg-stone-100">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </button>
                                    <button @click="deleteTask(task)"
                                            class="h-full px-4 flex flex-col items-center justify-center gap-0.5 text-xs font-medium text-red-600 bg-red-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Hapus
                                    </button>
                                </div>

                                <!-- Task content (slides left on swipe) -->
                                <div
                                    class="relative z-10 bg-white border rounded-xl px-3 py-3 flex items-start gap-0 transition-opacity"
                                    :class="task.status === 'done' || task.status === 'archived' ? 'opacity-60' : ''"
                                    :style="{
                                        transform: `translateX(${getSwipe(task.id).offsetX}px)`,
                                        transition: getSwipe(task.id).dragging ? 'none' : 'transform 0.2s ease',
                                        borderColor: selectedIds.has(task.id) ? '#92A89C' : '',
                                    }"
                                    @touchstart.passive="onSwipeStart(task, $event); onLongPressStart(task)"
                                    @touchmove.passive="onSwipeMove(task, $event); onLongPressCancel()"
                                    @touchend="onSwipeEnd(task); onLongPressCancel()"
                                >
                                    <!-- Checkbox (44x44px touch target) -->
                                    <button
                                        class="flex-shrink-0 flex items-center justify-center w-11 h-11 -ml-1.5 -mt-0.5"
                                        @click.stop="bulkMode ? toggleSelect(task) : toggle(task)"
                                        :disabled="togglingId === task.id && !bulkMode"
                                    >
                                        <!-- Bulk select -->
                                        <div v-if="bulkMode"
                                             class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                             :class="selectedIds.has(task.id)
                                                 ? 'border-[#92A89C] bg-[#92A89C]/100'
                                                 : 'border-stone-300'">
                                            <svg v-if="selectedIds.has(task.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <!-- Archived icon -->
                                        <div v-else-if="task.status === 'archived'"
                                             class="w-5 h-5 rounded-full border-2 border-stone-200 flex items-center justify-center">
                                            <svg class="w-3 h-3 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
                                            </svg>
                                        </div>
                                        <!-- Todo / Done circle -->
                                        <div v-else
                                             class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                             :class="task.status === 'done'
                                                 ? 'border-green-500 bg-green-500'
                                                 : 'border-stone-300 hover:border-[#92A89C]'">
                                            <svg v-if="task.status === 'done'" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0 ml-0.5">
                                        <p class="text-sm font-medium text-stone-800 leading-snug line-clamp-2"
                                           :class="task.status === 'done' ? 'line-through text-stone-400' : ''">
                                            {{ task.title }}
                                        </p>
                                        <p v-if="task.description"
                                           class="text-xs text-stone-400 mt-0.5 truncate">
                                            {{ task.description }}
                                        </p>

                                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                            <!-- Priority dot + label -->
                                            <div class="flex items-center gap-1">
                                                <div class="w-2 h-2 rounded-full flex-shrink-0"
                                                     :class="priorityConfig[task.priority]?.dotClass"/>
                                                <span class="text-xs"
                                                      :class="priorityConfig[task.priority]?.textClass">
                                                    {{ priorityConfig[task.priority]?.label }}
                                                </span>
                                            </div>

                                            <!-- Due date -->
                                            <span v-if="task.due_date && task.status !== 'archived'"
                                                  class="text-xs px-1.5 py-0.5 rounded-full"
                                                  :class="task.status !== 'done' && isOverdue(task.due_date)
                                                      ? 'bg-red-50 text-red-500'
                                                      : task.status !== 'done' && isDueSoon(task.due_date)
                                                          ? 'bg-[#92A89C]/10 text-[#73877C]'
                                                          : 'bg-stone-50 text-stone-400'">
                                                {{ formatDate(task.due_date) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Desktop actions -->
                                    <div class="hidden sm:flex items-center gap-0.5 flex-shrink-0 ml-2">
                                        <template v-if="task.status !== 'archived'">
                                            <button @click.stop="openEdit(task)"
                                                    class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors"
                                                    title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button @click.stop="archiveTask(task)"
                                                    class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors"
                                                    title="Arsipkan">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
                                                </svg>
                                            </button>
                                            <button @click.stop="deleteTask(task)"
                                                    class="p-1.5 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                                    title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click.stop="restoreTask(task)"
                                                    class="text-xs px-2.5 py-1 rounded-lg text-[#73877C] bg-[#92A89C]/10 hover:bg-[#92A89C]/20 transition-colors">
                                                Pulihkan
                                            </button>
                                        </template>
                                    </div>

                                    <!-- Mobile: archived restore -->
                                    <div v-if="task.status === 'archived'" class="flex sm:hidden items-center ml-2">
                                        <button @click.stop="restoreTask(task)"
                                                class="text-xs px-2.5 py-1 rounded-lg text-[#73877C] bg-[#92A89C]/10">
                                            Pulihkan
                                        </button>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </Transition>
                </div>
            </div>

        </template>

        <!-- ── FAB mobile ─────────────────────────────────────────── -->
        <button @click="openCreate"
                class="fixed bottom-6 right-6 lg:hidden w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white z-20"
                style="background-color: #92A89C">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>

        <!-- ── Task Form Modal ────────────────────────────────────── -->
        <Transition name="fade">
            <div v-if="showForm"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 px-4"
                 @click.self="closeForm">
                <div class="bg-white w-full max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl p-6">
                    <h2 class="text-base font-semibold text-stone-800 mb-4">
                        {{ editingTask ? 'Edit Task' : 'Tambah Task Baru' }}
                    </h2>

                    <div class="space-y-3">
                        <!-- Title -->
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Nama task <span class="text-red-400">*</span></label>
                            <input v-model="form.title" type="text" placeholder="Contoh: Booking fotografer"
                                   class="w-full border rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30"
                                   :class="formError.title ? 'border-red-300' : 'border-stone-200'"/>
                            <p v-if="formError.title" class="text-xs text-red-500 mt-1">{{ formError.title }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Category -->
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Kategori <span class="text-red-400">*</span></label>
                                <select v-if="!usingCustomCategory"
                                        v-model="form.category"
                                        class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30">
                                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                                <input v-else
                                       v-model="customCategory"
                                       type="text"
                                       placeholder="Nama kategori"
                                       class="w-full border rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30"
                                       :class="formError.category ? 'border-red-300' : 'border-stone-200'"/>
                                <button @click="usingCustomCategory = !usingCustomCategory"
                                        class="mt-1 text-xs text-[#73877C] hover:underline">
                                    {{ usingCustomCategory ? '← Pilih dari daftar' : '+ Kategori lain' }}
                                </button>
                                <p v-if="formError.category" class="text-xs text-red-500 mt-1">{{ formError.category }}</p>
                            </div>
                            <!-- Priority -->
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Prioritas</label>
                                <select v-model="form.priority"
                                        class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30">
                                    <option value="low">Rendah</option>
                                    <option value="medium">Sedang</option>
                                    <option value="high">Tinggi</option>
                                </select>
                            </div>
                        </div>

                        <!-- Due date -->
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Tenggat (opsional)</label>
                            <input v-model="form.due_date" type="date"
                                   class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30"/>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Catatan (opsional)</label>
                            <textarea v-model="form.description" rows="2" placeholder="Tambahkan catatan…"
                                      class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 resize-none focus:outline-none focus:ring-2 focus:ring-[#92A89C]/30"/>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-5">
                        <button @click="closeForm"
                                class="flex-1 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                            Batal
                        </button>
                        <button @click="saveForm" :disabled="saving"
                                class="flex-1 py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                                style="background-color: #92A89C">
                            {{ saving ? 'Menyimpan…' : (editingTask ? 'Simpan' : 'Tambah') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </DashboardLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
.line-clamp-2 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
</style>
