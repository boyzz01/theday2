<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String  },
});

const user = usePage().props.auth.user;

const form = useForm({
    name:  user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-sm font-semibold text-stone-800">Informasi Profil</h2>
            <p class="text-xs text-stone-400 mt-0.5">Perbarui nama dan alamat email akun kamu.</p>
        </div>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-4">

            <div>
                <label for="name" class="block text-sm font-medium text-stone-700 mb-1.5">Nama Lengkap</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    class="w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition-colors focus:ring-2"
                    :class="form.errors.name
                        ? 'border-red-300 focus:ring-red-100 bg-red-50/50'
                        : 'border-stone-200 focus:ring-[#92A89C]/20 focus:border-[#92A89C]/50 bg-white'"
                />
                <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    disabled
                    autocomplete="username"
                    class="w-full px-4 py-2.5 rounded-xl border text-sm bg-stone-50 text-stone-400 cursor-not-allowed border-stone-200"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null"
                 class="flex items-start gap-2 px-4 py-3 rounded-xl bg-[#92A89C]/10 border border-[#B8C7BF]/50 text-sm text-[#73877C]">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    Email belum diverifikasi.
                    <Link :href="route('verification.send')" method="post" as="button"
                          class="underline font-medium hover:text-[#2C2417]">
                        Kirim ulang email verifikasi.
                    </Link>
                </span>
            </div>

            <div v-if="status === 'verification-link-sent'"
                 class="flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Link verifikasi baru telah dikirim ke email kamu.
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90 disabled:opacity-60"
                    style="background-color: #92A89C"
                >
                    <span v-if="form.processing">Menyimpan…</span>
                    <span v-else>Simpan Perubahan</span>
                </button>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-medium">Tersimpan!</p>
                </Transition>
            </div>

        </form>
    </section>
</template>
