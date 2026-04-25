<!-- resources/js/Components/invitation/templates/scene/content/SceneContentGuestbook.vue -->
<script setup>
defineProps({
    messages:      { type: Array,   default: () => [] },
    msgForm:       { type: Object,  required: true },
    msgSubmitting: { type: Boolean, default: false },
    msgSuccess:    { type: Boolean, default: false },
    msgError:      { type: String,  default: '' },
    mode:          { type: String,  default: 'list' },
})
const emit = defineEmits(['submit'])
</script>

<template>
    <div>
        <!-- Form mode -->
        <form v-if="mode === 'form'" @submit.prevent="emit('submit')" class="space-y-3">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nama</label>
                <input
                    v-model="msgForm.name"
                    placeholder="Nama kamu"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Ucapan & Doa</label>
                <textarea
                    v-model="msgForm.message"
                    placeholder="Tulis ucapan dan doa untuk kedua mempelai..."
                    required
                    rows="4"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-pink-300 resize-none"
                />
            </div>
            <p v-if="msgError" class="text-xs text-red-500">{{ msgError }}</p>
            <p v-if="msgSuccess" class="text-xs text-green-600">Ucapan terkirim! 🎉</p>
            <button
                type="submit"
                :disabled="msgSubmitting"
                class="w-full bg-pink-400 text-white rounded-lg py-2.5 text-sm font-semibold disabled:opacity-50"
            >
                {{ msgSubmitting ? 'Mengirim...' : 'Kirim Ucapan' }}
            </button>
        </form>

        <!-- List mode -->
        <div v-else class="space-y-3">
            <p v-if="!messages.length" class="text-center text-gray-400 py-8">
                Belum ada ucapan. Jadilah yang pertama! 🌸
            </p>
            <div
                v-for="msg in messages"
                :key="msg.id ?? msg.name"
                class="bg-gray-50 rounded-xl px-4 py-3"
            >
                <p class="font-semibold text-sm text-gray-800">{{ msg.name }}</p>
                <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{{ msg.message }}</p>
            </div>
        </div>
    </div>
</template>
