<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
});

const statusClass = {
    paid:     'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
    pending:  'bg-[#92A89C]/15 text-[#73877C] ring-1 ring-[#92A89C]/30',
    failed:   'bg-red-50 text-red-600 ring-1 ring-red-100',
    refunded: 'bg-red-50 text-red-600 ring-1 ring-red-100',
};

const typeClass = {
    paket: 'bg-[#C8A26B]/15 text-[#C8A26B]',
    addon: 'bg-[#92A89C]/15 text-[#73877C]',
};
</script>

<template>
    <Head title="Riwayat Pembayaran" />

    <DashboardLayout>
        <template #header>
            <div>
                <h2 class="text-base font-semibold text-stone-800">Riwayat Pembayaran</h2>
                <p class="hidden sm:block text-sm text-stone-400 mt-0.5">Semua transaksi paket dan add-on.</p>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl border border-stone-100 overflow-hidden">

                <!-- Empty state -->
                <div v-if="!transactions.length" class="px-6 py-16 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-stone-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-stone-600 mb-1">Belum ada transaksi</p>
                    <p class="text-xs text-stone-400">Riwayat pembayaran akan muncul di sini.</p>
                </div>

                <!-- Mobile: card list -->
                <div v-else class="sm:hidden divide-y divide-stone-100">
                    <div v-for="t in transactions" :key="t.id" class="px-4 py-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="typeClass[t.type]">
                                    {{ t.type_label }}
                                </span>
                                <span class="text-xs text-stone-400">{{ t.created_at }}</span>
                            </div>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                  :class="statusClass[t.status] ?? statusClass.failed">
                                {{ t.status_label }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-stone-800">{{ t.plan_name }}</span>
                            <span class="text-sm font-semibold text-stone-700">{{ t.amount_fmt }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono text-stone-400">{{ t.invoice_number }}</span>
                            <a v-if="t.status === 'paid'"
                               :href="route('dashboard.transactions.invoice', t.id)"
                               target="_blank"
                               class="text-xs font-semibold text-[#92A89C] hover:underline">
                                Unduh Invoice
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desktop: table -->
                <div v-if="transactions.length" class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-100">
                                <th class="text-left px-6 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Tanggal</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Invoice</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Jenis</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Detail</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Harga</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Status</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="t in transactions" :key="t.id" class="hover:bg-stone-50 transition-colors">
                                <td class="px-6 py-3 text-stone-600 whitespace-nowrap">{{ t.created_at }}</td>
                                <td class="px-4 py-3 text-stone-500 text-xs font-mono">{{ t.invoice_number }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="typeClass[t.type]">
                                        {{ t.type_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-stone-700 font-medium">{{ t.plan_name }}</td>
                                <td class="px-4 py-3 text-stone-700 text-right font-semibold whitespace-nowrap">{{ t.amount_fmt }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="statusClass[t.status] ?? statusClass.failed">
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
