<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    transactionId: { type: String, required: true },
    status:        { type: String, required: true },
});

const currentStatus = ref(props.status);
const pollCount     = ref(0);
const maxed         = ref(false);
const MAX_POLLS     = 10;
let pollTimer       = null;

const poll = async () => {
    if (pollCount.value >= MAX_POLLS) {
        clearInterval(pollTimer);
        maxed.value = true;
        return;
    }
    try {
        const { data } = await axios.get(route('payment.status', props.transactionId));
        currentStatus.value = data.status;
        pollCount.value++;
        if (data.status === 'paid' || data.status === 'failed') {
            clearInterval(pollTimer);
        }
    } catch {
        clearInterval(pollTimer);
        maxed.value = true;
    }
};

onMounted(() => {
    if (currentStatus.value === 'pending') {
        pollTimer = setInterval(poll, 3000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

const goToDashboard = () => router.visit(route('dashboard'));
const goToTransactions = () => router.visit(route('dashboard.transactions.index'));
const retryPoll = () => {
    if (pollTimer) clearInterval(pollTimer);
    maxed.value = false;
    pollCount.value = 0;
    pollTimer = setInterval(poll, 3000);
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-stone-800">Status Pembayaran</h1>
        </template>

        <div class="max-w-md mx-auto mt-8">
            <div class="rounded-2xl border p-8 text-center"
                 :class="{
                     'bg-emerald-50 border-emerald-100': currentStatus === 'paid',
                     'bg-white border-stone-100':        currentStatus === 'pending',
                     'bg-red-50 border-red-100':         currentStatus === 'failed',
                     'bg-stone-50 border-stone-100':     !['paid','pending','failed'].includes(currentStatus),
                 }">

                <!-- Paid -->
                <template v-if="currentStatus === 'paid'">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-emerald-800 mb-2">Pembayaran Berhasil!</h2>
                    <p class="text-sm text-emerald-700 mb-6">Paket kamu sudah aktif. Selamat menikmati fitur Premium.</p>
                </template>

                <!-- Pending -->
                <template v-else-if="currentStatus === 'pending'">
                    <div class="w-16 h-16 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-4 animate-pulse">
                        <svg class="w-8 h-8 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-stone-800 mb-2">Menunggu Konfirmasi</h2>
                    <p class="text-sm text-stone-500 mb-4">Pembayaran sedang diproses. Halaman ini akan diperbarui otomatis.</p>
                    <div v-if="!maxed" class="flex items-center justify-center gap-2 text-xs text-stone-400 mb-6">
                        <span class="inline-block w-2 h-2 rounded-full bg-stone-300 animate-bounce"></span>
                        <span>Memeriksa status...</span>
                    </div>
                    <div v-else class="mb-6">
                        <p class="text-xs text-stone-400 mb-3">Cek otomatis selesai. Pembayaran mungkin masih diproses.</p>
                        <button @click="retryPoll"
                                class="px-4 py-2 rounded-xl text-sm font-semibold border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cek Ulang
                        </button>
                    </div>
                </template>

                <!-- Failed -->
                <template v-else-if="currentStatus === 'failed'">
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-red-800 mb-2">Pembayaran Gagal</h2>
                    <p class="text-sm text-red-600 mb-6">Terjadi masalah saat memproses pembayaran. Silakan coba lagi.</p>
                </template>

                <!-- Refunded / unknown -->
                <template v-else>
                    <div class="w-16 h-16 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-stone-800 mb-2">Status Pembayaran</h2>
                    <p class="text-sm text-stone-500 mb-6">Silakan cek riwayat pembayaran di dashboard untuk detail transaksi.</p>
                </template>

                <button v-if="currentStatus === 'paid'" @click="goToTransactions"
                        class="w-full py-3 rounded-xl text-sm font-semibold bg-brand-primary hover:bg-brand-primary-hover text-white transition-all">
                    Lihat Riwayat Pembayaran
                </button>
                <button v-else @click="goToDashboard"
                        class="w-full py-3 rounded-xl text-sm font-semibold bg-brand-primary hover:bg-brand-primary-hover text-white transition-all">
                    Kembali ke Dashboard
                </button>
            </div>
        </div>
    </DashboardLayout>
</template>
