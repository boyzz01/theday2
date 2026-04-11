<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    invitations:     { type: Array,  default: () => [] },
    hasTemplate:     { type: Boolean, default: false },
    defaultTemplate: { type: Object,  default: null },
});

// ── State ──────────────────────────────────────────────────────────────────
const guests      = ref([]);
const guestsMeta  = ref({ total: 0, current_page: 1, last_page: 1, per_page: 20 });
const summary     = ref({ total: 0, not_sent: 0, sent: 0, opened: 0, attending: 0, not_attending: 0, pending_rsvp: 0 });
const categories  = ref([]);
const loading     = ref(true);
const loadingGuests = ref(false);
const error       = ref(null);

// ── Filters ────────────────────────────────────────────────────────────────
const search      = ref('');
const filters     = reactive({ send_status: '', rsvp_status: '', category: '', invitation_id: '' });
const sort        = ref('newest');
const page        = ref(1);
let   searchTimer = null;

// ── Modals ─────────────────────────────────────────────────────────────────
const showGuestForm   = ref(false);
const editingGuest    = ref(null);
const showTemplateModal = ref(false);
const showSendModal   = ref(false);
const showImportModal = ref(false);
const showDeleteConfirm = ref(null); // guest to delete

// ── Guest form ─────────────────────────────────────────────────────────────
const emptyGuestForm = () => ({
    name: '', phone_number: '', category: '', greeting: '', note: '', invitation_id: '',
});
const guestForm  = ref(emptyGuestForm());
const guestErrors = ref({});
const savingGuest = ref(false);

// ── Template editor ────────────────────────────────────────────────────────
const templateForm    = reactive({ name: 'Template Default', content: '' });
const templateErrors  = ref({});
const savingTemplate  = ref(false);
const templatePreview = ref('');
const templateWarnings = ref([]);
const previewLoading  = ref(false);

// ── Send WA modal ──────────────────────────────────────────────────────────
const sendTarget      = ref(null);
const generatedMsg    = ref('');
const generatedWaUrl  = ref('');
const generatedPhone  = ref('');
const generating      = ref(false);
const markingSent     = ref(false);

// ── Import modal ───────────────────────────────────────────────────────────
const importMode      = ref('paste');
const importText      = ref('');
const importFile      = ref(null);
const importInvId     = ref('');
const importPreview   = ref([]);
const importPreviewing = ref(false);
const importCommitting = ref(false);
const importResult    = ref(null);
const importStep      = ref('input'); // 'input' | 'preview' | 'done'

// ── Bulk selection ─────────────────────────────────────────────────────────
const selected       = ref([]);
const bulkMarkSentLoading = ref(false);

const allSelected = computed(() =>
    guests.value.length > 0 && selected.value.length === guests.value.length
);

// ── Computed ───────────────────────────────────────────────────────────────
const hasActiveFilter = computed(() =>
    search.value || filters.send_status || filters.rsvp_status ||
    filters.category || filters.invitation_id
);

const suggestedCategories = computed(() => {
    const defaults = ['Keluarga', 'Teman', 'Kantor', 'Tetangga', 'Vendor', 'Lainnya'];
    const custom   = categories.value.filter(c => !defaults.includes(c));
    return [...defaults, ...custom];
});

// ── Bootstrap ──────────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        await Promise.all([loadGuests(), loadSummary(), loadCategories()]);
    } catch (e) {
        error.value = 'Gagal memuat data. Silakan muat ulang halaman.';
    } finally {
        loading.value = false;
    }

    if (props.defaultTemplate) {
        templateForm.name    = props.defaultTemplate.name;
        templateForm.content = props.defaultTemplate.content;
    }
});

// ── Watchers ───────────────────────────────────────────────────────────────
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { page.value = 1; loadGuests(); }, 350);
});

watch([filters, sort], () => { page.value = 1; loadGuests(); }, { deep: true });

// ── Data loaders ───────────────────────────────────────────────────────────
async function loadGuests() {
    loadingGuests.value = true;
    try {
        const params = { page: page.value, per_page: 20, sort: sort.value };
        if (search.value)           params.search       = search.value;
        if (filters.send_status)    params.send_status  = filters.send_status;
        if (filters.rsvp_status)    params.rsvp_status  = filters.rsvp_status;
        if (filters.category)       params.category     = filters.category;
        if (filters.invitation_id)  params.invitation_id = filters.invitation_id;

        const { data } = await axios.get(route('dashboard.guest-list.guests'), { params });
        guests.value   = data.data;
        guestsMeta.value = data.meta;
        selected.value = [];
    } finally {
        loadingGuests.value = false;
    }
}

async function loadSummary() {
    const params = {};
    if (filters.invitation_id) params.invitation_id = filters.invitation_id;
    const { data } = await axios.get(route('dashboard.guest-list.summary'), { params });
    summary.value = data;
}

async function loadCategories() {
    const { data } = await axios.get(route('dashboard.guest-list.categories'));
    categories.value = data.categories;
}

async function changePage(p) {
    page.value = p;
    await loadGuests();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Guest CRUD ─────────────────────────────────────────────────────────────
function openCreate() {
    editingGuest.value = null;
    guestForm.value    = emptyGuestForm();
    guestErrors.value  = {};
    showGuestForm.value = true;
}

function openEdit(guest) {
    editingGuest.value = guest;
    guestForm.value    = {
        name:          guest.name,
        phone_number:  guest.phone_number,
        category:      guest.category ?? '',
        greeting:      guest.greeting ?? '',
        note:          guest.note ?? '',
        invitation_id: guest.invitation?.id ?? '',
    };
    guestErrors.value  = {};
    showGuestForm.value = true;
}

function closeGuestForm() {
    showGuestForm.value = false;
    editingGuest.value  = null;
}

async function saveGuest() {
    guestErrors.value = {};
    savingGuest.value = true;
    try {
        const payload = { ...guestForm.value };
        if (!payload.invitation_id) delete payload.invitation_id;

        if (editingGuest.value) {
            const { data } = await axios.patch(
                route('dashboard.guest-list.update', editingGuest.value.id), payload
            );
            const idx = guests.value.findIndex(g => g.id === editingGuest.value.id);
            if (idx > -1) guests.value[idx] = data;
        } else {
            const { data } = await axios.post(route('dashboard.guest-list.store'), payload);
            guests.value.unshift(data);
        }

        closeGuestForm();
        await Promise.all([loadSummary(), loadCategories()]);
    } catch (e) {
        if (e.response?.status === 422) {
            guestErrors.value = e.response.data.errors ?? {};
        }
    } finally {
        savingGuest.value = false;
    }
}

async function deleteGuest(guest) {
    await axios.delete(route('dashboard.guest-list.destroy', guest.id));
    guests.value = guests.value.filter(g => g.id !== guest.id);
    showDeleteConfirm.value = null;
    await loadSummary();
}

// ── WA Template ────────────────────────────────────────────────────────────
function openTemplateEditor() {
    showTemplateModal.value = true;
    templatePreview.value   = '';
    templateWarnings.value  = [];
    if (!templateForm.content) {
        templateForm.content = "Halo {{guest_name}},\n\nDengan penuh kebahagiaan, kami mengundang Anda untuk hadir di hari spesial kami.\n\nBuka undangannya di sini:\n{{invitation_url}}\n\nTerima kasih atas doa dan kehadirannya.";
    }
}

async function previewTemplate() {
    if (!templateForm.content) return;
    previewLoading.value = true;
    try {
        const { data } = await axios.post(route('dashboard.guest-list.template.preview'), {
            content: templateForm.content,
        });
        templatePreview.value  = data.rendered;
        templateWarnings.value = data.warnings ?? [];
    } catch {
        showToast('Gagal memuat preview. Coba lagi.');
    } finally {
        previewLoading.value = false;
    }
}

async function saveTemplate() {
    templateErrors.value = {};
    savingTemplate.value = true;
    try {
        await axios.put(route('dashboard.guest-list.template.update'), {
            name:    templateForm.name || 'Template Default',
            content: templateForm.content,
        });
        showTemplateModal.value = false;
    } catch (e) {
        if (e.response?.status === 422) {
            templateErrors.value = e.response.data.errors ?? {};
        }
    } finally {
        savingTemplate.value = false;
    }
}

async function resetTemplate() {
    const { data } = await axios.post(route('dashboard.guest-list.template.reset'));
    templateForm.name    = data.template.name;
    templateForm.content = data.template.content;
    templatePreview.value = '';
}

function insertPlaceholder(ph) {
    templateForm.content += `{{${ph}}}`;
}

// ── Send WA ────────────────────────────────────────────────────────────────
async function openSendModal(guest) {
    sendTarget.value    = guest;
    generatedMsg.value  = '';
    generatedWaUrl.value = '';
    showSendModal.value = true;
    generating.value    = true;

    try {
        const { data } = await axios.post(
            route('dashboard.guest-list.message.generate', guest.id)
        );
        generatedMsg.value   = data.message;
        generatedWaUrl.value = data.whatsapp_url;
        generatedPhone.value = data.phone;
    } catch (e) {
        if (e.response?.status === 422) {
            showSendModal.value = false;
            openTemplateEditor();
        }
    } finally {
        generating.value = false;
    }
}

function openWhatsApp() {
    if (generatedWaUrl.value) {
        window.open(generatedWaUrl.value, '_blank');
    }
}

async function confirmSent() {
    if (!sendTarget.value || markingSent.value) return;
    markingSent.value = true;
    try {
        const { data } = await axios.post(
            route('dashboard.guest-list.message.mark-sent', sendTarget.value.id)
        );
        const idx = guests.value.findIndex(g => g.id === sendTarget.value.id);
        if (idx > -1) guests.value[idx] = data;
        await loadSummary();
        showSendModal.value = false;
        showToast('Status diperbarui: Sudah Dikirim');
    } finally {
        markingSent.value = false;
    }
}

async function copyMessage() {
    if (!generatedMsg.value) return;
    try {
        await navigator.clipboard.writeText(generatedMsg.value);
        showToast('Teks undangan berhasil disalin');
        if (sendTarget.value) {
            axios.post(route('dashboard.guest-list.message.copy-log', sendTarget.value.id)).catch(() => {});
        }
    } catch {
        showToast('Gagal menyalin. Salin manual di atas.');
    }
}

// ── Import ─────────────────────────────────────────────────────────────────
function openImport() {
    importStep.value   = 'input';
    importMode.value   = 'paste';
    importText.value   = '';
    importFile.value   = null;
    importInvId.value  = props.invitations[0]?.id ?? '';
    importPreview.value = [];
    importResult.value = null;
    showImportModal.value = true;
}

function onFileChange(e) {
    importFile.value = e.target.files[0] ?? null;
}

async function previewImport() {
    importPreviewing.value = true;
    try {
        const form = new FormData();
        form.append('mode', importMode.value);
        if (importInvId.value) form.append('invitation_id', importInvId.value);
        if (importMode.value === 'paste') {
            form.append('raw_text', importText.value);
        } else if (importFile.value) {
            form.append('file', importFile.value);
        }

        const { data } = await axios.post(
            route('dashboard.guest-list.import.preview'), form,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        importPreview.value = data.rows;
        importStep.value    = 'preview';
    } catch (e) {
        showToast('Gagal memproses data import.');
    } finally {
        importPreviewing.value = false;
    }
}

async function commitImport() {
    const validRows = importPreview.value.filter(r => r.valid);
    if (validRows.length === 0) return;

    importCommitting.value = true;
    try {
        const { data } = await axios.post(route('dashboard.guest-list.import.store'), {
            invitation_id: importInvId.value || null,
            rows: validRows.map(r => ({
                name:     r.name,
                phone:    r.phone,
                category: r.category,
                greeting: r.greeting,
                note:     r.note,
            })),
        });
        importResult.value = data;
        importStep.value   = 'done';
        await Promise.all([loadGuests(), loadSummary(), loadCategories()]);
    } finally {
        importCommitting.value = false;
    }
}

// ── Bulk actions ───────────────────────────────────────────────────────────
function toggleSelectAll() {
    selected.value = allSelected.value ? [] : guests.value.map(g => g.id);
}

function toggleSelect(id) {
    const idx = selected.value.indexOf(id);
    if (idx > -1) selected.value.splice(idx, 1);
    else selected.value.push(id);
}

async function bulkMarkSent() {
    if (selected.value.length === 0 || bulkMarkSentLoading.value) return;
    bulkMarkSentLoading.value = true;
    try {
        await Promise.all(
            selected.value.map(id =>
                axios.post(route('dashboard.guest-list.message.mark-sent', id))
            )
        );
        await Promise.all([loadGuests(), loadSummary()]);
        selected.value = [];
        showToast(`${selected.value.length} tamu ditandai sudah dikirim`);
    } finally {
        bulkMarkSentLoading.value = false;
    }
}

// ── Toast ──────────────────────────────────────────────────────────────────
const toast = ref('');
let toastTimer = null;

function showToast(msg) {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = ''; }, 3000);
}

// ── Helpers ────────────────────────────────────────────────────────────────
function sendStatusBadge(status) {
    return {
        not_sent: { label: 'Belum Kirim',  cls: 'bg-stone-100 text-stone-500' },
        sent:     { label: 'Sudah Kirim',  cls: 'bg-blue-50 text-blue-600' },
        opened:   { label: 'Sudah Dibuka', cls: 'bg-green-50 text-green-600' },
    }[status] ?? { label: status, cls: 'bg-stone-100 text-stone-400' };
}

function rsvpBadge(status) {
    return {
        pending:       { label: 'Belum Respon', cls: 'bg-amber-50 text-amber-600' },
        attending:     { label: 'Hadir',        cls: 'bg-green-50 text-green-700' },
        not_attending: { label: 'Tidak Hadir',  cls: 'bg-red-50 text-red-600' },
    }[status] ?? { label: status, cls: 'bg-stone-100 text-stone-400' };
}

function formatDate(iso) {
    if (!iso) return null;
    return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function phLabel(ph) {
    return '{{' + ph + '}}';
}

function formatPhone(phone) {
    if (!phone) return '';
    const local = '0' + phone.slice(2);
    if (local.length >= 10) {
        return local.slice(0, 4) + '-' + local.slice(4, 8) + '-' + local.slice(8);
    }
    return local;
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-base font-semibold text-stone-800">Guest List</h1>
        </template>

        <!-- ── Loading ───────────────────────────────────────────── -->
        <div v-if="loading" class="flex items-center justify-center py-24">
            <div class="w-8 h-8 rounded-full border-2 border-amber-300 border-t-amber-600 animate-spin"/>
        </div>

        <template v-else>

            <!-- ── Template prompt banner ────────────────────────── -->
            <div v-if="!hasTemplate && guests.length > 0"
                 class="mb-4 flex items-start gap-3 px-4 py-3 rounded-xl bg-amber-50 border border-amber-100 text-sm text-amber-700">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p>Buat template WhatsApp agar pengiriman undangan lebih cepat.</p>
                </div>
                <button @click="openTemplateEditor"
                        class="text-xs font-semibold text-amber-800 underline whitespace-nowrap">
                    Atur Template
                </button>
            </div>

            <!-- ── Summary Cards ─────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Total Tamu</p>
                    <p class="text-2xl font-bold text-stone-800">{{ summary.total }}</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Belum Kirim</p>
                    <p class="text-2xl font-bold text-stone-500">{{ summary.not_sent }}</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Sudah Kirim</p>
                    <p class="text-2xl font-bold text-blue-600">{{ summary.sent + summary.opened }}</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Sudah Dibuka</p>
                    <p class="text-2xl font-bold text-green-600">{{ summary.opened }}</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Hadir</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ summary.attending }}</p>
                </div>
                <div class="bg-white rounded-xl border border-stone-100 p-4">
                    <p class="text-xs text-stone-400 mb-1">Belum Respon</p>
                    <p class="text-2xl font-bold text-amber-600">{{ summary.pending_rsvp }}</p>
                </div>
            </div>

            <!-- ── Toolbar ───────────────────────────────────────── -->
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <!-- Search -->
                <div class="relative flex-1 min-w-48">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Cari nama atau nomor…"
                           class="w-full pl-9 pr-3 py-2 border border-stone-200 rounded-xl text-sm text-stone-800 bg-white focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                </div>

                <!-- Filter: send status -->
                <select v-model="filters.send_status"
                        class="text-sm border border-stone-200 rounded-xl pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="">Semua status</option>
                    <option value="not_sent">Belum Kirim</option>
                    <option value="sent">Sudah Kirim</option>
                    <option value="opened">Sudah Dibuka</option>
                </select>

                <!-- Filter: RSVP -->
                <select v-model="filters.rsvp_status"
                        class="text-sm border border-stone-200 rounded-xl pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="">Semua RSVP</option>
                    <option value="pending">Belum Respon</option>
                    <option value="attending">Hadir</option>
                    <option value="not_attending">Tidak Hadir</option>
                </select>

                <!-- Filter: category -->
                <select v-if="categories.length > 0" v-model="filters.category"
                        class="text-sm border border-stone-200 rounded-xl pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="">Semua kategori</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>

                <!-- Sort -->
                <select v-model="sort"
                        class="text-sm border border-stone-200 rounded-xl pl-3 pr-8 py-2 bg-white text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-200">
                    <option value="newest">Terbaru</option>
                    <option value="name_asc">Nama A–Z</option>
                    <option value="not_sent">Belum Kirim Dulu</option>
                    <option value="not_rsvp">Belum RSVP Dulu</option>
                </select>

                <!-- Reset filter -->
                <button v-if="hasActiveFilter" @click="search=''; Object.assign(filters,{send_status:'',rsvp_status:'',category:'',invitation_id:''})"
                        class="text-xs text-stone-500 underline px-2">Reset</button>

                <!-- Actions -->
                <div class="ml-auto flex items-center gap-2">
                    <button @click="openTemplateEditor"
                            class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h8"/>
                        </svg>
                        Template WA
                    </button>
                    <button @click="openImport"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import
                    </button>
                    <button @click="openCreate"
                            class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90"
                            style="background-color:#D4A373">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Tamu
                    </button>
                </div>
            </div>

            <!-- ── Bulk action bar ────────────────────────────────── -->
            <Transition name="slide-down">
                <div v-if="selected.length > 0"
                     class="mb-3 flex items-center gap-3 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-100">
                    <p class="text-sm text-amber-800 font-medium">{{ selected.length }} tamu dipilih</p>
                    <button @click="bulkMarkSent" :disabled="bulkMarkSentLoading"
                            class="text-xs px-3 py-1.5 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700 disabled:opacity-50 transition-colors">
                        Tandai Sudah Kirim
                    </button>
                    <button @click="selected = []"
                            class="ml-auto text-xs text-amber-600 hover:underline">Batal</button>
                </div>
            </Transition>

            <!-- ── Guest loading ──────────────────────────────────── -->
            <div v-if="loadingGuests" class="flex justify-center py-12">
                <div class="w-6 h-6 rounded-full border-2 border-amber-300 border-t-amber-600 animate-spin"/>
            </div>

            <!-- ── Empty state ────────────────────────────────────── -->
            <div v-else-if="guests.length === 0" class="flex flex-col items-center py-20 gap-4">
                <div class="w-16 h-16 rounded-full bg-amber-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <p class="text-stone-700 font-medium">
                        {{ hasActiveFilter ? 'Tidak ada tamu yang cocok dengan filter ini' : 'Tambahkan tamu pertamamu' }}
                    </p>
                    <p class="text-stone-400 text-sm mt-1">
                        {{ hasActiveFilter ? 'Coba ubah atau reset filter di atas.' : 'Kelola daftar tamu dan kirim undangan lebih rapi lewat WhatsApp.' }}
                    </p>
                </div>
                <div v-if="!hasActiveFilter" class="flex gap-2">
                    <button @click="openCreate"
                            class="px-4 py-2 rounded-xl text-sm font-medium text-white"
                            style="background-color:#D4A373">Tambah Tamu</button>
                    <button @click="openImport"
                            class="px-4 py-2 rounded-xl text-sm border border-stone-200 text-stone-600 hover:bg-stone-50">Import CSV</button>
                </div>
            </div>

            <!-- ── Desktop table ──────────────────────────────────── -->
            <div v-else class="hidden md:block bg-white border border-stone-100 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b border-stone-100 bg-stone-50/50">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" :checked="allSelected" @change="toggleSelectAll"
                                       class="rounded border-stone-300 text-amber-500 focus:ring-amber-200"/>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Tamu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Status Kirim</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">RSVP</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wide">Terakhir Kirim</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        <tr v-for="guest in guests" :key="guest.id"
                            class="hover:bg-stone-50/50 transition-colors">
                            <!-- Checkbox -->
                            <td class="px-4 py-3">
                                <input type="checkbox" :checked="selected.includes(guest.id)"
                                       @change="toggleSelect(guest.id)"
                                       class="rounded border-stone-300 text-amber-500 focus:ring-amber-200"/>
                            </td>
                            <!-- Name + phone -->
                            <td class="px-4 py-3">
                                <p class="font-medium text-stone-800 leading-snug">{{ guest.name }}</p>
                                <p class="text-xs text-stone-400 mt-0.5">
                                    {{ guest.normalized_phone ? formatPhone(guest.normalized_phone) : guest.phone_number }}
                                </p>
                                <p v-if="guest.invitation" class="text-xs text-stone-300 mt-0.5 truncate max-w-40">{{ guest.invitation.title }}</p>
                            </td>
                            <!-- Category -->
                            <td class="px-4 py-3">
                                <span v-if="guest.category"
                                      class="text-xs px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">
                                    {{ guest.category }}
                                </span>
                                <span v-else class="text-stone-300 text-xs">—</span>
                            </td>
                            <!-- Send status -->
                            <td class="px-4 py-3">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                                      :class="sendStatusBadge(guest.send_status).cls">
                                    {{ sendStatusBadge(guest.send_status).label }}
                                </span>
                            </td>
                            <!-- RSVP -->
                            <td class="px-4 py-3">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                                      :class="rsvpBadge(guest.rsvp_status).cls">
                                    {{ rsvpBadge(guest.rsvp_status).label }}
                                </span>
                            </td>
                            <!-- Last sent -->
                            <td class="px-4 py-3 text-xs text-stone-400">
                                {{ guest.last_sent_at ? formatDate(guest.last_sent_at) : '—' }}
                            </td>
                            <!-- Actions -->
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openSendModal(guest)"
                                            title="Kirim WA"
                                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-white transition-opacity hover:opacity-90"
                                            style="background-color:#25D366">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        Kirim
                                    </button>
                                    <button @click="openEdit(guest)"
                                            class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="showDeleteConfirm = guest"
                                            class="p-1.5 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Mobile card list ───────────────────────────────── -->
            <div v-if="!loadingGuests && guests.length > 0" class="md:hidden space-y-3">
                <div v-for="guest in guests" :key="guest.id"
                     class="bg-white border border-stone-100 rounded-xl p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-stone-800 leading-snug truncate">{{ guest.name }}</p>
                            <p class="text-xs text-stone-400 mt-0.5">
                                {{ guest.normalized_phone ? formatPhone(guest.normalized_phone) : guest.phone_number }}
                            </p>
                        </div>
                        <div class="flex gap-1">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :class="sendStatusBadge(guest.send_status).cls">
                                {{ sendStatusBadge(guest.send_status).label }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1 mb-3">
                        <span v-if="guest.category"
                              class="text-xs px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">{{ guest.category }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              :class="rsvpBadge(guest.rsvp_status).cls">{{ rsvpBadge(guest.rsvp_status).label }}</span>
                        <span v-if="guest.last_sent_at" class="text-xs text-stone-400">
                            Kirim: {{ formatDate(guest.last_sent_at) }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-2 border-t border-stone-50">
                        <button @click="openSendModal(guest)"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-sm font-medium text-white transition-opacity hover:opacity-90"
                                style="background-color:#25D366">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Kirim WA
                        </button>
                        <button @click="openEdit(guest)"
                                class="px-3 py-2 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-sm transition-colors">Edit</button>
                        <button @click="showDeleteConfirm = guest"
                                class="px-3 py-2 rounded-lg border border-red-100 text-red-500 hover:bg-red-50 text-sm transition-colors">Hapus</button>
                    </div>
                </div>
            </div>

            <!-- ── Pagination ─────────────────────────────────────── -->
            <div v-if="guestsMeta.last_page > 1"
                 class="flex items-center justify-between mt-4 pt-4 border-t border-stone-100">
                <p class="text-xs text-stone-400">
                    {{ (guestsMeta.current_page - 1) * guestsMeta.per_page + 1 }}–{{ Math.min(guestsMeta.current_page * guestsMeta.per_page, guestsMeta.total) }}
                    dari {{ guestsMeta.total }} tamu
                </p>
                <div class="flex gap-1">
                    <button v-for="p in guestsMeta.last_page" :key="p"
                            @click="changePage(p)"
                            class="w-8 h-8 rounded-lg text-sm transition-colors"
                            :class="p === guestsMeta.current_page
                                ? 'text-white'
                                : 'border border-stone-200 text-stone-600 hover:bg-stone-50'"
                            :style="p === guestsMeta.current_page ? 'background-color:#D4A373' : ''">
                        {{ p }}
                    </button>
                </div>
            </div>

        </template><!-- end v-else -->

        <!-- ── FAB mobile ─────────────────────────────────────────── -->
        <button @click="openCreate"
                class="fixed bottom-6 right-6 lg:hidden w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white z-20"
                style="background-color:#D4A373">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  MODAL: Add / Edit Guest                               -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showGuestForm"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 px-4"
                 @click.self="closeGuestForm">
                <div class="bg-white w-full max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl p-6">
                    <h2 class="text-base font-semibold text-stone-800 mb-4">
                        {{ editingGuest ? 'Edit Tamu' : 'Tambah Tamu Baru' }}
                    </h2>

                    <div class="space-y-3">
                        <!-- Name -->
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Nama tamu <span class="text-red-400">*</span></label>
                            <input v-model="guestForm.name" type="text" placeholder="Bapak Andi"
                                   class="w-full border rounded-xl px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                   :class="guestErrors.name ? 'border-red-300' : 'border-stone-200'"/>
                            <p v-if="guestErrors.name" class="text-xs text-red-500 mt-1">{{ guestErrors.name[0] }}</p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Nomor WhatsApp <span class="text-red-400">*</span></label>
                            <input v-model="guestForm.phone_number" type="tel" placeholder="08123456789"
                                   class="w-full border rounded-xl px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                   :class="guestErrors.phone_number ? 'border-red-300' : 'border-stone-200'"/>
                            <p v-if="guestErrors.phone_number" class="text-xs text-red-500 mt-1">{{ guestErrors.phone_number[0] }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Category -->
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Kategori</label>
                                <input v-model="guestForm.category" type="text" placeholder="Keluarga"
                                       list="category-list"
                                       class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                                <datalist id="category-list">
                                    <option v-for="cat in suggestedCategories" :key="cat" :value="cat"/>
                                </datalist>
                            </div>
                            <!-- Greeting -->
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Sapaan</label>
                                <input v-model="guestForm.greeting" type="text" placeholder="Bapak / Ibu / Kak"
                                       class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                            </div>
                        </div>

                        <!-- Invitation -->
                        <div v-if="invitations.length > 0">
                            <label class="text-xs text-stone-500 mb-1 block">Undangan terkait</label>
                            <select v-model="guestForm.invitation_id"
                                    class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200">
                                <option value="">Tidak ditautkan</option>
                                <option v-for="inv in invitations" :key="inv.id" :value="inv.id">{{ inv.title }}</option>
                            </select>
                        </div>

                        <!-- Note -->
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Catatan</label>
                            <textarea v-model="guestForm.note" rows="2" placeholder="Tambahkan catatan…"
                                      class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 resize-none focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-5">
                        <button @click="closeGuestForm"
                                class="flex-1 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50 transition-colors">
                            Batal
                        </button>
                        <button @click="saveGuest" :disabled="savingGuest"
                                class="flex-1 py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                                style="background-color:#D4A373">
                            {{ savingGuest ? 'Menyimpan…' : (editingGuest ? 'Simpan' : 'Tambah') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  MODAL: Delete Confirm                                  -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showDeleteConfirm"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                 @click.self="showDeleteConfirm = null">
                <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl p-6">
                    <h2 class="text-base font-semibold text-stone-800 mb-2">Hapus Tamu</h2>
                    <p class="text-sm text-stone-500 mb-5">
                        Hapus <span class="font-medium text-stone-700">{{ showDeleteConfirm.name }}</span> dari daftar tamu?
                        Tindakan ini tidak bisa dibatalkan.
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDeleteConfirm = null"
                                class="flex-1 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50">Batal</button>
                        <button @click="deleteGuest(showDeleteConfirm)"
                                class="flex-1 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500 hover:bg-red-600 transition-colors">Hapus</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  MODAL: WhatsApp Template Editor                       -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showTemplateModal"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 px-4"
                 @click.self="showTemplateModal = false">
                <div class="bg-white w-full max-w-2xl rounded-t-2xl sm:rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-stone-800">Template Pesan WhatsApp</h2>
                        <button @click="showTemplateModal = false" class="text-stone-400 hover:text-stone-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <!-- Left: editor -->
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Nama template</label>
                                <input v-model="templateForm.name" type="text"
                                       class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                            </div>
                            <div>
                                <label class="text-xs text-stone-500 mb-1 block">Isi pesan</label>
                                <textarea v-model="templateForm.content" rows="10"
                                          class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 font-mono resize-none focus:outline-none focus:ring-2 focus:ring-amber-200"
                                          :class="templateErrors.content ? 'border-red-300' : ''"/>
                                <p v-if="templateErrors.content" class="text-xs text-red-500 mt-1">{{ templateErrors.content[0] }}</p>
                            </div>

                            <!-- Placeholder chips -->
                            <div>
                                <p class="text-xs text-stone-400 mb-2">Placeholder (klik untuk sisipkan):</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="ph in ['guest_name','invitation_url','greeting','bride_name','groom_name','event_date','event_time','event_location']"
                                            :key="ph"
                                            @click="insertPlaceholder(ph)"
                                            class="text-xs px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 transition-colors font-mono">
                                        {{ phLabel(ph) }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: preview -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-stone-500">Preview pesan</p>
                                <button @click="previewTemplate" :disabled="previewLoading"
                                        class="text-xs text-amber-700 underline disabled:opacity-50">
                                    {{ previewLoading ? 'Memuat…' : 'Refresh preview' }}
                                </button>
                            </div>

                            <!-- Warnings -->
                            <div v-if="templateWarnings.length > 0" class="space-y-1">
                                <p v-for="w in templateWarnings" :key="w"
                                   class="text-xs text-amber-600 bg-amber-50 px-2.5 py-1.5 rounded-lg">{{ w }}</p>
                            </div>

                            <div class="bg-stone-50 rounded-xl p-4 min-h-40 text-sm text-stone-700 whitespace-pre-wrap font-sans border border-stone-100">
                                <span v-if="!templatePreview" class="text-stone-300 text-xs">Klik "Refresh preview" untuk melihat hasil pesan.</span>
                                <span v-else>{{ templatePreview }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 mt-5 pt-4 border-t border-stone-100">
                        <button @click="resetTemplate"
                                class="text-xs text-stone-500 underline hover:text-stone-700">Reset ke default</button>
                        <div class="ml-auto flex gap-3">
                            <button @click="showTemplateModal = false"
                                    class="px-4 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50">Batal</button>
                            <button @click="saveTemplate" :disabled="savingTemplate"
                                    class="px-6 py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                                    style="background-color:#D4A373">
                                {{ savingTemplate ? 'Menyimpan…' : 'Simpan Template' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  MODAL: Send WhatsApp                                  -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showSendModal"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 px-4"
                 @click.self="showSendModal = false">
                <div class="bg-white w-full max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white flex-shrink-0"
                             style="background-color:#25D366">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-stone-800">Kirim Undangan</h2>
                            <p class="text-xs text-stone-400">{{ sendTarget?.name }} · {{ sendTarget?.normalized_phone ? formatPhone(sendTarget.normalized_phone) : sendTarget?.phone_number }}</p>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div v-if="generating" class="flex justify-center py-8">
                        <div class="w-6 h-6 rounded-full border-2 border-amber-300 border-t-amber-600 animate-spin"/>
                    </div>

                    <template v-else>
                        <!-- Message preview -->
                        <div class="bg-stone-50 rounded-xl p-4 text-sm text-stone-700 whitespace-pre-wrap max-h-48 overflow-y-auto mb-4 border border-stone-100">{{ generatedMsg }}</div>

                        <!-- Actions -->
                        <div class="flex gap-2 mb-3">
                            <button @click="copyMessage"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-700 hover:bg-stone-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy Text
                            </button>
                            <button @click="openWhatsApp"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90"
                                    style="background-color:#25D366">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                Buka WhatsApp
                            </button>
                        </div>

                        <!-- Mark sent -->
                        <button @click="confirmSent" :disabled="markingSent"
                                class="w-full py-2 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50 disabled:opacity-50 transition-colors">
                            {{ markingSent ? 'Memperbarui…' : 'Tandai sudah dikirim' }}
                        </button>
                    </template>
                </div>
            </div>
        </Transition>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!--  MODAL: Import                                         -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <Transition name="fade">
            <div v-if="showImportModal"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 px-4"
                 @click.self="showImportModal = false">
                <div class="bg-white w-full max-w-lg rounded-t-2xl sm:rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-stone-800">Import Tamu</h2>
                        <button @click="showImportModal = false" class="text-stone-400 hover:text-stone-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Step: input -->
                    <template v-if="importStep === 'input'">
                        <!-- Mode tabs -->
                        <div class="flex rounded-xl border border-stone-200 overflow-hidden mb-4">
                            <button @click="importMode = 'paste'"
                                    :class="importMode === 'paste' ? 'text-white' : 'text-stone-600 hover:bg-stone-50'"
                                    :style="importMode === 'paste' ? 'background-color:#D4A373' : ''"
                                    class="flex-1 py-2 text-sm font-medium transition-colors">Paste List</button>
                            <button @click="importMode = 'csv'"
                                    :class="importMode === 'csv' ? 'text-white' : 'text-stone-600 hover:bg-stone-50'"
                                    :style="importMode === 'csv' ? 'background-color:#D4A373' : ''"
                                    class="flex-1 py-2 text-sm font-medium transition-colors">Upload CSV</button>
                        </div>

                        <!-- Invitation select -->
                        <div v-if="invitations.length > 0" class="mb-4">
                            <label class="text-xs text-stone-500 mb-1 block">Undangan terkait (opsional)</label>
                            <select v-model="importInvId"
                                    class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-200">
                                <option value="">Tidak ditautkan</option>
                                <option v-for="inv in invitations" :key="inv.id" :value="inv.id">{{ inv.title }}</option>
                            </select>
                        </div>

                        <!-- Paste mode -->
                        <template v-if="importMode === 'paste'">
                            <label class="text-xs text-stone-500 mb-1 block">
                                Paste daftar tamu (satu baris = satu tamu)
                            </label>
                            <p class="text-xs text-stone-300 mb-2">Format: Nama, Nomor WA, Kategori (opsional)</p>
                            <textarea v-model="importText" rows="8"
                                      placeholder="Bapak Andi,08123456789,Keluarga&#10;Ibu Rina,08234567890,Teman"
                                      class="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm text-stone-800 font-mono resize-none focus:outline-none focus:ring-2 focus:ring-amber-200"/>
                        </template>

                        <!-- CSV mode -->
                        <template v-else>
                            <label class="text-xs text-stone-500 mb-1 block">Upload file CSV</label>
                            <p class="text-xs text-stone-300 mb-2">Header: name, phone, category, greeting, note</p>
                            <input type="file" accept=".csv,.txt" @change="onFileChange"
                                   class="w-full text-sm text-stone-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:text-white file:cursor-pointer hover:file:opacity-90"
                                   style="--file-bg:#D4A373"/>
                        </template>

                        <div class="flex gap-3 mt-5">
                            <button @click="showImportModal = false"
                                    class="flex-1 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50">Batal</button>
                            <button @click="previewImport" :disabled="importPreviewing || (!importText && !importFile)"
                                    class="flex-1 py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                                    style="background-color:#D4A373">
                                {{ importPreviewing ? 'Memproses…' : 'Preview Data' }}
                            </button>
                        </div>
                    </template>

                    <!-- Step: preview -->
                    <template v-else-if="importStep === 'preview'">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="text-xs px-2.5 py-1 rounded-full bg-green-50 text-green-700 font-medium">
                                {{ importPreview.filter(r => r.valid).length }} valid
                            </span>
                            <span v-if="importPreview.filter(r => !r.valid).length > 0"
                                  class="text-xs px-2.5 py-1 rounded-full bg-red-50 text-red-600 font-medium">
                                {{ importPreview.filter(r => !r.valid).length }} error
                            </span>
                        </div>

                        <div class="border border-stone-100 rounded-xl overflow-hidden max-h-64 overflow-y-auto mb-4">
                            <table class="w-full text-xs">
                                <thead class="bg-stone-50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-stone-500 font-medium">#</th>
                                        <th class="px-3 py-2 text-left text-stone-500 font-medium">Nama</th>
                                        <th class="px-3 py-2 text-left text-stone-500 font-medium">Nomor WA</th>
                                        <th class="px-3 py-2 text-left text-stone-500 font-medium">Kategori</th>
                                        <th class="px-3 py-2 text-left text-stone-500 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-50">
                                    <tr v-for="row in importPreview" :key="row.row"
                                        :class="row.valid ? '' : 'bg-red-50'">
                                        <td class="px-3 py-2 text-stone-400">{{ row.row }}</td>
                                        <td class="px-3 py-2 text-stone-700">{{ row.name }}</td>
                                        <td class="px-3 py-2 text-stone-500">{{ row.phone }}</td>
                                        <td class="px-3 py-2 text-stone-500">{{ row.category || '—' }}</td>
                                        <td class="px-3 py-2">
                                            <span v-if="row.valid" class="text-green-600">✓ Valid</span>
                                            <span v-else class="text-red-500">{{ row.errors.join(', ') }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex gap-3">
                            <button @click="importStep = 'input'"
                                    class="flex-1 py-2.5 rounded-xl border border-stone-200 text-sm text-stone-600 hover:bg-stone-50">Kembali</button>
                            <button @click="commitImport"
                                    :disabled="importCommitting || importPreview.filter(r => r.valid).length === 0"
                                    class="flex-1 py-2.5 rounded-xl text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                                    style="background-color:#D4A373">
                                {{ importCommitting ? 'Menyimpan…' : `Import ${importPreview.filter(r => r.valid).length} Tamu` }}
                            </button>
                        </div>
                    </template>

                    <!-- Step: done -->
                    <template v-else>
                        <div class="text-center py-8">
                            <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="font-medium text-stone-800 mb-1">Import Berhasil</p>
                            <p class="text-sm text-stone-500">{{ importResult?.message }}</p>
                        </div>
                        <button @click="showImportModal = false"
                                class="w-full py-2.5 rounded-xl text-sm font-medium text-white"
                                style="background-color:#D4A373">Selesai</button>
                    </template>
                </div>
            </div>
        </Transition>

        <!-- ── Toast ──────────────────────────────────────────────── -->
        <Transition name="slide-up">
            <div v-if="toast"
                 class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-xl bg-stone-800 text-white text-sm shadow-lg">
                {{ toast }}
            </div>
        </Transition>

    </DashboardLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.25s; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateX(-50%) translateY(12px); }

input[type="file"]::file-selector-button {
    background-color: #D4A373;
    color: white;
    border-radius: 12px;
    font-size: 0.875rem;
}
</style>
