<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Array,  default: () => [] },
})
const emit   = defineEmits(['update:modelValue'])
const saving = ref(null)  // event id currently saving
const errors = ref({})

async function saveEvent(event) {
    saving.value = event.id
    const { [event.id]: _, ...rest } = errors.value
    errors.value = rest
    try {
        await axios.put(`/api/invitations/${props.invitationId}/events/${event.id}`, {
            event_name:    event.event_name,
            event_date:    event.event_date,
            start_time:    event.start_time || null,
            end_time:      event.end_time   || null,
            venue_name:    event.venue_name,
            venue_address: event.venue_address || null,
            maps_url:      event.maps_url    || null,
        })
        emit('update:modelValue', props.modelValue.map(e => e.id === event.id ? { ...event } : e))
    } catch (err) {
        errors.value[event.id] = err.response?.data?.message ?? 'Gagal menyimpan.'
    } finally {
        saving.value = null
    }
}
</script>

<template>
    <div class="space-y-6">
        <div v-for="event in modelValue" :key="event.id" class="space-y-3 pb-5 border-b border-stone-100 last:border-0">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">{{ event.event_name || 'Acara' }}</p>

            <div class="space-y-2">
                <input
                    v-model="event.event_name"
                    type="text"
                    placeholder="Nama acara (mis. Akad Nikah)"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <input
                    v-model="event.event_date"
                    type="date"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <div class="flex gap-2">
                    <input
                        v-model="event.start_time"
                        type="time"
                        placeholder="Mulai"
                        class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                    />
                    <input
                        v-model="event.end_time"
                        type="time"
                        placeholder="Selesai"
                        class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                    />
                </div>
                <input
                    v-model="event.venue_name"
                    type="text"
                    placeholder="Nama venue"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <input
                    v-model="event.venue_address"
                    type="text"
                    placeholder="Alamat lengkap"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
                <input
                    v-model="event.maps_url"
                    type="url"
                    placeholder="Link Google Maps"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]"
                />
            </div>

            <p v-if="errors[event.id]" class="text-xs text-red-400">{{ errors[event.id] }}</p>

            <button
                type="button"
                @click="saveEvent(event)"
                :disabled="saving === event.id"
                class="w-full py-2 rounded-xl text-xs font-bold text-white bg-[#92A89C] hover:opacity-90 disabled:opacity-60 transition-all"
            >
                {{ saving === event.id ? 'Menyimpan...' : 'Simpan' }}
            </button>
        </div>

        <p v-if="!modelValue.length" class="text-xs text-stone-400 text-center py-4">Belum ada acara.</p>
    </div>
</template>
