<!-- resources/js/Components/invitation/templates/scene/content/SceneContentRsvp.vue -->
<script setup>
defineProps({
    rsvpForm:       { type: Object,  required: true },
    rsvpSubmitting: { type: Boolean, default: false },
    rsvpSuccess:    { type: Boolean, default: false },
    rsvpError:      { type: String,  default: '' },
})
const emit = defineEmits(['submit'])
</script>

<template>
    <div>
        <div v-if="rsvpSuccess" class="text-center py-8">
            <p class="text-2xl mb-2">🎉</p>
            <p class="font-semibold text-gray-800">Terima kasih!</p>
            <p class="text-sm text-gray-500 mt-1">Konfirmasi kehadiran kamu sudah kami terima.</p>
        </div>

        <form v-else @submit.prevent="emit('submit')" class="space-y-3">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nama Lengkap</label>
                <input
                    v-model="rsvpForm.guest_name"
                    placeholder="Nama lengkap kamu"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Konfirmasi Kehadiran</label>
                <select
                    v-model="rsvpForm.attendance"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300 bg-white"
                >
                    <option value="">Pilih konfirmasi</option>
                    <option value="hadir">Hadir</option>
                    <option value="tidak_hadir">Tidak Hadir</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Jumlah Tamu</label>
                <input
                    v-model.number="rsvpForm.guest_count"
                    type="number"
                    min="1"
                    max="10"
                    placeholder="1"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Catatan (opsional)</label>
                <textarea
                    v-model="rsvpForm.notes"
                    placeholder="Catatan untuk pengantin..."
                    rows="2"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300 resize-none"
                />
            </div>

            <p v-if="rsvpError" class="text-xs text-red-500">{{ rsvpError }}</p>

            <button
                type="submit"
                :disabled="rsvpSubmitting"
                class="w-full bg-pink-400 text-white rounded-lg py-2.5 text-sm font-semibold disabled:opacity-50"
            >
                {{ rsvpSubmitting ? 'Mengirim...' : 'Kirim RSVP' }}
            </button>
        </form>
    </div>
</template>
