<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    weddingPlan: Object,
});

// ── State ──────────────────────────────────────────────────────────────────
const tasks        = ref([]);
const summary      = ref({ total: 0, todo: 0, done: 0, archived: 0, progress: 0, has_event_date: false });
const loading      = ref(true);
const error        = ref(null);
const filterStatus = ref('');
const filterCat    = ref('');
const eventDate    = ref('');
const savingDate   = ref(false);
const eventDateError = ref('');
const showForm     = ref(false);
const editingTask  = ref(null);
const togglingId   = ref(null);

// ── Form ───────────────────────────────────────────────────────────────────
const emptyForm = () => ({ title: '', category: 'lainnya', priority: 'medium', due_date: '', description: '' });
const form      = ref(emptyForm());
const formError = ref({});
const saving    = ref(false);

// ── Categories ─────────────────────────────────────────────────────────────
const categories = [
    { value: 'administrasi', label: 'Administrasi' },
    { value: 'venue',        label: 'Venue' },
    { value: 'vendor',       label: 'Vendor' },
    { value: 'busana',       label: 'Busana' },
    { value: 'undangan',     label: 'Undangan' },
    { value: 'tamu',         label: 'Tamu' },
    { value: 'acara',        label: 'Acara' },
    { value: 'dokumentasi',  label: 'Dokumentasi' },
    { value: 'lainnya',      label: 'Lainnya' },
];

const categoryLabel = (val) => categories.find(c => c.value === val)?.label ?? val;

const priorityConfig = {
    high:   { label: 'Tinggi',  class: 'bg-red-50 text-red-600' },
    medium: { label: 'Sedang',  class: 'bg-amber-50 text-amber-600' },
    low:    { label: 'Rendah',  class: 'bg-stone-100 text-stone-500' },
};

// ── Computed ───────────────────────────────────────────────────────────────
const activeTasks   = computed(() => tasks.value.filter(t => t.status !== 'archived'));
const archivedTasks = computed(() => tasks.value.filter(t => t.status === 'archived'));

const filtered = computed(() => {
    let list = filterStatus.value === 'archived' ? archivedTasks.value : activeTasks.value;
    if (filterStatus.value && filterStatus.value !== 'archived') {
        list = list.filter(t => t.status === filterStatus.value);
    }
    if (filterCat.value) {
        list = list.filter(t => t.category === filterCat.value);
    }
    return list;
});

// ── Bootstrap ──────────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        if (!props.weddingPlan?.initialized) {
            await axios.post(route('dashboard.checklist.initialize'));
        }
        await Promise.all([loadTasks(), loadSummary()]);
    } catch (e) {
        error.value = 'Gagal memuat checklist. Silakan muat ulang halaman.';
    } finally {
        loading.value = false;
    }
});

async function loadTasks() {
    const params = {};
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterCat.value)    params.category = filterCat.value;
    const { data } = await axios.get(route('dashboard.checklist.tasks'), { params });
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

async function applyFilter() {
    await loadTasks();
}

// ── Toggle ─────────────────────────────────────────────────────────────────
async function toggle(task) {
    if (togglingId.value) return;
    const prev = task.status;
    task.status = task.status === 'done' ? 'todo' : 'done'; // optimistic
    togglingId.value = task.id;
    try {
        const { data } = await axios.patch(route('dashboard.checklist.tasks.toggle', task.id));
        Object.assign(task, data);
        await loadSummary();
    } catch {
        task.status = prev; // rollback
    } finally {
        togglingId.value = null;
    }
}

// ── Archive / Restore ──────────────────────────────────────────────────────
async function archiveTask(task) {
    await axios.patch(route('dashboard.checklist.tasks.archive', task.id));
    await Promise.all([loadTasks(), loadSummary()]);
}

async function restoreTask(task) {
    await axios.patch(route('dashboard.checklist.tasks.restore', task.id));
    filterStatus.value = '';
    await Promise.all([loadTasks(), loadSummary()]);
}

// ── Create / Edit ──────────────────────────────────────────────────────────
function openCreate() {
    editingTask.value = null;
    form.value = emptyForm();
    formError.value = {};
    showForm.value = true;
}

function openEdit(task) {
    editingTask.value = task;
    form.value = {
        title:       task.title,
        category:    task.category,
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
    try {
        const payload = { ...form.value, due_date: form.value.due_date || null };
        if (editingTask.value) {
            await axios.patch(route('dashboard.checklist.tasks.update', editingTask.value.id), payload);
        } else {
            await axios.post(route('dashboard.checklist.tasks.store'), payload);
        }
        closeForm();
        await Promise.all([loadTasks(), loadSummary()]);
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

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-24">
            <div class="w-8 h-8 rounded-full border-2 border-amber-300 border-t-amber-600 animate-spin"/>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex flex-col items-center py-24 gap-3">
            <p class="text-stone-500 text-sm">{{ error }}</p>
            <button @click="() => { error = null; loading = true; onMounted(); }"
                    class="text-sm text-amber-700 underline">Coba lagi</button>
        </div>

        <template v-else>

            <!-- ── Summary cards ──────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Progress</p>
                    <p class="text-2xl font-bold text-stone-800">{{ summary.progress }}<span class="text-sm font-normal text-stone-400">%</span></p>
                    <div class="mt-2 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                             style="background-color: #D4A373"
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
                    <p class="text-2xl font-bold text-amber-600">{{ summary.todo }}</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Diarsipkan</p>
                    <p class="text-2xl font-bold text-stone-400">{{ summary.archived }}</p>
                </div>
            </div>

            <!-- No event date prompt -->
            <div v-if="!summary.has_event_date"
                 class="mb-4 px-4 py-3 rounded-xl bg-amber-50 border border-amber-100 text-sm text-amber-700">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Lengkapi tanggal acara agar tenggat waktu task bisa dihitung otomatis.</span>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="eventDate" type="date"
                           class="flex-1 border rounded-lg px-3 py-1.5 text-sm text-stone-800 bg-white focus:outline-none focus:ring-2 focus:ring-amber-300"
                           :class="eventDateError ? 'border-red-300' : 'border-amber-200'"/>
                    <button @click="saveEventDate" :disabled="!eventDate || savingDate"
                            class="px-4 py-1.5 rounded-lg text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-40"
                            style="background-color: #D4A373">
                        {{ savingDate ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
                <p v-if="eventDateError" class="mt-1.5 text-xs text-red-500">{{ eventDateError }}</p>
            </div>

            <!-- ── Filters + Add button ───────────────────────────── -->
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <select v-model="filterStatus" @change="applyFilter"
                        class="text-sm border border-stone-200 rounded-lg pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="">Semua status</option>
                    <option value="todo">Belum selesai</option>
                    <option value="done">Selesai</option>
                    <option value="archived">Diarsipkan</option>
                </select>

                <select v-model="filterCat" @change="applyFilter"
                        class="text-sm border border-stone-200 rounded-lg pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="">Semua kategori</option>
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>

                <button @click="openCreate"
                        class="ml-auto flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90"
                        style="background-color: #D4A373">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Task
                </button>
            </div>

            <!-- ── Task list ──────────────────────────────────────── -->
            <div v-if="filtered.length === 0" class="py-16 text-center">
                <p class="text-stone-400 text-sm">Tidak ada task di sini.</p>
                <button v-if="!showForm" @click="openCreate"
                        class="mt-3 text-sm text-amber-700 underline">Tambah task baru</button>
            </div>

            <div v-else class="space-y-2">
                <div v-for="task in filtered" :key="task.id"
                     class="bg-white border rounded-xl px-4 py-3 flex items-start gap-3 transition-opacity"
                     :class="task.status === 'archived' ? 'opacity-60 border-stone-100' : 'border-stone-100 hover:border-stone-200'">

                    <!-- Checkbox -->
                    <button v-if="task.status !== 'archived'"
                            @click="toggle(task)"
                            :disabled="togglingId === task.id"
                            class="mt-0.5 w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-colors"
                            :class="task.status === 'done'
                                ? 'border-green-500 bg-green-500'
                                : 'border-stone-300 hover:border-amber-400'">
                        <svg v-if="task.status === 'done'" class="w-3 h-3 text-white" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <!-- Archived icon -->
                    <div v-else class="mt-0.5 w-5 h-5 rounded-full border-2 border-stone-200 flex-shrink-0 flex items-center justify-center">
                        <svg class="w-3 h-3 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-stone-800 leading-snug"
                           :class="task.status === 'done' ? 'line-through text-stone-400' : ''">
                            {{ task.title }}
                        </p>
                        <p v-if="task.description" class="text-xs text-stone-400 mt-0.5 truncate">{{ task.description }}</p>

                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                            <!-- Category -->
                            <span class="text-xs px-2 py-0.5 rounded-full bg-stone-100 text-stone-500">
                                {{ categoryLabel(task.category) }}
                            </span>
                            <!-- Priority -->
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="priorityConfig[task.priority]?.class">
                                {{ priorityConfig[task.priority]?.label }}
                            </span>
                            <!-- Due date -->
                            <span v-if="task.due_date && task.status !== 'archived'"
                                  class="text-xs px-2 py-0.5 rounded-full"
                                  :class="task.status !== 'done' && isOverdue(task.due_date)
                                      ? 'bg-red-50 text-red-500'
                                      : task.status !== 'done' && isDueSoon(task.due_date)
                                          ? 'bg-amber-50 text-amber-600'
                                          : 'bg-stone-50 text-stone-400'">
                                {{ formatDate(task.due_date) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <template v-if="task.status !== 'archived'">
                            <button @click="openEdit(task)"
                                    class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors"
                                    title="Edit">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button @click="archiveTask(task)"
                                    class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors"
                                    title="Arsipkan">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
                                </svg>
                            </button>
                        </template>
                        <template v-else>
                            <button @click="restoreTask(task)"
                                    class="text-xs px-2.5 py-1 rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors">
                                Pulihkan
                            </button>
                        </template>
                    </div>
                </div>
            </div>

        </template>

        <!-- ── FAB mobile ─────────────────────────────────────────── -->
        <button @click="openCreate"
                class="fixed bottom-6 right-6 lg:hidden w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white z-20"
                style="background-color: #D4A373">
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
                                   class="w-full border rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                   :class="formError.title ? 'border-red-300' : 'border-stone-200'"/>
                            <p v-if="formError.title" class="text-xs text-red-500 mt-1">{{ formError.title }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Category -->
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Kategori <span class="text-red-400">*</span></label>
                                <select v-model="form.category"
                                        class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200">
                                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                </select>
                            </div>
                            <!-- Priority -->
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Prioritas</label>
                                <select v-model="form.priority"
                                        class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200">
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
                                   class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Catatan (opsional)</label>
                            <textarea v-model="form.description" rows="2" placeholder="Tambahkan catatan..."
                                      class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm text-stone-800 resize-none focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-5">
                        <button @click="closeForm"
                                class="flex-1 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                            Batal
                        </button>
                        <button @click="saveForm" :disabled="saving"
                                class="flex-1 py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                                style="background-color: #D4A373">
                            {{ saving ? 'Menyimpan...' : (editingTask ? 'Simpan' : 'Tambah') }}
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
</style>
