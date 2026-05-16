<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Array,  default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const saving  = ref(null)   // event id being saved, or 'new'
const deleting = ref(null)  // event id being deleted
const errors  = ref({})
const adding  = ref(false)

const newForm = ref({ event_name: '', event_date: '', start_time: '', end_time: '', venue_name: '', venue_address: '', maps_url: '' })

function startAdd() {
    newForm.value = { event_name: '', event_date: '', start_time: '', end_time: '', venue_name: '', venue_address: '', maps_url: '' }
    adding.value  = true
}

function cancelAdd() {
    adding.value = false
}

async function createEvent() {
    saving.value = 'new'
    errors.value = { ...errors.value, new: null }
    try {
        const res = await axios.post(`/api/invitations/${props.invitationId}/events`, {
            event_name:    newForm.value.event_name,
            event_date:    newForm.value.event_date,
            start_time:    newForm.value.start_time  || null,
            end_time:      newForm.value.end_time    || null,
            venue_name:    newForm.value.venue_name,
            venue_address: newForm.value.venue_address || null,
            maps_url:      newForm.value.maps_url    || null,
        })
        emit('update:modelValue', [...props.modelValue, res.data.data])
        adding.value = false
    } catch (err) {
        errors.value = { ...errors.value, new: err.response?.data?.message ?? 'Gagal menambah acara.' }
    } finally {
        saving.value = null
    }
}

async function saveEvent(event) {
    saving.value = event.id
    const { [event.id]: _, ...rest } = errors.value
    errors.value = rest
    try {
        await axios.put(`/api/invitations/${props.invitationId}/events/${event.id}`, {
            event_name:    event.event_name,
            event_date:    event.event_date,
            start_time:    event.start_time  || null,
            end_time:      event.end_time    || null,
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

async function deleteEvent(event) {
    if (!confirm(`Hapus acara "${event.event_name}"?`)) return
    deleting.value = event.id
    try {
        await axios.delete(`/api/invitations/${props.invitationId}/events/${event.id}`)
        emit('update:modelValue', props.modelValue.filter(e => e.id !== event.id))
    } catch {
        alert('Gagal menghapus acara.')
    } finally {
        deleting.value = null
    }
}
</script>

<template>
    <div class="space-y-6">

        <!-- Existing events -->
        <div v-for="event in modelValue" :key="event.id" class="space-y-3 pb-5 border-b border-stone-100 last:border-0">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">{{ event.event_name || 'Acara' }}</p>
                <button
                    type="button"
                    @click="deleteEvent(event)"
                    :disabled="deleting === event.id"
                    class="text-xs text-red-400 hover:text-red-600 disabled:opacity-40"
                >{{ deleting === event.id ? '...' : 'Hapus' }}</button>
            </div>

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
                    <input v-model="event.start_time" type="time"
                        class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
                    <input v-model="event.end_time" type="time"
                        class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
                </div>
                <input v-model="event.venue_name" type="text" placeholder="Nama venue"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
                <input v-model="event.venue_address" type="text" placeholder="Alamat lengkap"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
                <input v-model="event.maps_url" type="url" placeholder="Link Google Maps"
                    class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
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

        <!-- Empty state -->
        <p v-if="!modelValue.length && !adding" class="text-xs text-stone-400 text-center py-4">Belum ada acara.</p>

        <!-- Add new event form -->
        <div v-if="adding" class="space-y-2 p-3 rounded-xl border border-[#92A89C]/30 bg-stone-50">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Acara Baru</p>
            <input v-model="newForm.event_name" type="text" placeholder="Nama acara (mis. Akad Nikah)"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="newForm.event_date" type="date"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <div class="flex gap-2">
                <input v-model="newForm.start_time" type="time"
                    class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
                <input v-model="newForm.end_time" type="time"
                    class="flex-1 px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            </div>
            <input v-model="newForm.venue_name" type="text" placeholder="Nama venue"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="newForm.venue_address" type="text" placeholder="Alamat lengkap"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="newForm.maps_url" type="url" placeholder="Link Google Maps"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />

            <p v-if="errors.new" class="text-xs text-red-400">{{ errors.new }}</p>

            <div class="flex gap-2">
                <button type="button" @click="cancelAdd"
                    class="flex-1 py-2 rounded-xl text-xs font-medium border border-stone-200 text-stone-600 hover:bg-stone-50">
                    Batal
                </button>
                <button type="button" @click="createEvent" :disabled="saving === 'new'"
                    class="flex-1 py-2 rounded-xl text-xs font-bold text-white bg-[#92A89C] hover:opacity-90 disabled:opacity-60">
                    {{ saving === 'new' ? 'Menyimpan...' : 'Tambah' }}
                </button>
            </div>
        </div>

        <!-- Add button -->
        <button
            v-if="!adding"
            type="button"
            @click="startAdd"
            class="w-full py-2.5 rounded-xl border-2 border-dashed border-stone-200 text-sm text-stone-500 hover:border-stone-300 hover:bg-stone-50 transition-colors"
        >
            + Tambah Acara
        </button>

    </div>
</template>
