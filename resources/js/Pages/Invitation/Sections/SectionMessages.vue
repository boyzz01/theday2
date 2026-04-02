<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
    slug:         { type: String, required: true },
    messages:     { type: Array,  default: () => [] },
    primaryColor: { type: String, default: '#D4A373' },
    fontFamily:   { type: String, default: 'Playfair Display' },
});

const emit = defineEmits(['message-sent']);

const form = reactive({ name: '', message: '' });
const submitting  = ref(false);
const justSent    = ref(false);
const errors      = ref({});
const serverError = ref('');
const showForm    = ref(true);

async function submit() {
    errors.value      = {};
    serverError.value = '';
    submitting.value  = true;

    try {
        const res = await axios.post(`/${props.slug}/messages`, form);
        emit('message-sent', res.data.data);
        justSent.value    = true;
        showForm.value    = false;
        form.name         = '';
        form.message      = '';
        // Allow sending another message after 3s
        setTimeout(() => { justSent.value = false; showForm.value = true; }, 4000);
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value      = e.response.data.errors ?? {};
            serverError.value = e.response.data.message ?? '';
        } else {
            serverError.value = 'Gagal mengirim ucapan. Coba lagi.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <section class="py-20 px-6" :style="{ backgroundColor: primaryColor + '08' }">
        <div class="max-w-sm mx-auto space-y-8">

            <!-- Heading -->
            <div class="text-center space-y-2">
                <div class="flex items-center justify-center gap-2">
                    <div class="h-px w-10" :style="{ backgroundColor: primaryColor }"/>
                    <svg class="w-4 h-4" :style="{ color: primaryColor }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <div class="h-px w-10" :style="{ backgroundColor: primaryColor }"/>
                </div>
                <h2 class="text-2xl font-semibold text-stone-800" :style="{ fontFamily }">Ucapan &amp; Doa</h2>
                <p class="text-sm text-stone-400">Tinggalkan ucapan dan doa untuk pengantin</p>
            </div>

            <!-- Send form -->
            <div class="bg-white rounded-3xl p-5 shadow-sm border" :style="{ borderColor: primaryColor + '20' }">
                <!-- Just sent -->
                <div v-if="justSent" class="text-center py-4 space-y-2">
                    <div class="text-3xl">💌</div>
                    <p class="text-sm font-medium text-stone-700">Ucapan terkirim!</p>
                </div>

                <form v-else @submit.prevent="submit" class="space-y-3">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Nama Anda"
                        class="w-full px-4 py-3 rounded-2xl border text-sm focus:outline-none transition"
                        :class="errors.name ? 'border-red-300' : 'border-stone-200'"
                    />
                    <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] }}</p>

                    <textarea
                        v-model="form.message"
                        rows="3"
                        placeholder="Tuliskan ucapan & doa Anda…"
                        class="w-full px-4 py-3 rounded-2xl border text-sm focus:outline-none transition resize-none"
                        :class="errors.message ? 'border-red-300' : 'border-stone-200'"
                    />
                    <p v-if="errors.message" class="text-xs text-red-500">{{ errors.message[0] }}</p>
                    <p v-if="serverError" class="text-xs text-red-500">{{ serverError }}</p>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="w-full py-3 rounded-2xl text-white text-sm font-semibold transition-all active:scale-95 disabled:opacity-60"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        {{ submitting ? 'Mengirim…' : 'Kirim Ucapan' }}
                    </button>
                </form>
            </div>

            <!-- Messages list -->
            <div v-if="messages.length" class="space-y-3">
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="bg-white rounded-2xl px-5 py-4 shadow-sm border"
                    :style="{ borderColor: primaryColor + '15' }"
                >
                    <div class="flex items-start gap-3">
                        <!-- Avatar initial -->
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-semibold flex-shrink-0"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            {{ msg.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-stone-800 truncate">{{ msg.name }}</p>
                                <p class="text-xs text-stone-400 flex-shrink-0">{{ msg.created_at }}</p>
                            </div>
                            <p class="text-sm text-stone-600 mt-1 leading-relaxed">{{ msg.message }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="text-center text-sm text-stone-400">
                Belum ada ucapan. Jadilah yang pertama!
            </p>
        </div>
    </section>
</template>
