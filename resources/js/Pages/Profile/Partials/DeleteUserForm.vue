<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput          = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError:   () => passwordInput.value?.focus(),
        onFinish:  () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-sm font-semibold text-red-700">Hapus Akun</h2>
            <p class="text-xs text-stone-400 mt-0.5">
                Setelah akun dihapus, semua data akan hilang permanen dan tidak dapat dipulihkan.
            </p>
        </div>

        <button
            @click="confirmUserDeletion"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-red-500 hover:bg-red-600 transition-colors"
        >
            Hapus Akun Saya
        </button>

        <!-- Confirm modal -->
        <Transition name="modal">
            <div v-if="confirmingUserDeletion"
                 class="fixed inset-0 z-50 flex items-center justify-center px-4"
                 style="background: rgba(0,0,0,0.4); backdrop-filter: blur(2px)"
                 @click.self="closeModal">

                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">

                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-stone-900">Hapus akun secara permanen?</h3>
                            <p class="text-sm text-stone-500 mt-1">
                                Semua undangan, data tamu, dan pengaturan akan dihapus dan tidak bisa dikembalikan.
                                Masukkan password untuk konfirmasi.
                            </p>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="delete-password" class="block text-sm font-medium text-stone-700 mb-1.5">Password</label>
                        <input
                            id="delete-password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="Masukkan password kamu"
                            @keyup.enter="deleteUser"
                            class="w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition-colors focus:ring-2"
                            :class="form.errors.password
                                ? 'border-red-300 focus:ring-red-100 bg-red-50/50'
                                : 'border-stone-200 focus:ring-red-100 focus:border-red-300 bg-white'"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-5 py-2.5 rounded-xl text-sm font-medium text-stone-600 border border-stone-200 hover:bg-stone-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="deleteUser"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-red-500 hover:bg-red-600 transition-colors disabled:opacity-60"
                        >
                            <span v-if="form.processing">Menghapus…</span>
                            <span v-else>Ya, Hapus Akun</span>
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </section>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
