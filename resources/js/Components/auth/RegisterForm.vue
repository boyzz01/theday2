<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    isModal: { type: Boolean, default: false },
});

const emit = defineEmits(['authenticated', 'switchTab']);

const form = reactive({
    name:                  '',
    phone:                 '',
    email:                 '',
    password:              '',
    password_confirmation: '',
    agree:                 false,
});

const errors       = reactive({});
const loading      = ref(false);
const showPass     = ref(false);
const showConfirm  = ref(false);
const emailChecking = ref(false);
const emailStatus   = ref(null); // null | 'available' | 'taken'

// ── Phone auto-format ────────────────────────────────────────────────────────

function formatPhone() {
    let v = form.phone.replace(/\D/g, '');
    if (v.startsWith('0')) v = '+62' + v.slice(1);
    else if (v.startsWith('62')) v = '+' + v;
    form.phone = v;
}

// ── Password strength ────────────────────────────────────────────────────────

const strength = computed(() => {
    const p = form.password;
    if (! p) return 0;
    let score = 0;
    if (p.length >= 8)  score++;
    if (p.length >= 12) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    return score; // 0–5
});

const strengthLabel = computed(() => {
    if (strength.value === 0) return '';
    if (strength.value <= 2)  return { text: 'Lemah',   color: '#EF4444' };
    if (strength.value <= 3)  return { text: 'Cukup',   color: '#92A89C' };
    return                           { text: 'Kuat',    color: '#22C55E' };
});

const strengthBars = computed(() =>
    [1, 2, 3, 4, 5].map(n => ({
        filled: n <= strength.value,
        color:  strengthLabel.value?.color ?? '#E5E7EB',
    }))
);

// ── Email availability check ─────────────────────────────────────────────────

async function checkEmail() {
    if (! form.email || errors.email) return;
    emailChecking.value = true;
    emailStatus.value   = null;
    try {
        const res = await axios.post('/api/auth/check-email', { email: form.email });
        emailStatus.value = res.data.available ? 'available' : 'taken';
        if (! res.data.available) {
            errors.email = ['Email sudah terdaftar.'];
        }
    } catch {
        // ignore — server will validate on submit
    } finally {
        emailChecking.value = false;
    }
}

// ── Submit ───────────────────────────────────────────────────────────────────

async function submit() {
    Object.keys(errors).forEach(k => delete errors[k]);

    if (! form.agree) {
        errors.agree = ['Harap setujui syarat & ketentuan.'];
        return;
    }
    if (form.password !== form.password_confirmation) {
        errors.password_confirmation = ['Konfirmasi password tidak cocok.'];
        return;
    }

    loading.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.post('/register', {
            name:                  form.name,
            phone:                 form.phone,
            email:                 form.email,
            password:              form.password,
            password_confirmation: form.password_confirmation,
        });
        emit('authenticated', res.data?.redirect ?? null);
    } catch (e) {
        const errs = e.response?.data?.errors ?? {};
        Object.assign(errors, errs);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">

        <!-- Nama Lengkap -->
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1.5">Nama Lengkap</label>
            <input
                v-model="form.name"
                type="text"
                required
                autocomplete="name"
                placeholder="Nama kamu"
                class="w-full px-4 py-2.5 rounded-xl border text-sm outline-none focus:ring-2 transition-colors"
                :class="errors.name
                    ? 'border-red-300 focus:ring-red-100 bg-red-50'
                    : 'border-stone-200 focus:ring-[#92A89C]/20 focus:border-[#92A89C]/50'"
            />
            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1.5">Email</label>
            <div class="relative">
                <input
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    placeholder="nama@email.com"
                    @blur="checkEmail"
                    class="w-full px-4 py-2.5 pr-9 rounded-xl border text-sm outline-none focus:ring-2 transition-colors"
                    :class="errors.email
                        ? 'border-red-300 focus:ring-red-100 bg-red-50'
                        : emailStatus === 'available'
                            ? 'border-green-300 focus:ring-green-100'
                            : 'border-stone-200 focus:ring-[#92A89C]/20 focus:border-[#92A89C]/50'"
                />
                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg v-if="emailChecking" class="w-4 h-4 animate-spin text-stone-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else-if="emailStatus === 'available'" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email[0] }}</p>
        </div>

        <!-- No. WhatsApp -->
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1.5">No. WhatsApp</label>
            <input
                v-model="form.phone"
                type="tel"
                required
                autocomplete="tel"
                placeholder="08xxxxxxxxxx"
                @blur="formatPhone"
                class="w-full px-4 py-2.5 rounded-xl border text-sm outline-none focus:ring-2 transition-colors"
                :class="errors.phone
                    ? 'border-red-300 focus:ring-red-100 bg-red-50'
                    : 'border-stone-200 focus:ring-[#92A89C]/20 focus:border-[#92A89C]/50'"
            />
            <p v-if="errors.phone" class="mt-1 text-xs text-red-500">{{ errors.phone[0] }}</p>
        </div>

        <!-- Password -->
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1.5">Password</label>
            <div class="relative">
                <input
                    v-model="form.password"
                    :type="showPass ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="Min. 8 karakter"
                    class="w-full px-4 py-2.5 pr-10 rounded-xl border text-sm outline-none focus:ring-2 transition-colors"
                    :class="errors.password
                        ? 'border-red-300 focus:ring-red-100 bg-red-50'
                        : 'border-stone-200 focus:ring-[#92A89C]/20 focus:border-[#92A89C]/50'"
                />
                <button type="button" @click="showPass = !showPass"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600" tabindex="-1">
                    <svg v-if="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>

            <!-- Strength indicator -->
            <div v-if="form.password" class="mt-2 flex items-center gap-2">
                <div class="flex gap-1 flex-1">
                    <div
                        v-for="(bar, i) in strengthBars"
                        :key="i"
                        class="h-1 flex-1 rounded-full transition-all duration-300"
                        :style="{ backgroundColor: bar.filled ? bar.color : '#E5E7EB' }"
                    />
                </div>
                <span class="text-xs font-medium" :style="{ color: strengthLabel?.color }">
                    {{ strengthLabel?.text }}
                </span>
            </div>

            <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password[0] }}</p>
        </div>

        <!-- Konfirmasi Password -->
        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1.5">Konfirmasi Password</label>
            <div class="relative">
                <input
                    v-model="form.password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password"
                    class="w-full px-4 py-2.5 pr-10 rounded-xl border text-sm outline-none focus:ring-2 transition-colors"
                    :class="errors.password_confirmation
                        ? 'border-red-300 focus:ring-red-100 bg-red-50'
                        : 'border-stone-200 focus:ring-[#92A89C]/20 focus:border-[#92A89C]/50'"
                />
                <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600" tabindex="-1">
                    <svg v-if="!showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            <p v-if="errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ errors.password_confirmation[0] }}</p>
        </div>

        <!-- Agree -->
        <div>
            <label class="flex items-start gap-2 cursor-pointer">
                <input
                    v-model="form.agree"
                    type="checkbox"
                    class="mt-0.5 w-4 h-4 rounded border-stone-300 text-[#92A89C] focus:ring-[#92A89C]/50 flex-shrink-0"
                />
                <span class="text-xs text-stone-500 leading-relaxed">
                    Saya setuju dengan
                    <a href="/syarat-ketentuan" target="_blank" class="underline text-stone-700 hover:text-stone-900">Syarat & Ketentuan</a>
                    dan
                    <a href="/privasi" target="_blank" class="underline text-stone-700 hover:text-stone-900">Kebijakan Privasi</a>
                    TheDay.
                </span>
            </label>
            <p v-if="errors.agree" class="mt-1 text-xs text-red-500">{{ errors.agree[0] }}</p>
        </div>

        <!-- Submit -->
        <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-60 disabled:cursor-not-allowed"
            style="background-color: #92A89C"
        >
            <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Mendaftar…
            </span>
            <span v-else>Daftar Gratis</span>
        </button>

        <!-- Switch tab -->
        <p v-if="isModal" class="text-center text-xs text-stone-400">
            Sudah punya akun?
            <button type="button" @click="emit('switchTab', 'login')" class="font-semibold underline text-stone-600 hover:text-stone-800">
                Masuk
            </button>
        </p>
        <p v-else class="text-center text-xs text-stone-400">
            Sudah punya akun?
            <a href="/login" class="font-semibold underline text-stone-600 hover:text-stone-800">Masuk</a>
        </p>
    </form>
</template>
