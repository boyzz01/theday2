<script setup>
// Step 2 — Acara
// Sections: events (req), countdown (opt), live_streaming (opt), additional_info (opt)

import { ref } from 'vue';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';

const props = defineProps({
    events:          { type: Object,   required: true }, // ref([])
    sections:        { type: Object,   required: true },
    addEvent:        { type: Function, required: true },
    removeEvent:     { type: Function, required: true },
    moveEvent:       { type: Function, required: true },
    onToggleSection: { type: Function, required: true },
});

const expanded = ref('events');

function toggle(key) {
    expanded.value = expanded.value === key ? null : key;
}

const dragState = { from: null };
function onDragStart(index) { dragState.from = index; }
function onDrop(index) {
    if (dragState.from !== null && dragState.from !== index) {
        props.moveEvent(dragState.from, index);
    }
    dragState.from = null;
}

function onTimeInput(event, obj, key) {
    let v = event.target.value.replace(/[^0-9]/g, '');
    if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2, 4);
    event.target.value = v;
    obj[key] = v.length === 5 ? v : '';
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Acara</h2>
            <p class="text-sm text-stone-400 mt-0.5">Detail rangkaian acara dan informasi tambahan</p>
        </div>

        <!-- Events -->
        <SectionAccordionCard
            title="Acara & Lokasi"
            description="Tambahkan satu atau beberapa rangkaian acara"
            :is-required="sections.events?.is_required ?? true"
            :is-enabled="sections.events?.is_enabled ?? true"
            :status="sections.events?.completion_status ?? 'empty'"
            :expanded="expanded === 'events'"
            @toggle-expand="toggle('events')"
            @toggle-enabled="onToggleSection('events')"
        >
            <div class="space-y-4">
                <div
                    v-for="(event, index) in events"
                    :key="event._key"
                    draggable="true"
                    @dragstart="onDragStart(index)"
                    @dragover.prevent
                    @drop="onDrop(index)"
                    class="relative group rounded-xl border border-stone-200 bg-stone-50/50 p-4 space-y-3 transition-all hover:border-stone-300 cursor-grab active:cursor-grabbing"
                >
                    <!-- Event card header -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-lg">Acara {{ index + 1 }}</span>
                        <div class="flex gap-1 ml-auto">
                            <button v-if="index > 0" @click="moveEvent(index, index - 1)"
                                    class="p-1 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                            <button v-if="index < events.length - 1" @click="moveEvent(index, index + 1)"
                                    class="p-1 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <button v-if="events.length > 1" @click="removeEvent(index)"
                                    class="p-1 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Nama Acara *</label>
                            <input v-model="event.event_name" type="text" placeholder="Akad Nikah / Resepsi"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Tanggal *</label>
                            <input v-model="event.event_date" type="date"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Waktu Mulai</label>
                            <input :value="event.start_time ? event.start_time.slice(0,5) : ''"
                                   @input="onTimeInput($event, event, 'start_time')"
                                   type="text" maxlength="5" placeholder="HH:MM"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Waktu Selesai</label>
                            <input :value="event.end_time ? event.end_time.slice(0,5) : ''"
                                   @input="onTimeInput($event, event, 'end_time')"
                                   type="text" maxlength="5" placeholder="HH:MM"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Nama Venue *</label>
                            <input v-model="event.venue_name" type="text" placeholder="Gedung XYZ"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Link Google Maps</label>
                            <input v-model="event.maps_url" type="url" placeholder="https://maps.google.com/…"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-stone-600">Alamat Lengkap</label>
                        <textarea v-model="event.venue_address" rows="2"
                                  placeholder="Jl. Contoh No. 1, Kecamatan, Kota"
                                  class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition resize-none bg-white"/>
                    </div>
                </div>

                <button
                    @click="addEvent"
                    class="w-full py-3 rounded-xl border-2 border-dashed border-stone-200 text-sm font-medium text-stone-500 hover:text-amber-700 hover:border-amber-300 hover:bg-amber-50/50 transition-all flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Acara
                </button>
            </div>
        </SectionAccordionCard>

        <!-- Countdown (optional) -->
        <SectionAccordionCard
            title="Hitung Mundur"
            description="Tampilkan timer hitung mundur menuju hari H"
            :is-required="sections.countdown?.is_required ?? false"
            :is-enabled="sections.countdown?.is_enabled ?? true"
            :status="sections.countdown?.completion_status ?? 'complete'"
            :expanded="expanded === 'countdown'"
            @toggle-expand="toggle('countdown')"
            @toggle-enabled="onToggleSection('countdown')"
        >
            <p class="text-sm text-stone-500 py-2">
                Hitung mundur otomatis menggunakan tanggal acara pertama yang Anda isi.
                Aktifkan toggle untuk menampilkannya di undangan.
            </p>
        </SectionAccordionCard>

        <!-- Live Streaming (optional) -->
        <SectionAccordionCard
            title="Live Streaming"
            description="Link siaran langsung acara untuk tamu yang tidak hadir"
            :is-required="sections.live_streaming?.is_required ?? false"
            :is-enabled="sections.live_streaming?.is_enabled ?? false"
            :status="sections.live_streaming?.completion_status ?? 'disabled'"
            :expanded="expanded === 'live_streaming'"
            @toggle-expand="toggle('live_streaming')"
            @toggle-enabled="onToggleSection('live_streaming')"
        >
            <div class="space-y-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Link Streaming</label>
                    <input
                        v-model="sections.live_streaming.data_json.url"
                        type="url"
                        placeholder="https://youtube.com/live/..."
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Platform</label>
                    <input
                        v-model="sections.live_streaming.data_json.platform"
                        type="text"
                        placeholder="YouTube / Zoom / dll"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition"
                    />
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Additional Info (optional) -->
        <SectionAccordionCard
            title="Informasi Tambahan"
            description="Catatan khusus untuk tamu undangan"
            :is-required="sections.additional_info?.is_required ?? false"
            :is-enabled="sections.additional_info?.is_enabled ?? false"
            :status="sections.additional_info?.completion_status ?? 'disabled'"
            :expanded="expanded === 'additional_info'"
            @toggle-expand="toggle('additional_info')"
            @toggle-enabled="onToggleSection('additional_info')"
        >
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-stone-700">Catatan</label>
                <textarea
                    v-model="sections.additional_info.data_json.text"
                    rows="4"
                    placeholder="Mohon hadir tepat waktu. Dress code: Putih & Emas..."
                    class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition resize-none"
                />
            </div>
        </SectionAccordionCard>

    </div>
</template>
