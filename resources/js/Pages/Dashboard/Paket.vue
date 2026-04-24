<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    currentPlan:  { type: Object, required: true },
    transactions: { type: Array,  default: () => [] },
});

// ── Checkout state ────────────────────────────────────────────────────────────
const isCheckingOut = ref(false);
const checkoutError = ref('');

// ── FAQ accordion ─────────────────────────────────────────────────────────────
const openFaq = ref(null);
const faqs = [
    {
        q: 'Apakah Premium otomatis diperpanjang?',
        a: 'Tidak. Premium adalah pembayaran satu kali untuk 3 bulan (90 hari). Tidak ada auto-renewal. Kamu bisa perpanjang manual kapan saja.',
    },
    {
        q: 'Bagaimana cara pembayaran?',
        a: 'Kamu bisa bayar via GoPay, OVO, Dana, QRIS, transfer bank virtual, atau kartu kredit melalui Mayar — aman dan terenkripsi.',
    },
    {
        q: 'Ada garansi uang kembali?',
        a: 'Ada. Jika tidak puas dalam 7 hari pertama, kami kembalikan 100% pembayaranmu. Hubungi hello@theday.id.',
    },
    {
        q: 'Kalau Premium habis, undangan saya hilang?',
        a: 'Tidak. Undangan kamu tetap ada dan bisa diakses. Namun beberapa fitur Premium tidak aktif lagi sampai kamu perpanjang.',
    },
    {
        q: 'Bisa upgrade di tengah jalan?',
        a: 'Bisa kapan saja. Kamu langsung dapat akses Premium setelah pembayaran berhasil.',
    },
];

// ── Computed ──────────────────────────────────────────────────────────────────
const isPremium    = computed(() => props.currentPlan.is_premium);
const daysLeft     = computed(() => props.currentPlan.days_remaining ?? null);
const expiresAt    = computed(() => props.currentPlan.expires_at);
const expiryWarn   = computed(() => isPremium.value && daysLeft.value !== null && daysLeft.value <= 7);

const premiumCtaLabel = computed(() => {
    if (!isPremium.value) return 'Upgrade Sekarang →';
    if (daysLeft.value !== null && daysLeft.value <= 14) return 'Perpanjang →';
    return 'Sudah Premium ✓';
});
const premiumCtaDisabled = computed(() => isPremium.value && daysLeft.value !== null && daysLeft.value > 14);

// ── Checkout ──────────────────────────────────────────────────────────────────
const startCheckout = async () => {
    if (isCheckingOut.value || premiumCtaDisabled.value) return;

    isCheckingOut.value = true;
    checkoutError.value = '';

    try {
        const { data } = await axios.post(route('dashboard.subscriptions.checkout'));
        window.location.href = data.payment_url;
    } catch (err) {
        checkoutError.value = err?.response?.data?.error ?? 'Terjadi kesalahan. Silakan coba lagi.';
        isCheckingOut.value = false;
    }
};

// ── Feature comparison rows ───────────────────────────────────────────────────
const features = [
    { label: 'Undangan aktif',      free: '1',  premium: 'Unlimited' },
    { label: 'Foto per undangan',   free: '5',  premium: 'Unlimited' },
    { label: 'Template premium',    free: false, premium: true },
    { label: 'Upload musik sendiri',free: false, premium: true },
    { label: 'Tanpa watermark',     free: false, premium: true },
    { label: 'Custom URL slug',     free: false, premium: true },
    { label: 'Password protection', free: false, premium: true },
    { label: 'Analytics kunjungan', free: false, premium: true },
    { label: 'Prioritas dukungan',  free: false, premium: true },
];
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-stone-800">Paket &amp; Langganan</h1>
        </template>

        <div class="max-w-3xl mx-auto space-y-8">

            <!-- ── 1. Current Plan Status ──────────────────────────────── -->
            <div class="rounded-2xl border p-6"
                 :class="isPremium
                    ? 'bg-gradient-to-br from-[#EFF2F0] to-[#F4F7F5] border-[#92A89C]/40'
                    : 'bg-white border-stone-100'">

                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest mb-1"
                           :class="isPremium ? 'text-[#73877C]' : 'text-stone-400'">
                            Paket Aktif
                        </p>
                        <h2 class="text-2xl font-bold text-[#2C2417]">
                            {{ isPremium ? 'Premium ' : 'Gratis' }}
                        </h2>

                        <!-- Premium: expiry info -->
                        <template v-if="isPremium && expiresAt">
                            <p class="text-sm mt-1" :class="expiryWarn ? 'text-[#2C2417]' : 'text-stone-500'">
                                Aktif hingga <strong>{{ expiresAt }}</strong>
                            </p>
                            <span v-if="expiryWarn"
                                  class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-800">
                                ⚠ Segera berakhir ({{ daysLeft }} hari)
                            </span>
                            <p v-else class="text-xs mt-1 text-stone-400">
                                Sisa {{ daysLeft }} hari
                            </p>
                        </template>

                        <!-- Free: limits summary -->
                        <template v-else-if="!isPremium">
                            <p class="text-sm mt-1 text-stone-500">
                                Kamu menggunakan paket gratis dengan fitur terbatas.
                            </p>
                            <ul class="mt-2 space-y-0.5 text-xs text-stone-400">
                                <li>• 1 undangan aktif</li>
                                <li>• 5 foto per undangan</li>
                                <li>• Template gratis saja</li>
                                <li>• Watermark TheDay</li>
                            </ul>
                        </template>
                    </div>

                    <button @click="startCheckout"
                            :disabled="premiumCtaDisabled || isCheckingOut"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all flex-shrink-0"
                            :class="premiumCtaDisabled
                                ? 'bg-stone-100 text-stone-400 cursor-not-allowed'
                                : 'bg-brand-primary hover:bg-brand-primary-hover text-white'">
                        {{ isCheckingOut ? 'Mengarahkan ke pembayaran...' : (isPremium ? 'Perpanjang Premium →' : 'Upgrade ke Premium →') }}
                    </button>
                </div>
            </div>

            <!-- Checkout error notice -->
            <div v-if="checkoutError"
                 class="rounded-xl p-4 text-sm bg-red-50 text-red-700 border border-red-100">
                {{ checkoutError }}
            </div>

            <!-- ── 2. Pricing Cards ──────────────────────────────────────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Free Card -->
                <div class="rounded-2xl border border-stone-100 p-6 bg-white">
                    <div class="flex items-center gap-2 mb-4">
                        <h3 class="text-base font-semibold text-stone-800">Gratis</h3>
                        <span v-if="!isPremium"
                              class="text-xs font-semibold px-2 py-0.5 rounded-full bg-[#92A89C]/15 text-[#73877C]">
                            Paket Kamu
                        </span>
                    </div>
                    <div class="mb-1">
                        <span class="text-3xl font-bold text-stone-800">Rp 0</span>
                    </div>
                    <p class="text-xs text-stone-400 mb-5">selamanya</p>
                    <p class="text-sm text-stone-500 mb-5">Coba tanpa risiko</p>
                    <ul class="space-y-2.5 mb-6 text-sm text-stone-600">
                        <li v-for="feat in ['1 undangan aktif', '5 foto gallery', 'Template gratis', 'Musik default']"
                            :key="feat"
                            class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            {{ feat }}
                        </li>
                        <li class="flex items-center gap-2 text-stone-400">
                            <span class="w-4 h-4 rounded-full bg-stone-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </span>
                            Watermark "Dibuat dengan TheDay"
                        </li>
                    </ul>
                    <button disabled
                            class="w-full py-2.5 rounded-xl text-sm font-semibold border border-stone-100 text-stone-400 cursor-not-allowed">
                        {{ isPremium ? 'Gratis' : 'Paket Kamu' }}
                    </button>
                </div>

                <!-- Premium Card -->
                <div class="rounded-2xl p-6 relative bg-gradient-to-br from-[#EFF2F0] to-[#F4F7F5] border-2 border-[#92A89C]/50 shadow-[0_8px_32px_rgba(146,168,156,0.18)]">
                    <div class="absolute -top-3 left-6">
                        <span class="text-xs font-bold px-3 py-1 rounded-full text-white"
                              style="background:linear-gradient(90deg,#73877C,#92A89C)">
                            Paling Populer
                        </span>
                    </div>

                    <div class="flex items-center gap-2 mt-2 mb-4">
                        <h3 class="text-base font-semibold text-[#2C2417]">Premium</h3>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-[#92A89C]/20 text-[#73877C]" v-if="isPremium">
                            Paket Kamu
                        </span>
                    </div>

                    <div class="mb-1">
                        <span class="text-3xl font-bold text-[#2C2417]">Rp 35.000</span>
                    </div>
                    <p class="text-xs mb-2 text-[#73877C] font-medium">/ 3 bulan (90 hari)</p>
                    <p class="text-sm mb-5 text-stone-500">Semua yang kamu butuhkan untuk undangan impian</p>

                    <ul class="space-y-2.5 mb-6 text-sm text-stone-700">
                        <li v-for="feat in ['Unlimited undangan aktif','Unlimited foto gallery','Semua template (gratis + premium)','Upload musik sendiri','Tanpa watermark','Custom URL slug','Password protection','Analytics kunjungan','Prioritas dukungan']"
                            :key="feat"
                            class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-[#92A89C]/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-2.5 h-2.5 text-[#73877C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            {{ feat }}
                        </li>
                    </ul>

                    <button @click="startCheckout"
                            :disabled="premiumCtaDisabled || isCheckingOut"
                            class="w-full py-3 rounded-xl text-sm font-bold transition-all"
                            :class="premiumCtaDisabled
                                ? 'cursor-not-allowed bg-stone-100 text-stone-400'
                                : 'bg-brand-primary hover:bg-brand-primary-hover text-white'">
                        {{ isCheckingOut ? 'Mengarahkan...' : premiumCtaLabel }}
                    </button>

                    <p class="text-center text-xs mt-3 text-stone-400">
                        Pembayaran aman. Garansi uang kembali 7 hari.
                    </p>
                </div>
            </div>

            <!-- ── 3. Feature Comparison Table ─────────────────────────── -->
            <div class="bg-white rounded-2xl border border-stone-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-stone-100">
                    <h3 class="text-base font-semibold text-stone-800">Perbandingan Fitur</h3>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider w-1/2">Fitur</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Gratis</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-[#73877C] uppercase tracking-wider">Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in features" :key="row.label"
                            :class="i % 2 === 0 ? 'bg-white' : 'bg-stone-50'">
                            <td class="px-6 py-3 text-stone-700">{{ row.label }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="row.free === true" class="text-emerald-600 font-bold">✓</span>
                                <span v-else-if="row.free === false" class="text-stone-300">✗</span>
                                <span v-else class="text-stone-600 font-medium text-xs">{{ row.free }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="row.premium === true" class="text-[#73877C] font-bold">✓</span>
                                <span v-else-if="row.premium === false" class="text-stone-300">✗</span>
                                <span v-else class="text-[#73877C] font-semibold text-xs">{{ row.premium }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── 4. Payment Methods ──────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-stone-100 p-6">
                <h3 class="text-sm font-semibold text-stone-700 mb-4">Kami mendukung berbagai metode pembayaran:</h3>
                <div class="flex flex-wrap gap-3">
                    <span v-for="m in ['GoPay','OVO','Dana','QRIS','Transfer Bank','Kartu Kredit']" :key="m"
                          class="px-3 py-1.5 rounded-lg border border-stone-100 text-xs font-semibold text-stone-600 bg-stone-50">
                        {{ m }}
                    </span>
                </div>
            </div>

            <!-- ── 5. FAQ ──────────────────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-stone-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-stone-100">
                    <h3 class="text-base font-semibold text-stone-800">Pertanyaan Umum</h3>
                </div>
                <div class="divide-y divide-stone-100">
                    <div v-for="(faq, i) in faqs" :key="i">
                        <button @click="openFaq = openFaq === i ? null : i"
                                class="w-full flex items-center justify-between px-6 py-4 text-left text-sm font-medium text-stone-700 hover:bg-stone-50 transition-colors">
                            <span>{{ faq.q }}</span>
                            <svg class="w-4 h-4 flex-shrink-0 ml-3 transition-transform text-stone-400"
                                 :class="openFaq === i ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <Transition name="expand">
                            <div v-if="openFaq === i" class="px-6 pb-4 text-sm text-stone-500 leading-relaxed">
                                {{ faq.a }}
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- ── 6. Payment History ─────────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-stone-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-stone-100">
                    <h3 class="text-base font-semibold text-stone-800">Riwayat Pembayaran</h3>
                </div>

                <!-- Empty state -->
                <div v-if="!transactions.length" class="px-6 py-12 text-center">
                    <div class="w-12 h-12 rounded-full bg-stone-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-sm text-stone-400">Belum ada riwayat pembayaran.</p>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-100">
                                <th class="text-left px-6 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Tanggal</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Invoice</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Paket</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Harga</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Status</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="t in transactions" :key="t.id"
                                class="hover:bg-stone-50 transition-colors">
                                <td class="px-6 py-3 text-stone-600">{{ t.created_at }}</td>
                                <td class="px-4 py-3 text-stone-500 text-xs font-mono">{{ t.invoice_number }}</td>
                                <td class="px-4 py-3 text-stone-700 font-medium">{{ t.plan_name }}</td>
                                <td class="px-4 py-3 text-stone-700 text-right font-semibold">{{ t.amount_fmt }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="{
                                              'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100': t.status === 'paid',
                                              'bg-[#92A89C]/15 text-[#73877C] ring-1 ring-[#92A89C]/30': t.status === 'pending',
                                              'bg-red-50 text-red-600 ring-1 ring-red-100': t.status === 'failed' || t.status === 'refunded',
                                          }">
                                        {{ t.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a v-if="t.status === 'paid'"
                                       :href="route('dashboard.transactions.invoice', t.id)"
                                       target="_blank"
                                       class="text-xs font-semibold text-[#92A89C] hover:underline">
                                        Invoice
                                    </a>
                                    <span v-else class="text-xs text-stone-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>

<style scoped>
.expand-enter-active, .expand-leave-active { transition: all 0.2s ease; overflow: hidden; }
.expand-enter-from, .expand-leave-to { opacity: 0; max-height: 0; }
.expand-enter-to, .expand-leave-from { opacity: 1; max-height: 200px; }
</style>
