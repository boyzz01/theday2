<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
    isModal: { type: Boolean, default: false },
});

const emit = defineEmits(['authenticated', 'switchTab']);

const form = reactive({
    email:    '',
    password: '',
    remember: false,
});

const errors    = reactive({});
const loading   = ref(false);
const showPass  = ref(false);

async function submit() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.post('/login', {
            email:    form.email,
            password: form.password,
            remember: form.remember,
        });
        emit('authenticated', res.data?.redirect ?? null);
    } catch (e) {
        const errs = e.response?.data?.errors ?? {};
        Object.assign(errors, errs);
        if (e.response?.data?.message && ! Object.keys(errs).length) {
            errors.email = [e.response.data.message];
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">

        <!-- Email -->
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1.5">Email</label>
            <input
                v-model="form.email"
                type="email"
                required
                autocomplete="username"
                placeholder="nama@email.com"
                class="w-full px-4 py-2.5 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                :class="errors.email
                    ? 'border-red-300 focus:ring-red-100 bg-red-50'
                    : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
            />
            <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email[0] }}</p>
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-sm font-medium text-stone-700">Password</label>
                <a href="/forgot-password" class="text-xs text-stone-400 hover:text-stone-600 transition-colors">
                    Lupa password?
                </a>
            </div>
            <div class="relative">
                <input
                    v-model="form.password"
                    :type="showPass ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full px-4 py-2.5 pr-10 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                    :class="errors.password
                        ? 'border-red-300 focus:ring-red-100 bg-red-50'
                        : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
                />
                <button
                    type="button"
                    @click="showPass = !showPass"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600"
                    tabindex="-1"
                >
                    <svg v-if="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password[0] }}</p>
        </div>

        <!-- Remember me -->
        <label class="flex items-center gap-2 cursor-pointer">
            <input
                v-model="form.remember"
                type="checkbox"
                class="w-4 h-4 rounded border-stone-300 text-amber-500 focus:ring-amber-300"
            />
            <span class="text-sm text-stone-500">Ingat saya</span>
        </label>

        <!-- Submit -->
        <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-60 disabled:cursor-not-allowed"
            style="background-color: #C8A26B"
        >
            <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Masuk…
            </span>
            <span v-else>Masuk</span>
        </button>

        <!-- Switch tab link (only in modal) -->
        <p v-if="isModal" class="text-center text-xs text-stone-400">
            Belum punya akun?
            <button type="button" @click="emit('switchTab', 'register')" class="font-semibold underline text-stone-600 hover:text-stone-800">
                Daftar gratis
            </button>
        </p>
        <p v-else class="text-center text-xs text-stone-400">
            Belum punya akun?
            <a href="/register" class="font-semibold underline text-stone-600 hover:text-stone-800">Daftar gratis</a>
        </p>
    </form>
</template>
