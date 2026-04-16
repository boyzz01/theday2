<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    slug:         { type: String,  required: true },
    messages:     { type: Array,   default: () => [] },
    primaryColor: { type: String,  default: '#D4A373' },
    fontFamily:   { type: String,  default: 'Playfair Display' },
    isDemo:       { type: Boolean, default: false },
});

const emit = defineEmits(['message-sent']);

const form = reactive({ name: '', message: '', is_anonymous: false });
const submitting  = ref(false);
const justSent    = ref(false);
const errors      = ref({});
const serverError = ref('');
const rateLimited = ref(false);

// Live feed
const liveFeed   = ref([...props.messages]);
const expandedIds = ref(new Set());

// Polling for new messages (every 15s)
let pollTimer = null;

async function pollMessages() {
    if (props.isDemo) return;
    try {
        const res = await axios.get(`/${props.slug}/messages`);
        liveFeed.value = res.data.data ?? [];
    } catch { /* silent */ }
}

onMounted(() => {
    if (!props.isDemo) {
        pollTimer = setInterval(pollMessages, 15000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

async function submit() {
    if (props.isDemo) {
        serverError.value = 'Ini hanya demo — ucapan tidak dapat dikirim.';
        return;
    }
    errors.value      = {};
    serverError.value = '';
    rateLimited.value = false;
    submitting.value  = true;

    try {
        const res = await axios.post(`/${props.slug}/messages`, {
            name:         form.name,
            message:      form.message,
            is_anonymous: form.is_anonymous,
        });

        // Optimistic prepend to live feed
        liveFeed.value.unshift(res.data.data);
        emit('message-sent', res.data.data);

        justSent.value    = true;
        form.name         = '';
        form.message      = '';
        form.is_anonymous = false;
        setTimeout(() => { justSent.value = false; }, 4000);
    } catch (e) {
        if (e.response?.status === 429) {
            rateLimited.value = true;
            serverError.value = e.response.data.message ?? 'Kamu sudah mengirim ucapan. Terima kasih! 🤍';
        } else if (e.response?.status === 422) {
            errors.value      = e.response.data.errors ?? {};
            serverError.value = e.response.data.message ?? '';
        } else {
            serverError.value = 'Gagal mengirim ucapan. Coba lagi.';
        }
    } finally {
        submitting.value = false;
    }
}

function toggleExpand(id) {
    if (expandedIds.value.has(id)) expandedIds.value.delete(id);
    else expandedIds.value.add(id);
    // Force reactivity
    expandedIds.value = new Set(expandedIds.value);
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

            <!-- Demo notice -->
            <div v-if="isDemo"
                 class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-medium"
                 style="background:#FEF3C7; color:#92400E">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Ucapan di bawah adalah contoh. Formulir tidak dapat dikirim di mode demo.
            </div>

            <!-- Send form -->
            <div class="bg-white rounded-3xl p-5 shadow-sm border" :style="{ borderColor: primaryColor + '20' }">

                <!-- Rate limited -->
                <div v-if="rateLimited" class="text-center py-4 space-y-2">
                    <div class="text-3xl">🤍</div>
                    <p class="text-sm font-medium text-stone-700">{{ serverError }}</p>
                </div>

                <!-- Just sent -->
                <div v-else-if="justSent" class="text-center py-4 space-y-2">
                    <div class="text-3xl">💌</div>
                    <p class="text-sm font-medium text-stone-700">Ucapanmu sudah terkirim 🤍</p>
                </div>

                <form v-else @submit.prevent="submit" class="space-y-3">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Nama Anda"
                        maxlength="100"
                        class="w-full px-4 py-3 rounded-2xl border text-sm focus:outline-none transition"
                        :class="errors.name ? 'border-red-300' : 'border-stone-200'"
                    />
                    <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] }}</p>

                    <textarea
                        v-model="form.message"
                        rows="3"
                        maxlength="500"
                        placeholder="Tuliskan ucapan & doa Anda…"
                        class="w-full px-4 py-3 rounded-2xl border text-sm focus:outline-none transition resize-none"
                        :class="errors.message ? 'border-red-300' : 'border-stone-200'"
                    />
                    <div class="flex items-center justify-between">
                        <p v-if="errors.message" class="text-xs text-red-500">{{ errors.message[0] }}</p>
                        <p v-else class="text-xs text-stone-300">{{ form.message.length }}/500</p>
                    </div>

                    <!-- Anonymous toggle -->
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input
                            v-model="form.is_anonymous"
                            type="checkbox"
                            class="w-4 h-4 rounded border-stone-300"
                            :style="{ accentColor: primaryColor }"
                        />
                        <span class="text-xs text-stone-500">Kirim sebagai Tamu Anonim</span>
                    </label>

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

            <!-- Messages feed -->
            <div v-if="liveFeed.length" class="space-y-3">
                <TransitionGroup name="msg-list" tag="div" class="space-y-3">
                    <div
                        v-for="msg in liveFeed"
                        :key="msg.id"
                        class="bg-white rounded-2xl px-5 py-4 shadow-sm border"
                        :style="{ borderColor: msg.is_pinned ? primaryColor + '40' : primaryColor + '15' }"
                    >
                        <!-- Pinned indicator -->
                        <div v-if="msg.is_pinned"
                             class="flex items-center gap-1 text-xs font-medium mb-2"
                             :style="{ color: primaryColor }">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            Ucapan Pilihan
                        </div>

                        <div class="flex items-start gap-3">
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
                                <p class="text-sm text-stone-600 mt-1 leading-relaxed"
                                   :class="!expandedIds.has(msg.id) ? 'line-clamp-3' : ''">
                                    {{ msg.message }}
                                </p>
                                <button
                                    v-if="msg.message.length > 120"
                                    @click="toggleExpand(msg.id)"
                                    class="text-xs mt-1 font-medium"
                                    :style="{ color: primaryColor }"
                                >
                                    {{ expandedIds.has(msg.id) ? 'Sembunyikan' : 'Lihat selengkapnya' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>

            <p v-else class="text-center text-sm text-stone-400">
                Belum ada ucapan. Jadilah yang pertama!
            </p>
        </div>
    </section>
</template>

<style scoped>
.msg-list-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}
.msg-list-enter-active {
    transition: all 0.3s ease;
}
</style>
