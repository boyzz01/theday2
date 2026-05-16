<script setup>
import { computed } from 'vue';

const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);

const items = computed(() => props.data.items ?? []);

function addEvent() {
    emit('update:data', {
        ...props.data,
        items: [...items.value, {
            type: 'resepsi', title: '', date: '', start_time: '', end_time: '',
            timezone: 'Asia/Jakarta', venue_name: '', address: '', maps_url: '', note: '',
        }],
    });
}

function removeEvent(idx) {
    const newItems = items.value.filter((_, i) => i !== idx);
    emit('update:data', { ...props.data, items: newItems });
}

function updateEvent(idx, field, value) {
    const newItems = items.value.map((item, i) =>
        i === idx ? { ...item, [field]: value } : item
    );
    emit('update:data', { ...props.data, items: newItems });
}

const eventTypeOptions = [
    { value: 'akad',    label: 'Akad Nikah' },
    { value: 'resepsi', label: 'Resepsi' },
    { value: 'lainnya', label: 'Lainnya' },
];
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(event, idx) in items"
            :key="idx"
            class="rounded-2xl border border-stone-100 p-4 space-y-3"
        >
            <!-- Header row -->
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wider">
                    Acara {{ idx + 1 }}
                </h3>
                <button
                    v-if="items.length > 1"
                    @click="removeEvent(idx)"
                    class="text-xs text-red-400 hover:text-red-600 transition-colors"
                >Hapus</button>
            </div>

            <!-- Type -->
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Jenis Acara</label>
                <select
                    :value="event.type"
                    @change="updateEvent(idx, 'type', $event.target.value)"
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors bg-white"
                >
                    <option v-for="opt in eventTypeOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Nama Acara</label>
                <input
                    type="text"
                    :value="event.title"
                    @input="updateEvent(idx, 'title', $event.target.value)"
                    placeholder="Akad Nikah / Resepsi..."
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                />
            </div>

            <!-- Date + Time row -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">Tanggal <span class="text-red-400">*</span></label>
                    <input
                        type="date"
                        :value="event.date"
                        @input="updateEvent(idx, 'date', $event.target.value)"
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                    />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-1.5">Mulai</label>
                        <input
                            type="time"
                            :value="event.start_time"
                            @input="updateEvent(idx, 'start_time', $event.target.value)"
                            class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-1.5">Selesai</label>
                        <input
                            type="time"
                            :value="event.end_time"
                            @input="updateEvent(idx, 'end_time', $event.target.value)"
                            class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                        />
                    </div>
                </div>
            </div>

            <!-- Venue -->
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Nama Venue</label>
                <input
                    type="text"
                    :value="event.venue_name"
                    @input="updateEvent(idx, 'venue_name', $event.target.value)"
                    placeholder="Gedung / Masjid / dll..."
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                />
            </div>

            <!-- Address -->
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Alamat Lengkap</label>
                <textarea
                    rows="2"
                    :value="event.address"
                    @input="updateEvent(idx, 'address', $event.target.value)"
                    placeholder="Jl. Contoh No. 1, Kota..."
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors resize-none"
                />
            </div>

            <!-- Maps URL -->
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Link Google Maps</label>
                <input
                    type="url"
                    :value="event.maps_url"
                    @input="updateEvent(idx, 'maps_url', $event.target.value)"
                    placeholder="https://maps.google.com/..."
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                />
            </div>

            <!-- Note -->
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Catatan</label>
                <input
                    type="text"
                    :value="event.note"
                    @input="updateEvent(idx, 'note', $event.target.value)"
                    placeholder="Busana bebas rapi..."
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                />
            </div>
        </div>

        <!-- Add event -->
        <button
            @click="addEvent"
            class="w-full py-2.5 rounded-xl border border-dashed border-stone-200 text-sm text-stone-400 hover:border-amber-300 hover:text-amber-600 transition-colors flex items-center justify-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Acara
        </button>
    </div>
</template>
