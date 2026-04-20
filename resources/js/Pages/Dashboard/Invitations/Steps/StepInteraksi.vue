<script setup>
// Step 4 — Interaksi
// Sections: rsvp (opt), wishes (opt), gift (opt)

import { ref, computed } from 'vue';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';

const props = defineProps({
    sections:        { type: Object,   required: true },
    onToggleSection: { type: Function, required: true },
    canUsePremium:   { type: Boolean,  default: false },
});

const expanded = ref(new Set());

function toggle(key) {
    const s = new Set(expanded.value);
    if (s.has(key)) s.delete(key); else s.add(key);
    expanded.value = s;
}

function addBankAccount() {
    if (!props.sections.gift.data_json.accounts) {
        props.sections.gift.data_json.accounts = [];
    }
    props.sections.gift.data_json.accounts.push({ bank: '', name: '', number: '' });
}

function removeBankAccount(index) {
    props.sections.gift.data_json.accounts?.splice(index, 1);
}

// ── Date picker ──────────────────────────────────────────────────
const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const DAYS_ID   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

const showDatePicker = ref(false);
const calToday       = new Date();
const calYear        = ref(calToday.getFullYear());
const calMonth       = ref(calToday.getMonth());

function openDatePicker() {
    const val = props.sections.rsvp?.data_json?.deadline;
    if (val) {
        const [y, m] = val.split('-').map(Number);
        calYear.value  = y;
        calMonth.value = m - 1;
    } else {
        calYear.value  = calToday.getFullYear();
        calMonth.value = calToday.getMonth();
    }
    showDatePicker.value = true;
}
function closeDatePicker() { showDatePicker.value = false; }
function prevCalMonth() {
    if (calMonth.value === 0) { calMonth.value = 11; calYear.value--; }
    else calMonth.value--;
}
function nextCalMonth() {
    if (calMonth.value === 11) { calMonth.value = 0; calYear.value++; }
    else calMonth.value++;
}
const calDays = computed(() => {
    const first = new Date(calYear.value, calMonth.value, 1).getDay();
    const total = new Date(calYear.value, calMonth.value + 1, 0).getDate();
    const cells = [];
    for (let i = 0; i < first; i++) cells.push(null);
    for (let d = 1; d <= total; d++) cells.push(d);
    return cells;
});
function pickDay(day) {
    if (!day) return;
    const m = String(calMonth.value + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    props.sections.rsvp.data_json.deadline = `${calYear.value}-${m}-${d}`;
}
function isPickedDay(day) {
    if (!day) return false;
    const val = props.sections.rsvp?.data_json?.deadline;
    if (!val) return false;
    const [y, m, d] = val.split('-').map(Number);
    return y === calYear.value && m === calMonth.value + 1 && d === day;
}
function calDisplayDate(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}
const currentPickerDate = computed(() => props.sections.rsvp?.data_json?.deadline ?? '');
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Interaksi</h2>
            <p class="text-sm text-stone-400 mt-0.5">RSVP, ucapan, dan kado digital (semua opsional)</p>
        </div>

        <!-- RSVP (optional, enabled by default) -->
        <SectionAccordionCard
            title="RSVP"
            description="Form konfirmasi kehadiran tamu"
            :is-required="sections.rsvp?.is_required ?? false"
            :is-enabled="sections.rsvp?.is_enabled ?? true"
            :status="sections.rsvp?.completion_status ?? 'complete'"
            :expanded="expanded.has('rsvp')"
            @toggle-expand="toggle('rsvp')"
            @toggle-enabled="onToggleSection('rsvp')"
        >
            <div class="space-y-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Batas RSVP</label>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="openDatePicker()"
                                class="flex-1 border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-left transition-colors hover:border-[#92A89C]/50 bg-white">
                            <span v-if="sections.rsvp?.data_json?.deadline" class="text-stone-800">{{ calDisplayDate(sections.rsvp.data_json.deadline) }}</span>
                            <span v-else class="text-stone-400">Pilih tanggal (opsional)</span>
                        </button>
                        <button v-if="sections.rsvp?.data_json?.deadline" type="button"
                                @click="sections.rsvp.data_json.deadline = ''"
                                class="p-2 rounded-xl text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-stone-400">Opsional. Tamu masih bisa RSVP setelah tanggal ini jika tidak diisi.</p>
                </div>
                <div class="flex items-center gap-3 p-3 bg-[#92A89C]/10 border border-[#B8C7BF]/50 rounded-xl">
                    <svg class="w-4 h-4 text-[#73877C] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-[#73877C]">Form RSVP akan muncul di halaman undangan. Data konfirmasi masuk ke dashboard.</p>
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Wishes (optional, enabled by default) -->
        <SectionAccordionCard
            title="Ucapan & Doa"
            description="Kolom ucapan selamat dari tamu"
            :is-required="sections.wishes?.is_required ?? false"
            :is-enabled="sections.wishes?.is_enabled ?? true"
            :status="sections.wishes?.completion_status ?? 'complete'"
            :expanded="expanded.has('wishes')"
            @toggle-expand="toggle('wishes')"
            @toggle-enabled="onToggleSection('wishes')"
        >
            <div class="p-3 bg-[#92A89C]/10 border border-[#B8C7BF]/50 rounded-xl">
                <p class="text-xs text-[#73877C]">
                    Tamu dapat mengirimkan ucapan langsung dari halaman undangan.
                    Semua ucapan tersimpan di dashboard Anda.
                </p>
            </div>
        </SectionAccordionCard>

        <!-- Gift — Premium only (Pattern A: hidden for free users) -->
        <SectionAccordionCard
            v-if="canUsePremium"
            title="Kado Digital"
            description="Rekening bank untuk kado pernikahan"
            :is-required="sections.gift?.is_required ?? false"
            :is-enabled="sections.gift?.is_enabled ?? false"
            :status="sections.gift?.completion_status ?? 'disabled'"
            :expanded="expanded.has('gift')"
            @toggle-expand="toggle('gift')"
            @toggle-enabled="onToggleSection('gift')"
        >
            <div class="space-y-4">
                <div
                    v-for="(acc, index) in sections.gift.data_json.accounts ?? []"
                    :key="index"
                    class="rounded-xl border border-stone-200 p-4 space-y-3 bg-stone-50/50"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-stone-600">Rekening {{ index + 1 }}</span>
                        <button @click="removeBankAccount(index)"
                                class="p-1 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Bank</label>
                            <input v-model="acc.bank" type="text" placeholder="BCA / Mandiri / dll"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Nomor Rekening</label>
                            <input v-model="acc.number" type="text" placeholder="1234567890"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Atas Nama</label>
                            <input v-model="acc.name" type="text" placeholder="Nama Pemilik"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        </div>
                    </div>
                </div>

                <button
                    @click="addBankAccount"
                    class="w-full py-3 rounded-xl border-2 border-dashed border-stone-200 text-sm font-medium text-stone-500 hover:text-[#73877C] hover:border-[#92A89C]/50 hover:bg-[#92A89C]/10 transition-all flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Rekening
                </button>
            </div>
        </SectionAccordionCard>

    </div>

    <!-- Date Picker Modal -->
    <Teleport to="body">
        <Transition name="datepicker-modal">
            <div v-if="showDatePicker" class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDatePicker"/>
                <div class="relative w-full sm:max-w-sm bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden"
                     style="max-height: 92dvh; overflow-y: auto">
                    <div class="sm:hidden flex justify-center pt-3 pb-1">
                        <div class="w-10 h-1 rounded-full bg-stone-200"/>
                    </div>
                    <div class="flex items-center justify-between px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-stone-800">Pilih Tanggal</p>
                            <p v-if="currentPickerDate" class="text-xs text-[#73877C] mt-0.5">{{ calDisplayDate(currentPickerDate) }}</p>
                        </div>
                        <button type="button" @click="closeDatePicker"
                                class="w-8 h-8 rounded-full bg-stone-100 flex items-center justify-center hover:bg-stone-200 transition-colors">
                            <svg class="w-4 h-4 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between px-5 pb-2">
                        <button type="button" @click="prevCalMonth"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-stone-500 hover:bg-stone-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <span class="text-sm font-semibold text-stone-700">{{ MONTHS_ID[calMonth] }} {{ calYear }}</span>
                        <button type="button" @click="nextCalMonth"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-stone-500 hover:bg-stone-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-7 px-4 pb-1">
                        <div v-for="d in DAYS_ID" :key="d"
                             class="text-center text-xs font-semibold py-1"
                             :class="d === 'Min' ? 'text-rose-400' : 'text-stone-400'">{{ d }}</div>
                    </div>
                    <div class="grid grid-cols-7 px-4 pb-3 gap-y-1">
                        <div v-for="(day, i) in calDays" :key="i" class="flex items-center justify-center aspect-square">
                            <button v-if="day" type="button" @click="pickDay(day)"
                                    class="w-9 h-9 rounded-full text-sm font-medium transition-all"
                                    :class="isPickedDay(day) ? 'text-white font-bold shadow-sm' : 'text-stone-700 hover:bg-[#92A89C]/10 active:bg-[#92A89C]/20'"
                                    :style="isPickedDay(day) ? 'background-color:#92A89C' : ''">
                                {{ day }}
                            </button>
                        </div>
                    </div>
                    <div class="px-5 pb-6 pt-2">
                        <button type="button" @click="closeDatePicker" :disabled="!currentPickerDate"
                                class="w-full py-3.5 rounded-2xl text-sm font-bold text-white transition-all disabled:opacity-40"
                                style="background-color:#92A89C">
                            <span v-if="currentPickerDate">Pilih — {{ calDisplayDate(currentPickerDate) }}</span>
                            <span v-else>Pilih tanggal dulu</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.datepicker-modal-enter-active, .datepicker-modal-leave-active { transition: opacity 0.2s; }
.datepicker-modal-enter-from, .datepicker-modal-leave-to { opacity: 0; }
</style>
