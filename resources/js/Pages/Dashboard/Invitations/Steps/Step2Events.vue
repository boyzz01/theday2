<script setup>
import { ref, computed } from 'vue';
import { useLocale } from '@/Composables/useLocale';

const { t, locale } = useLocale();

const props = defineProps({
    events:      { type: Object, required: true }, // ref([])
    addEvent:    { type: Function, required: true },
    removeEvent: { type: Function, required: true },
    moveEvent:   { type: Function, required: true },
});

const dragState = { from: null };

function onTimeInput(event, obj, key) {
    let v = event.target.value.replace(/[^0-9]/g, '');
    if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2, 4);
    event.target.value = v;
    obj[key] = v.length === 5 ? v : '';
}

function onDragStart(index) {
    dragState.from = index;
}

function onDrop(index) {
    if (dragState.from !== null && dragState.from !== index) {
        props.moveEvent(dragState.from, index);
    }
    dragState.from = null;
}

// ── Date picker ──────────────────────────────────────────────────
const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const MONTHS_EN = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS_ID   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
const DAYS_EN   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

const calMonths = computed(() => locale.value === 'id' ? MONTHS_ID : MONTHS_EN);
const calDays   = computed(() => locale.value === 'id' ? DAYS_ID : DAYS_EN);

const showDatePicker = ref(false);
const activeEvent    = ref(null);
const calToday       = new Date();
const calYear        = ref(calToday.getFullYear());
const calMonth       = ref(calToday.getMonth());

function openDatePicker(event) {
    activeEvent.value = event;
    if (event.event_date) {
        const [y, m] = event.event_date.split('-').map(Number);
        calYear.value  = y;
        calMonth.value = m - 1;
    } else {
        calYear.value  = calToday.getFullYear();
        calMonth.value = calToday.getMonth();
    }
    showDatePicker.value = true;
}
function closeDatePicker() { showDatePicker.value = false; activeEvent.value = null; }
function prevCalMonth() {
    if (calMonth.value === 0) { calMonth.value = 11; calYear.value--; }
    else calMonth.value--;
}
function nextCalMonth() {
    if (calMonth.value === 11) { calMonth.value = 0; calYear.value++; }
    else calMonth.value++;
}
const calDaysCells = computed(() => {
    const first = new Date(calYear.value, calMonth.value, 1).getDay();
    const total = new Date(calYear.value, calMonth.value + 1, 0).getDate();
    const cells = [];
    for (let i = 0; i < first; i++) cells.push(null);
    for (let d = 1; d <= total; d++) cells.push(d);
    return cells;
});
function pickDay(day) {
    if (!day || !activeEvent.value) return;
    const m = String(calMonth.value + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    activeEvent.value.event_date = `${calYear.value}-${m}-${d}`;
}
function isPickedDay(day) {
    if (!day || !activeEvent.value?.event_date) return false;
    const [y, m, d] = activeEvent.value.event_date.split('-').map(Number);
    return y === calYear.value && m === calMonth.value + 1 && d === day;
}
function calDisplayDate(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString(locale.value === 'id' ? 'id-ID' : 'en-US', { day: 'numeric', month: 'long', year: 'numeric' });
}
const currentPickerDate = computed(() => activeEvent.value?.event_date ?? '');
</script>

<template>
    <div class="p-6 space-y-6">

        <div>
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                {{ t('dashboard.invitations.step2Events.title') }}
            </h2>
            <p class="text-sm text-stone-400 mt-0.5">{{ t('dashboard.invitations.step2Events.subtitle') }}</p>
        </div>

        <!-- Event cards -->
        <div class="space-y-4">
            <div
                v-for="(event, index) in events"
                :key="event._key"
                draggable="true"
                @dragstart="onDragStart(index)"
                @dragover.prevent
                @drop="onDrop(index)"
                class="relative group rounded-2xl border border-stone-200 bg-stone-50/50 p-5 space-y-4 transition-all hover:border-stone-300 hover:shadow-sm cursor-grab active:cursor-grabbing"
            >
                <!-- Header row -->
                <div class="flex items-center gap-3">
                    <!-- Drag handle -->
                    <div class="text-stone-300 group-hover:text-stone-400 flex-shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zM16 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                    </div>

                    <!-- Badge -->
                    <span class="text-xs font-semibold text-[#73877C] bg-[#92A89C]/10 border border-[#B8C7BF]/50 px-2 py-0.5 rounded-lg">
                        {{ t('dashboard.invitations.step2Events.eventBadge', { n: index + 1 }) }}
                    </span>

                    <!-- Move buttons -->
                    <div class="flex gap-1 ml-auto">
                        <button
                            v-if="index > 0"
                            @click="moveEvent(index, index - 1)"
                            class="p-1 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors"
                            title="Naik"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        <button
                            v-if="index < events.length - 1"
                            @click="moveEvent(index, index + 1)"
                            class="p-1 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors"
                            title="Turun"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <button
                            v-if="events.length > 1"
                            @click="removeEvent(index)"
                            class="p-1 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                            title="Hapus"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Fields grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">{{ t('dashboard.invitations.step2Events.eventName') }} <span class="text-red-400">*</span></label>
                        <input v-model="event.event_name" type="text" placeholder="Akad Nikah / Resepsi"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">{{ t('dashboard.invitations.step2Events.date') }} <span class="text-red-400">*</span></label>
                        <button type="button" @click="openDatePicker(event)"
                                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-left transition-colors hover:border-[#92A89C]/50 bg-white">
                            <span v-if="event.event_date" class="text-stone-800">{{ calDisplayDate(event.event_date) }}</span>
                            <span v-else class="text-stone-400">{{ t('dashboard.invitations.step2Events.pickDate') }}</span>
                        </button>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">{{ t('dashboard.invitations.step2Events.startTime') }}</label>
                        <input :value="event.start_time ? event.start_time.slice(0, 5) : ''"
                               @input="onTimeInput($event, event, 'start_time')"
                               type="text" maxlength="5" placeholder="HH:MM"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">{{ t('dashboard.invitations.step2Events.endTime') }}</label>
                        <input :value="event.end_time ? event.end_time.slice(0, 5) : ''"
                               @input="onTimeInput($event, event, 'end_time')"
                               type="text" maxlength="5" placeholder="HH:MM"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">{{ t('dashboard.invitations.step2Events.venueName') }} <span class="text-red-400">*</span></label>
                        <input v-model="event.venue_name" type="text" placeholder="Gedung Serbaguna XYZ"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">{{ t('dashboard.invitations.step2Events.mapsUrl') }}</label>
                        <input v-model="event.maps_url" type="url" placeholder="https://maps.google.com/..."
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                    </div>
                </div>

                <!-- Address (full width) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-stone-600">{{ t('dashboard.invitations.step2Events.address') }}</label>
                    <textarea v-model="event.venue_address" rows="2"
                              placeholder="Jl. Contoh No. 1, Kecamatan, Kota"
                              class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition resize-none bg-white"/>
                </div>
            </div>
        </div>

        <!-- Add event button -->
        <button
            @click="addEvent"
            class="w-full py-3 rounded-2xl border-2 border-dashed border-stone-200 text-sm font-medium text-stone-500 hover:text-[#73877C] hover:border-[#92A89C]/50 hover:bg-[#92A89C]/10 transition-all flex items-center justify-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ t('dashboard.invitations.step2Events.addEvent') }}
        </button>
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
                            <p class="text-sm font-bold text-stone-800">{{ t('dashboard.invitations.step2Events.datePickerTitle') }}</p>
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
                        <span class="text-sm font-semibold text-stone-700">{{ calMonths[calMonth] }} {{ calYear }}</span>
                        <button type="button" @click="nextCalMonth"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-stone-500 hover:bg-stone-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-7 px-4 pb-1">
                        <div v-for="d in calDays" :key="d"
                             class="text-center text-xs font-semibold py-1"
                             :class="(locale === 'id' ? d === 'Min' : d === 'Sun') ? 'text-rose-400' : 'text-stone-400'">{{ d }}</div>
                    </div>
                    <div class="grid grid-cols-7 px-4 pb-3 gap-y-1">
                        <div v-for="(day, i) in calDaysCells" :key="i" class="flex items-center justify-center aspect-square">
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
                            <span v-if="currentPickerDate">{{ t('dashboard.invitations.step2Events.datePickerConfirm', { date: calDisplayDate(currentPickerDate) }) }}</span>
                            <span v-else>{{ t('dashboard.invitations.step2Events.datePickerPrompt') }}</span>
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
