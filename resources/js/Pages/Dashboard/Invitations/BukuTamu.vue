<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    invitation: { type: Object, required: true },
});

// ─── State ────────────────────────────────────────────────────
const messages    = ref([]);
const meta        = ref({ current_page: 1, last_page: 1, total: 0 });
const summary     = ref({ total: 0, visible: 0, hidden: 0, pinned: 0 });
const loading     = ref(false);
const loadingMore = ref(false);

const search    = ref('');
const filter    = ref('all');
const sort      = ref('newest');

const selectedIds      = ref([]);
const showDeleteConfirm = ref(false);
const deleteTarget     = ref(null); // null = bulk, else single message
const expandedIds      = ref(new Set());

let searchTimer = null;

// ─── Fetch ────────────────────────────────────────────────────
async function fetchMessages(page = 1, append = false) {
    if (append) loadingMore.value = true;
    else loading.value = true;

    try {
        const res = await axios.get(route('dashboard.invitations.messages.list', props.invitation.id), {
            params: { filter: filter.value, sort: sort.value, search: search.value, page, per_page: 30 },
        });
        if (append) {
            messages.value.push(...res.data.data);
        } else {
            messages.value = res.data.data;
            selectedIds.value = [];
        }
        meta.value = res.data.meta;
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

async function fetchSummary() {
    const res = await axios.get(route('dashboard.invitations.messages.summary', props.invitation.id));
    summary.value = res.data;
}

function refresh() {
    fetchMessages(1);
    fetchSummary();
}

onMounted(() => refresh());

watch([filter, sort], () => fetchMessages(1));
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchMessages(1), 300);
});

// ─── Selection ────────────────────────────────────────────────
const allSelected = computed(() =>
    messages.value.length > 0 && messages.value.every(m => selectedIds.value.includes(m.id))
);

function toggleSelectAll() {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = messages.value.map(m => m.id);
    }
}

function toggleSelect(id) {
    const i = selectedIds.value.indexOf(id);
    if (i === -1) selectedIds.value.push(id);
    else selectedIds.value.splice(i, 1);
}

// ─── Actions ─────────────────────────────────────────────────
async function togglePin(msg) {
    const res = await axios.patch(
        route('dashboard.invitations.messages.update', [props.invitation.id, msg.id]),
        { is_pinned: !msg.is_pinned }
    );
    Object.assign(msg, res.data);
    fetchSummary();
}

async function toggleHide(msg) {
    const res = await axios.patch(
        route('dashboard.invitations.messages.update', [props.invitation.id, msg.id]),
        { is_hidden: !msg.is_hidden }
    );
    Object.assign(msg, res.data);
    fetchSummary();
}

function confirmDelete(msg = null) {
    deleteTarget.value = msg;
    showDeleteConfirm.value = true;
}

async function executeDelete() {
    if (deleteTarget.value) {
        // Single delete
        await axios.delete(
            route('dashboard.invitations.messages.destroy', [props.invitation.id, deleteTarget.value.id])
        );
    } else {
        // Bulk delete
        await axios.post(
            route('dashboard.invitations.messages.bulk', props.invitation.id),
            { ids: selectedIds.value, action: 'delete' }
        );
        selectedIds.value = [];
    }
    showDeleteConfirm.value = false;
    deleteTarget.value = null;
    refresh();
}

async function bulkAction(action) {
    if (!selectedIds.value.length) return;
    if (action === 'delete') {
        confirmDelete(null);
        return;
    }
    await axios.post(
        route('dashboard.invitations.messages.bulk', props.invitation.id),
        { ids: selectedIds.value, action }
    );
    selectedIds.value = [];
    refresh();
}

function loadMore() {
    if (meta.value.current_page < meta.value.last_page) {
        fetchMessages(meta.value.current_page + 1, true);
    }
}

function toggleExpand(id) {
    if (expandedIds.value.has(id)) expandedIds.value.delete(id);
    else expandedIds.value.add(id);
}

// ─── Helpers ─────────────────────────────────────────────────
function relativeTime(iso) {
    if (!iso) return '';
    const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (diff < 60)    return 'Baru saja';
    if (diff < 3600)  return `${Math.floor(diff / 60)} mnt lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    if (diff < 172800) return 'Kemarin';
    if (diff < 2592000) return `${Math.floor(diff / 86400)} hari lalu`;
    return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function exportUrl(scope = 'all') {
    return route('dashboard.invitations.messages.export', props.invitation.id) + `?scope=${scope}`;
}
</script>

<template>
    <Head :title="`Buku Tamu — ${invitation.title}`" />

    <DashboardLayout>
        <template #header>
            <div class="flex items-center gap-3 w-full">
                <Link
                    :href="route('dashboard.invitations.index')"
                    class="p-2 rounded-xl text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors flex-shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-stone-800 truncate">Buku Tamu</h2>
                    <p class="text-sm text-stone-400 truncate">{{ invitation.title }}</p>
                </div>

                <!-- Export dropdown -->
                <div class="ml-auto relative group flex-shrink-0">
                    <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border border-stone-200 text-stone-600 hover:bg-stone-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                    <div class="absolute right-0 mt-1 w-48 bg-white border border-stone-100 rounded-xl shadow-lg py-1 z-20 hidden group-hover:block">
                        <a :href="exportUrl('all')"    class="block px-4 py-2 text-sm text-stone-700 hover:bg-stone-50">Semua Ucapan</a>
                        <a :href="exportUrl('visible')" class="block px-4 py-2 text-sm text-stone-700 hover:bg-stone-50">Hanya Tampil</a>
                        <a :href="exportUrl('pinned')"  class="block px-4 py-2 text-sm text-stone-700 hover:bg-stone-50">Hanya Dipinned</a>
                    </div>
                </div>
            </div>
        </template>

        <!-- ── Stats ───────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <button
                v-for="stat in [
                    { key: 'all',     label: 'Total',         value: summary.total,   color: 'stone',  icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
                    { key: 'visible', label: 'Tampil',        value: summary.visible, color: 'emerald',icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
                    { key: 'hidden',  label: 'Disembunyikan', value: summary.hidden,  color: 'amber',  icon: 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21' },
                    { key: 'pinned',  label: 'Dipinned',      value: summary.pinned,  color: 'violet', icon: 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z' },
                ]"
                :key="stat.key"
                @click="filter = stat.key"
                class="bg-white rounded-2xl border p-3 sm:p-4 text-left transition-all hover:-translate-y-0.5"
                :class="filter === stat.key
                    ? `border-${stat.color}-300 ring-2 ring-${stat.color}-200`
                    : 'border-stone-100 hover:border-stone-200'"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-stone-500">{{ stat.label }}</span>
                    <div class="w-6 h-6 rounded-lg flex items-center justify-center"
                         :class="`bg-${stat.color}-50`">
                        <svg class="w-3.5 h-3.5" :class="`text-${stat.color}-500`" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-stone-800">{{ stat.value.toLocaleString('id-ID') }}</p>
            </button>
        </div>

        <!-- ── Toolbar ─────────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-stone-100 p-3 mb-3 flex flex-col sm:flex-row gap-2">
            <!-- Search -->
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama atau ucapan…"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition"
                />
            </div>

            <!-- Filter pills -->
            <div class="grid grid-cols-2 sm:flex gap-1.5">
                <button
                    v-for="f in [
                        { value: 'all',     label: 'Semua' },
                        { value: 'visible', label: 'Tampil' },
                        { value: 'hidden',  label: 'Disembunyikan' },
                        { value: 'pinned',  label: 'Dipinned' },
                    ]"
                    :key="f.value"
                    @click="filter = f.value"
                    class="px-3 py-2 rounded-xl text-xs font-medium transition-colors text-center"
                    :class="filter === f.value
                        ? 'bg-amber-100 text-amber-800 border border-amber-200'
                        : 'bg-stone-50 text-stone-600 border border-stone-100 hover:bg-stone-100'"
                >
                    {{ f.label }}
                </button>
            </div>

            <!-- Sort -->
            <select
                v-model="sort"
                class="px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-600 focus:outline-none focus:ring-2 focus:ring-amber-300 bg-white"
            >
                <option value="newest">Terbaru</option>
                <option value="oldest">Terlama</option>
                <option value="pinned">Pinned dulu</option>
            </select>
        </div>

        <!-- ── Bulk action bar ─────────────────────────────────────────── -->
        <Transition
            enter-from-class="opacity-0 -translate-y-2"
            enter-active-class="transition-all duration-200"
            leave-to-class="opacity-0 -translate-y-2"
            leave-active-class="transition-all duration-150"
        >
            <div v-if="selectedIds.length" class="bg-amber-50 border border-amber-200 rounded-2xl p-3 mb-3 flex items-center gap-3 flex-wrap">
                <span class="text-sm font-medium text-amber-800">{{ selectedIds.length }} dipilih</span>
                <div class="flex gap-2 flex-wrap">
                    <button @click="bulkAction('hide')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-stone-200 text-stone-700 hover:bg-stone-50 transition-colors">
                        Sembunyikan
                    </button>
                    <button @click="bulkAction('show')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-stone-200 text-stone-700 hover:bg-stone-50 transition-colors">
                        Tampilkan
                    </button>
                    <button @click="bulkAction('unpin')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-stone-200 text-stone-700 hover:bg-stone-50 transition-colors">
                        Unpin
                    </button>
                    <button @click="bulkAction('delete')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 border border-red-100 text-red-600 hover:bg-red-100 transition-colors">
                        Hapus
                    </button>
                </div>
                <button @click="selectedIds = []" class="ml-auto p-1 text-stone-400 hover:text-stone-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- ── Loading skeleton ────────────────────────────────────────── -->
        <div v-if="loading" class="space-y-3">
            <div v-for="i in 5" :key="i" class="bg-white rounded-2xl border border-stone-100 p-4 animate-pulse">
                <div class="flex gap-3">
                    <div class="w-9 h-9 rounded-full bg-stone-100 flex-shrink-0"/>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 bg-stone-100 rounded w-1/3"/>
                        <div class="h-3 bg-stone-100 rounded w-3/4"/>
                        <div class="h-3 bg-stone-100 rounded w-1/2"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Empty state ──────────────────────────────────────────────── -->
        <div v-else-if="!messages.length"
             class="bg-white rounded-2xl border border-dashed border-stone-200 p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-stone-600 mb-1">
                {{ search || filter !== 'all' ? 'Tidak ada ucapan ditemukan' : 'Belum ada ucapan yang masuk' }}
            </p>
            <p class="text-xs text-stone-400">
                {{ search || filter !== 'all'
                    ? 'Coba ubah filter atau kata kunci pencarian'
                    : 'Ucapan tamu akan muncul di sini setelah mereka membuka undangan' }}
            </p>
        </div>

        <!-- ── Message list ─────────────────────────────────────────────── -->
        <div v-else class="space-y-2">
            <!-- Select all header -->
            <div class="flex items-center gap-3 px-1 mb-1">
                <input
                    type="checkbox"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                    class="w-4 h-4 rounded border-stone-300 text-amber-500 focus:ring-amber-300"
                />
                <span class="text-xs text-stone-400">Pilih semua ({{ messages.length }})</span>
            </div>

            <div
                v-for="msg in messages"
                :key="msg.id"
                class="bg-white rounded-2xl border transition-all"
                :class="[
                    selectedIds.includes(msg.id) ? 'border-amber-200 bg-amber-50/30' : 'border-stone-100',
                    msg.is_hidden ? 'opacity-60' : '',
                ]"
            >
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <!-- Checkbox -->
                        <input
                            type="checkbox"
                            :value="msg.id"
                            v-model="selectedIds"
                            class="mt-1 w-4 h-4 rounded border-stone-300 text-amber-500 focus:ring-amber-300 flex-shrink-0"
                        />

                        <!-- Avatar -->
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-semibold flex-shrink-0 bg-amber-400">
                            {{ msg.display_name.charAt(0).toUpperCase() }}
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <p class="text-sm font-semibold text-stone-800">{{ msg.display_name }}</p>
                                <p v-if="msg.is_anonymous" class="text-xs text-stone-400">({{ msg.name }})</p>

                                <!-- Badges -->
                                <span v-if="msg.is_pinned"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-700">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    Pinned
                                </span>
                                <span v-if="msg.is_hidden"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-500">
                                    Tersembunyi
                                </span>

                                <span class="text-xs text-stone-400 ml-auto flex-shrink-0">{{ relativeTime(msg.created_at) }}</span>
                            </div>

                            <!-- Message text -->
                            <p class="text-sm text-stone-600 leading-relaxed"
                               :class="!expandedIds.has(msg.id) ? 'line-clamp-3' : ''">
                                {{ msg.message }}
                            </p>
                            <button
                                v-if="msg.message.length > 150"
                                @click="toggleExpand(msg.id)"
                                class="text-xs text-amber-600 hover:text-amber-700 mt-1 font-medium"
                            >
                                {{ expandedIds.has(msg.id) ? 'Sembunyikan' : 'Lihat selengkapnya' }}
                            </button>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 mt-3 flex-wrap">
                                <!-- Pin -->
                                <button
                                    @click="togglePin(msg)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors border"
                                    :class="msg.is_pinned
                                        ? 'bg-violet-50 border-violet-100 text-violet-700 hover:bg-violet-100'
                                        : 'bg-stone-50 border-stone-100 text-stone-600 hover:bg-stone-100'"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    {{ msg.is_pinned ? 'Unpin' : 'Pin' }}
                                </button>

                                <!-- Hide/Show -->
                                <button
                                    @click="toggleHide(msg)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors border"
                                    :class="msg.is_hidden
                                        ? 'bg-emerald-50 border-emerald-100 text-emerald-700 hover:bg-emerald-100'
                                        : 'bg-stone-50 border-stone-100 text-stone-600 hover:bg-stone-100'"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path v-if="msg.is_hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                    {{ msg.is_hidden ? 'Tampilkan' : 'Sembunyikan' }}
                                </button>

                                <!-- Delete -->
                                <button
                                    @click="confirmDelete(msg)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border bg-stone-50 border-red-100 text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors ml-auto"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Load more -->
            <div v-if="meta.current_page < meta.last_page" class="pt-2 text-center">
                <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="px-6 py-2.5 rounded-xl text-sm font-medium border border-stone-200 text-stone-600 hover:bg-stone-50 transition-colors disabled:opacity-60"
                >
                    {{ loadingMore ? 'Memuat…' : `Muat lebih banyak (${meta.total - messages.length} lagi)` }}
                </button>
            </div>
        </div>

        <!-- ── Delete confirm modal ─────────────────────────────────────── -->
        <Transition
            enter-from-class="opacity-0"
            enter-active-class="transition-opacity duration-200"
            leave-to-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
        >
            <div v-if="showDeleteConfirm"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                 @click.self="showDeleteConfirm = false">
                <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-stone-800 text-center mb-1">Hapus Ucapan?</h3>
                    <p class="text-sm text-stone-500 text-center mb-6">
                        {{ deleteTarget
                            ? `Ucapan dari "${deleteTarget.display_name}" akan dihapus permanen.`
                            : `${selectedIds.length} ucapan akan dihapus permanen.` }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            @click="showDeleteConfirm = false"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-stone-200 text-stone-600 hover:bg-stone-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            @click="executeDelete"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium bg-red-500 text-white hover:bg-red-600 transition-colors"
                        >
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </DashboardLayout>
</template>
