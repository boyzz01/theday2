<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    user: { type: Object, required: true },
});

// ── Persistence ───────────────────────────────────────────────────
const STORAGE_KEY = 'theday_onboarding';

function saveProgress() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        step: step.value,
        form: {
            groom_name:     form.groom_name,
            groom_nickname: form.groom_nickname,
            bride_name:     form.bride_name,
            bride_nickname: form.bride_nickname,
            phone:          form.phone,
            no_date:        form.no_date,
            wedding_date:   form.wedding_date,
            start_time:     form.start_time,
            venue_name:     form.venue_name,
            venue_address:  form.venue_address,
            skip_slug:      form.skip_slug,
            slug:           form.slug,
        },
    }));
}

function clearProgress() {
    localStorage.removeItem(STORAGE_KEY);
}

// ── Step management ───────────────────────────────────────────────
const step    = ref(1);
const STEPS   = 3;

const stepMeta = [
    { label: 'Mempelai',   hint: 'Siapa yang akan menikah?' },
    { label: 'Hari H',     hint: 'Kapan dan di mana?' },
    { label: 'Tautan',     hint: 'Alamat undangan digitalmu' },
];

const goNext = () => { if (step.value < STEPS) step.value++ };
const goBack = () => { if (step.value > 1) step.value-- };

// ── Form ──────────────────────────────────────────────────────────
const form = useForm({
    groom_name:     '',
    groom_nickname: '',
    bride_name:     '',
    bride_nickname: '',
    phone:          props.user.phone ?? '',
    no_date:        false,
    wedding_date:   '',
    start_time:     '',
    venue_name:     '',
    venue_address:  '',
    skip_slug:      false,
    slug:           '',
});

// ── Step 1 validation (client-side gate before advancing) ─────────
const step1Errors = computed(() => {
    const e = {};
    if (!form.groom_name.trim()) e.groom_name = 'Wajib diisi.';
    if (!form.bride_name.trim()) e.bride_name  = 'Wajib diisi.';
    if (form.groom_nickname && form.groom_nickname.length > 10)
        e.groom_nickname = 'Maks. 10 karakter.';
    if (form.bride_nickname && form.bride_nickname.length > 10)
        e.bride_nickname = 'Maks. 10 karakter.';
    return e;
});

const step2Errors = computed(() => {
    const e = {};
    if (!form.no_date && !form.wedding_date) e.wedding_date = 'Wajib diisi atau centang "Belum menentukan tanggal".';
    return e;
});

function tryNext() {
    if (step.value === 1 && Object.keys(step1Errors.value).length) return;
    if (step.value === 2 && Object.keys(step2Errors.value).length) return;
    goNext();
    saveProgress();
}

// ── Slug helpers ──────────────────────────────────────────────────
function suggestSlug() {
    const g = (form.groom_nickname || form.groom_name).trim().toLowerCase().replace(/\s+/g, '');
    const b = (form.bride_nickname || form.bride_name).trim().toLowerCase().replace(/\s+/g, '');
    if (g && b) form.slug = `${g}-${b}`;
}

function sanitizeSlug() {
    form.slug = form.slug.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
}

// ── Restore from localStorage on mount ───────────────────────────
onMounted(() => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) return;
        const { step: savedStep, form: savedForm } = JSON.parse(saved);
        Object.assign(form, savedForm);
        step.value = savedStep ?? 1;
        // Sync calendar view to restored date
        if (savedForm.wedding_date) {
            const [y, m] = savedForm.wedding_date.split('-').map(Number);
            calYear.value  = y;
            calMonth.value = m - 1;
        }
        // Sync time picker
        if (savedForm.start_time) {
            const [h, min] = savedForm.start_time.split(':');
            timeHour.value   = h;
            timeMinute.value = MINUTES.includes(min) ? min : '00';
        }
    } catch { /* ignore corrupt storage */ }
});

// ── Submit ────────────────────────────────────────────────────────
function submit() {
    form.post(route('onboarding.store'), {
        onSuccess: () => clearProgress(),
    });
}

// ── Computed display ──────────────────────────────────────────────
const coupleDisplay = computed(() => {
    const g = form.groom_nickname || form.groom_name.split(' ')[0];
    const b = form.bride_nickname || form.bride_name.split(' ')[0];
    if (g && b) return `${g} & ${b}`;
    return '';
});

// ── Date modal ────────────────────────────────────────────────────
const showDateModal = ref(false);

function openDateModal()  { showDateModal.value = true;  }
function closeDateModal() { showDateModal.value = false; }

// ── Custom date picker ────────────────────────────────────────────
const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const DAYS_ID   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

const today        = new Date();
const calYear      = ref(today.getFullYear());
const calMonth     = ref(today.getMonth()); // 0-indexed

function prevMonth() {
    if (calMonth.value === 0) { calMonth.value = 11; calYear.value--; }
    else calMonth.value--;
}
function nextMonth() {
    if (calMonth.value === 11) { calMonth.value = 0; calYear.value++; }
    else calMonth.value++;
}

// Grid of day numbers (null = padding cell)
const calDays = computed(() => {
    const first   = new Date(calYear.value, calMonth.value, 1).getDay();
    const total   = new Date(calYear.value, calMonth.value + 1, 0).getDate();
    const cells   = [];
    for (let i = 0; i < first; i++) cells.push(null);
    for (let d = 1; d <= total; d++) cells.push(d);
    return cells;
});

function selectDay(day) {
    if (!day) return;
    const m = String(calMonth.value + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    form.wedding_date = `${calYear.value}-${m}-${d}`;
}

function isSelectedDay(day) {
    if (!day || !form.wedding_date) return false;
    const [y, m, d] = form.wedding_date.split('-').map(Number);
    return y === calYear.value && m === calMonth.value + 1 && d === day;
}

function isPastDay(day) {
    if (!day) return false;
    const cellDate = new Date(calYear.value, calMonth.value, day);
    const t        = new Date(); t.setHours(0,0,0,0);
    return cellDate < t;
}

const displayDate = computed(() => {
    if (!form.wedding_date) return '';
    const [y, m, d] = form.wedding_date.split('-').map(Number);
    const date = new Date(y, m - 1, d);
    return date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
});

// Sync calendar view when date is set externally
watch(() => form.wedding_date, (val) => {
    if (val) {
        const [y, m] = val.split('-').map(Number);
        calYear.value  = y;
        calMonth.value = m - 1;
    }
});

// ── Time picker ───────────────────────────────────────────────────
const timeHour   = ref('');
const timeMinute = ref('');

const HOURS   = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const MINUTES = ['00', '15', '30', '45'];

watch([timeHour, timeMinute], ([h, m]) => {
    if (h !== '') form.start_time = m !== '' ? `${h}:${m}` : `${h}:00`;
});
</script>

<template>
    <Head title="Setup Undangan — TheDay" />

    <div class="min-h-screen flex flex-col" style="background-color: #FFFCF7">

        <!-- ── Top bar ──────────────────────────────────────────────── -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: #D4A373">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <span class="font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">TheDay</span>
            </a>

            <!-- Step indicators -->
            <div class="flex items-center gap-2">
                <div v-for="i in STEPS" :key="i" class="flex items-center gap-2">
                    <div
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-200"
                        :class="i < step
                            ? 'text-white'
                            : i === step
                                ? 'text-white'
                                : 'bg-stone-100 text-stone-400'"
                        :style="i <= step ? 'background-color: #D4A373' : ''"
                    >
                        <svg v-if="i < step" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span v-else>{{ i }}</span>
                    </div>
                    <div v-if="i < STEPS" class="w-6 h-px" :class="i < step ? 'bg-amber-400' : 'bg-stone-200'"/>
                </div>
            </div>
        </div>

        <!-- ── Main content ─────────────────────────────────────────── -->
        <div class="flex-1 flex flex-col items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">

                <!-- Step header -->
                <div class="mb-8 text-center">
                    <p class="text-xs font-semibold uppercase tracking-widest mb-2" style="color: #D4A373">
                        Langkah {{ step }} dari {{ STEPS }}
                    </p>
                    <h1 class="text-2xl font-bold text-stone-900 mb-1" style="font-family: 'Playfair Display', serif">
                        {{ stepMeta[step - 1].label }}
                    </h1>
                    <p class="text-sm text-stone-400">{{ stepMeta[step - 1].hint }}</p>
                </div>

                <!-- ── STEP 1: Couple names ───────────────────────────── -->
                <transition name="slide" mode="out-in">
                <div v-if="step === 1" key="step1" class="space-y-5">

                    <!-- Mempelai Pria -->
                    <div class="bg-white rounded-2xl border border-stone-100 shadow-sm p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0" style="background-color: #D4A373">P</div>
                            <span class="text-sm font-semibold text-stone-700">Mempelai Pria</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">
                                Nama Lengkap <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="form.groom_name"
                                type="text"
                                placeholder="Ahmad Budi Santoso"
                                autofocus
                                class="w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                                :class="(form.errors.groom_name || step1Errors.groom_name) && form.groom_name === ''
                                    ? 'border-red-300 focus:ring-red-100'
                                    : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
                            />
                            <p v-if="form.errors.groom_name" class="text-xs text-red-500">{{ form.errors.groom_name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">
                                Nama Panggilan
                                <span class="text-stone-400 font-normal">(maks. 10 huruf, tampil di undangan)</span>
                            </label>
                            <input
                                v-model="form.groom_nickname"
                                type="text"
                                placeholder="Budi"
                                maxlength="10"
                                class="w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                                :class="step1Errors.groom_nickname
                                    ? 'border-red-300 focus:ring-red-100'
                                    : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
                            />
                            <div class="flex justify-between items-center">
                                <p v-if="step1Errors.groom_nickname" class="text-xs text-red-500">{{ step1Errors.groom_nickname }}</p>
                                <p class="text-xs text-stone-300 ml-auto">{{ form.groom_nickname.length }}/10</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mempelai Wanita -->
                    <div class="bg-white rounded-2xl border border-stone-100 shadow-sm p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0" style="background-color: #C8A26B">W</div>
                            <span class="text-sm font-semibold text-stone-700">Mempelai Wanita</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">
                                Nama Lengkap <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="form.bride_name"
                                type="text"
                                placeholder="Siti Rahayu Putri"
                                class="w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                                :class="(form.errors.bride_name || step1Errors.bride_name) && form.bride_name === ''
                                    ? 'border-red-300 focus:ring-red-100'
                                    : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
                            />
                            <p v-if="form.errors.bride_name" class="text-xs text-red-500">{{ form.errors.bride_name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">
                                Nama Panggilan
                                <span class="text-stone-400 font-normal">(maks. 10 huruf, tampil di undangan)</span>
                            </label>
                            <input
                                v-model="form.bride_nickname"
                                type="text"
                                placeholder="Rahayu"
                                maxlength="10"
                                class="w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                                :class="step1Errors.bride_nickname
                                    ? 'border-red-300 focus:ring-red-100'
                                    : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
                            />
                            <div class="flex justify-between items-center">
                                <p v-if="step1Errors.bride_nickname" class="text-xs text-red-500">{{ step1Errors.bride_nickname }}</p>
                                <p class="text-xs text-stone-300 ml-auto">{{ form.bride_nickname.length }}/10</p>
                            </div>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="bg-white rounded-2xl border border-stone-100 shadow-sm p-5 space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">
                            Nomor WhatsApp
                            <span class="text-stone-400 font-normal">(untuk konfirmasi undangan)</span>
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-stone-200 bg-stone-50 text-xs text-stone-500 font-medium">+62</span>
                            <input
                                v-model="form.phone"
                                type="tel"
                                placeholder="812 3456 7890"
                                class="flex-1 px-4 py-3 rounded-r-xl border border-stone-200 text-sm transition-colors outline-none focus:ring-2 focus:ring-amber-100 focus:border-amber-300"
                            />
                        </div>
                        <p v-if="form.errors.phone" class="text-xs text-red-500">{{ form.errors.phone }}</p>
                    </div>

                    <button
                        type="button"
                        @click="tryNext"
                        :disabled="!form.groom_name.trim() || !form.bride_name.trim() || Object.keys(step1Errors).length > 0"
                        class="w-full py-3.5 rounded-2xl text-sm font-bold text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 active:scale-[0.99]"
                        style="background-color: #D4A373"
                    >
                        Lanjut →
                    </button>
                </div>
                </transition>

                <!-- ── STEP 2: Date & Venue ───────────────────────────── -->
                <transition name="slide" mode="out-in">
                <div v-if="step === 2" key="step2" class="space-y-5">

                    <!-- Preview couple name -->
                    <div v-if="coupleDisplay" class="text-center py-3">
                        <p class="text-xl font-semibold text-stone-700" style="font-family: 'Playfair Display', serif">
                            {{ coupleDisplay }}
                        </p>
                        <p class="text-xs text-stone-400 mt-0.5">Kapan hari bahagianya? 🌸</p>
                    </div>

                    <!-- No date checkbox -->
                    <label class="flex items-center gap-3 p-4 rounded-2xl border cursor-pointer transition-colors"
                           :class="form.no_date ? 'border-amber-300 bg-amber-50/50' : 'border-stone-200 bg-white'">
                        <div class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 border-2 transition-colors"
                             :style="form.no_date ? 'background-color:#D4A373; border-color:#D4A373' : 'border-color:#D1D5DB'">
                            <svg v-if="form.no_date" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <input type="checkbox" v-model="form.no_date" class="sr-only"/>
                        <div>
                            <p class="text-sm font-medium text-stone-700">Belum menentukan tanggal dan waktu</p>
                            <p class="text-xs text-stone-400">Bisa diisi nanti setelah template dipilih</p>
                        </div>
                    </label>

                    <!-- Date trigger button -->
                    <div v-if="!form.no_date" class="space-y-1.5">
                        <p class="text-xs font-medium text-stone-600">
                            Tanggal & Waktu Pernikahan <span class="text-red-400">*</span>
                        </p>
                        <button
                            type="button"
                            @click="openDateModal"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl border bg-white text-sm transition-colors"
                            :class="step2Errors.wedding_date ? 'border-red-300' : 'border-stone-200 hover:border-amber-300'"
                        >
                            <span v-if="displayDate" class="text-stone-800 font-medium">
                                {{ displayDate }}{{ form.start_time ? ' · ' + form.start_time + ' WIB' : '' }}
                            </span>
                            <span v-else class="text-stone-400">Pilih tanggal…</span>
                            <svg class="w-4 h-4 text-stone-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <p v-if="step2Errors.wedding_date || form.errors.wedding_date" class="text-xs text-red-500">
                            {{ form.errors.wedding_date || step2Errors.wedding_date }}
                        </p>
                    </div>

                    <!-- ── Date/time modal ───────────────────────────── -->
                    <teleport to="body">
                    <transition name="modal">
                    <div v-if="showDateModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
                        <!-- Backdrop -->
                        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDateModal"/>

                        <!-- Sheet -->
                        <div class="relative w-full sm:max-w-sm bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden"
                             style="max-height: 92dvh; overflow-y: auto">

                            <!-- Handle bar (mobile) -->
                            <div class="sm:hidden flex justify-center pt-3 pb-1">
                                <div class="w-10 h-1 rounded-full bg-stone-200"/>
                            </div>

                            <!-- Header -->
                            <div class="flex items-center justify-between px-5 py-4">
                                <div>
                                    <p class="text-sm font-bold text-stone-800" style="font-family:'Playfair Display',serif">
                                        Pilih Tanggal
                                    </p>
                                    <p v-if="displayDate" class="text-xs text-amber-600 mt-0.5">{{ displayDate }}</p>
                                </div>
                                <button type="button" @click="closeDateModal"
                                        class="w-8 h-8 rounded-full bg-stone-100 flex items-center justify-center hover:bg-stone-200 transition-colors">
                                    <svg class="w-4 h-4 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Month/year navigation -->
                            <div class="flex items-center justify-between px-5 pb-2">
                                <button type="button" @click="prevMonth"
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-stone-500 hover:bg-stone-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <span class="text-sm font-semibold text-stone-700">
                                    {{ MONTHS_ID[calMonth] }} {{ calYear }}
                                </span>
                                <button type="button" @click="nextMonth"
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-stone-500 hover:bg-stone-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Day-of-week headers -->
                            <div class="grid grid-cols-7 px-4 pb-1">
                                <div v-for="d in DAYS_ID" :key="d"
                                     class="text-center text-xs font-semibold py-1"
                                     :class="d === 'Min' ? 'text-rose-400' : 'text-stone-400'">
                                    {{ d }}
                                </div>
                            </div>

                            <!-- Calendar grid -->
                            <div class="grid grid-cols-7 px-4 pb-3 gap-y-1">
                                <div v-for="(day, i) in calDays" :key="i"
                                     class="flex items-center justify-center aspect-square">
                                    <button
                                        v-if="day"
                                        type="button"
                                        @click="selectDay(day)"
                                        :disabled="isPastDay(day)"
                                        class="w-9 h-9 rounded-full text-sm font-medium transition-all"
                                        :class="[
                                            isSelectedDay(day)
                                                ? 'text-white font-bold shadow-sm'
                                                : isPastDay(day)
                                                    ? 'text-stone-200 cursor-not-allowed'
                                                    : 'text-stone-700 hover:bg-amber-50 active:bg-amber-100',
                                        ]"
                                        :style="isSelectedDay(day) ? 'background-color:#D4A373' : ''"
                                    >
                                        {{ day }}
                                    </button>
                                </div>
                            </div>

                            <!-- Time picker -->
                            <div class="border-t border-stone-100 px-5 py-4 space-y-3">
                                <p class="text-xs font-semibold text-stone-500 uppercase tracking-wide">
                                    Waktu Mulai <span class="text-stone-300 font-normal normal-case">(opsional)</span>
                                </p>
                                <div class="flex gap-3">
                                    <!-- Hours -->
                                    <div class="flex-1 space-y-1.5">
                                        <p class="text-xs text-stone-400 text-center">Jam</p>
                                        <div class="grid grid-cols-4 gap-1">
                                            <button
                                                v-for="h in HOURS" :key="h"
                                                type="button"
                                                @click="timeHour = h"
                                                class="py-1.5 rounded-lg text-xs font-medium transition-all"
                                                :class="timeHour === h ? 'text-white font-bold' : 'text-stone-600 bg-stone-50 hover:bg-amber-50'"
                                                :style="timeHour === h ? 'background-color:#D4A373' : ''"
                                            >{{ h }}</button>
                                        </div>
                                    </div>
                                    <!-- Divider -->
                                    <div class="w-px bg-stone-100 self-stretch"/>
                                    <!-- Minutes -->
                                    <div class="w-20 flex-shrink-0 space-y-1.5">
                                        <p class="text-xs text-stone-400 text-center">Menit</p>
                                        <div class="flex flex-col gap-1.5">
                                            <button
                                                v-for="m in MINUTES" :key="m"
                                                type="button"
                                                @click="timeMinute = m"
                                                class="py-2.5 rounded-lg text-xs font-medium transition-all"
                                                :class="timeMinute === m ? 'text-white font-bold' : 'text-stone-600 bg-stone-50 hover:bg-amber-50'"
                                                :style="timeMinute === m ? 'background-color:#D4A373' : ''"
                                            >{{ m }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm button -->
                            <div class="px-5 pb-6 pt-2">
                                <button
                                    type="button"
                                    @click="closeDateModal"
                                    :disabled="!form.wedding_date"
                                    class="w-full py-3.5 rounded-2xl text-sm font-bold text-white transition-all disabled:opacity-40"
                                    style="background-color:#D4A373"
                                >
                                    <span v-if="form.wedding_date">
                                        Simpan{{ form.start_time ? ' · ' + form.start_time + ' WIB' : '' }}
                                    </span>
                                    <span v-else>Pilih tanggal dulu</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    </transition>
                    </teleport>

                    <!-- Venue -->
                    <div class="bg-white rounded-2xl border border-stone-100 shadow-sm p-5 space-y-4">
                        <p class="text-xs font-semibold text-stone-500 uppercase tracking-wide">Lokasi</p>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">Nama Gedung / Tempat</label>
                            <input
                                v-model="form.venue_name"
                                type="text"
                                placeholder="Ballroom Hotel Grand, Masjid Al-Akbar…"
                                class="w-full px-4 py-3 rounded-xl border border-stone-200 text-sm transition-colors outline-none focus:ring-2 focus:ring-amber-100 focus:border-amber-300"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">Alamat Lengkap</label>
                            <textarea
                                v-model="form.venue_address"
                                rows="2"
                                placeholder="Jl. Pemuda No. 1, Surabaya, Jawa Timur"
                                class="w-full px-4 py-3 rounded-xl border border-stone-200 text-sm transition-colors outline-none focus:ring-2 focus:ring-amber-100 focus:border-amber-300 resize-none"
                            />
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="goBack"
                                class="flex-none px-5 py-3.5 rounded-2xl text-sm font-medium text-stone-600 bg-stone-100 hover:bg-stone-200 transition-colors">
                            ← Kembali
                        </button>
                        <button
                            type="button"
                            @click="tryNext"
                            :disabled="Object.keys(step2Errors).length > 0"
                            class="flex-1 py-3.5 rounded-2xl text-sm font-bold text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 active:scale-[0.99]"
                            style="background-color: #D4A373"
                        >
                            Lanjut →
                        </button>
                    </div>
                </div>
                </transition>

                <!-- ── STEP 3: Slug ───────────────────────────────────── -->
                <transition name="slide" mode="out-in">
                <div v-if="step === 3" key="step3" class="space-y-5">

                    <div class="text-center py-2">
                        <p class="text-xs text-stone-400">Undanganmu akan bisa dibuka di:</p>
                        <p class="text-sm font-mono font-semibold text-stone-700 mt-1">
                            theday.id/<span class="text-amber-600">{{ form.skip_slug ? '...' : (form.slug || 'nama-slug') }}</span>
                        </p>
                    </div>

                    <!-- Skip slug checkbox -->
                    <label class="flex items-center gap-3 p-4 rounded-2xl border cursor-pointer transition-colors"
                           :class="form.skip_slug ? 'border-amber-300 bg-amber-50/50' : 'border-stone-200 bg-white'">
                        <div class="w-5 h-5 rounded flex items-center justify-center flex-shrink-0 border-2 transition-colors"
                             :style="form.skip_slug ? 'background-color:#D4A373; border-color:#D4A373' : 'border-color:#D1D5DB'">
                            <svg v-if="form.skip_slug" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <input type="checkbox" v-model="form.skip_slug" class="sr-only"/>
                        <div>
                            <p class="text-sm font-medium text-stone-700">Lewati dan isi slug nanti</p>
                            <p class="text-xs text-stone-400">Sistem akan membuat tautan sementara untukmu</p>
                        </div>
                    </label>

                    <!-- Slug input -->
                    <div v-if="!form.skip_slug" class="bg-white rounded-2xl border border-stone-100 shadow-sm p-5 space-y-3">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">
                                Slug Undangan <span class="text-red-400">*</span>
                            </label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-stone-200 bg-stone-50 text-xs text-stone-400">theday.id/</span>
                                <input
                                    v-model="form.slug"
                                    @input="sanitizeSlug"
                                    type="text"
                                    placeholder="budi-dan-rahayu"
                                    class="flex-1 px-4 py-3 rounded-r-xl border text-sm transition-colors outline-none focus:ring-2"
                                    :class="form.errors.slug
                                        ? 'border-red-300 focus:ring-red-100'
                                        : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
                                />
                            </div>
                            <p v-if="form.errors.slug" class="text-xs text-red-500">{{ form.errors.slug }}</p>
                            <p class="text-xs text-stone-400">Hanya huruf kecil, angka, dan tanda hubung (-). Tidak bisa diubah setelah undangan dipublikasi.</p>
                        </div>

                        <button
                            v-if="form.groom_name && form.bride_name"
                            type="button"
                            @click="suggestSlug"
                            class="text-xs font-medium transition-colors hover:underline"
                            style="color: #D4A373"
                        >
                            💡 Sarankan slug otomatis
                        </button>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="goBack"
                                class="flex-none px-5 py-3.5 rounded-2xl text-sm font-medium text-stone-600 bg-stone-100 hover:bg-stone-200 transition-colors">
                            ← Kembali
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing || (!form.skip_slug && !form.slug.trim())"
                            class="flex-1 py-3.5 rounded-2xl text-sm font-bold text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 active:scale-[0.98]"
                            style="background-color: #D4A373"
                        >
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Membuat undangan…
                            </span>
                            <span v-else>Buat Undanganku ✨</span>
                        </button>
                    </div>

                    <!-- Server errors summary -->
                    <div v-if="Object.keys(form.errors).length && !form.processing"
                         class="p-4 rounded-2xl bg-red-50 border border-red-200">
                        <p class="text-xs font-semibold text-red-700 mb-1">Ada yang perlu diperbaiki:</p>
                        <ul class="space-y-0.5">
                            <li v-for="(msg, field) in form.errors" :key="field" class="text-xs text-red-600">• {{ msg }}</li>
                        </ul>
                    </div>
                </div>
                </transition>

            </div>
        </div>

        <!-- ── Footer logout ────────────────────────────────────────── -->
        <div class="text-center pb-6">
            <form method="POST" :action="route('logout')" class="inline">
                <input type="hidden" name="_token" :value="$page.props.csrf_token ?? ''"/>
                <button type="submit" class="text-xs text-stone-400 hover:text-stone-600 transition-colors">
                    Keluar dari akun
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.slide-enter-from { opacity: 0; transform: translateX(16px); }
.slide-leave-to   { opacity: 0; transform: translateX(-16px); }

/* Modal / bottom-sheet */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.22s ease;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.22s ease;
}
.modal-enter-from { opacity: 0; }
.modal-leave-to   { opacity: 0; }
.modal-enter-from .relative { transform: translateY(40px); }
.modal-leave-to   .relative { transform: translateY(40px); }
</style>
