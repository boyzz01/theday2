<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    slug:        { type: String, required: true },
    primaryColor:{ type: String, default: '#92A89C' },
    fontFamily:  { type: String, default: 'Playfair Display' },
    coverUrl:    { type: String, default: null },
});

const emit = defineEmits(['unlocked']);

const password = ref('');
const error    = ref('');
const loading  = ref(false);

async function submit() {
    if (!password.value.trim()) return;
    loading.value = true;
    error.value   = '';
    try {
        await axios.post(`/${props.slug}/unlock`, { password: password.value });
        emit('unlocked');
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Password salah.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div
        class="min-h-screen flex flex-col items-center justify-center px-6 text-center"
        :style="{ backgroundColor: primaryColor + '12', fontFamily }"
    >
        <div
            v-if="coverUrl"
            class="absolute inset-0 bg-cover bg-center opacity-10"
            :style="{ backgroundImage: `url(${coverUrl})` }"
        />

        <div class="relative z-10 max-w-xs w-full space-y-6">
            <!-- Lock icon -->
            <div
                class="w-16 h-16 rounded-full flex items-center justify-center mx-auto"
                :style="{ backgroundColor: primaryColor + '20' }"
            >
                <svg class="w-7 h-7" :style="{ color: primaryColor }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <div>
                <h1 class="text-2xl font-semibold text-stone-800" :style="{ fontFamily }">
                    Undangan Pribadi
                </h1>
                <p class="text-sm text-stone-500 mt-1">
                    Masukkan password untuk membuka undangan ini
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-3">
                <input
                    v-model="password"
                    type="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    class="w-full px-4 py-3 rounded-2xl border text-sm text-center tracking-widest focus:outline-none transition"
                    :style="{ borderColor: error ? '#EF4444' : primaryColor + '60' }"
                    :class="{ 'border-red-400 bg-red-50': error }"
                />
                <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-3 rounded-2xl text-white text-sm font-semibold transition-all active:scale-95 disabled:opacity-60"
                    :style="{ backgroundColor: primaryColor }"
                >
                    <svg v-if="loading" class="inline w-4 h-4 animate-spin mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ loading ? 'Memverifikasi…' : 'Buka Undangan' }}
                </button>
            </form>
        </div>
    </div>
</template>
