<script setup>
const props = defineProps({
    events:      { type: Object, required: true }, // ref([])
    addEvent:    { type: Function, required: true },
    removeEvent: { type: Function, required: true },
    moveEvent:   { type: Function, required: true },
});

const dragState = { from: null };

function onDragStart(index) {
    dragState.from = index;
}

function onDrop(index) {
    if (dragState.from !== null && dragState.from !== index) {
        props.moveEvent(dragState.from, index);
    }
    dragState.from = null;
}
</script>

<template>
    <div class="p-6 space-y-6">

        <div>
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                Acara & Lokasi
            </h2>
            <p class="text-sm text-stone-400 mt-0.5">Tambahkan satu atau beberapa rangkaian acara</p>
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
                    <span class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-lg">
                        Acara {{ index + 1 }}
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
                        <label class="block text-xs font-medium text-stone-600">Nama Acara <span class="text-red-400">*</span></label>
                        <input v-model="event.event_name" type="text" placeholder="Akad Nikah / Resepsi"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">Tanggal <span class="text-red-400">*</span></label>
                        <input v-model="event.event_date" type="date"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">Waktu Mulai</label>
                        <input v-model="event.start_time" type="time"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">Waktu Selesai</label>
                        <input v-model="event.end_time" type="time"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">Nama Venue <span class="text-red-400">*</span></label>
                        <input v-model="event.venue_name" type="text" placeholder="Gedung Serbaguna XYZ"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-stone-600">Link Google Maps</label>
                        <input v-model="event.maps_url" type="url" placeholder="https://maps.google.com/..."
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white"/>
                    </div>
                </div>

                <!-- Address (full width) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-stone-600">Alamat Lengkap</label>
                    <textarea v-model="event.venue_address" rows="2"
                              placeholder="Jl. Contoh No. 1, Kecamatan, Kota"
                              class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition resize-none bg-white"/>
                </div>
            </div>
        </div>

        <!-- Add event button -->
        <button
            @click="addEvent"
            class="w-full py-3 rounded-2xl border-2 border-dashed border-stone-200 text-sm font-medium text-stone-500 hover:text-amber-700 hover:border-amber-300 hover:bg-amber-50/50 transition-all flex items-center justify-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Acara
        </button>
    </div>
</template>
