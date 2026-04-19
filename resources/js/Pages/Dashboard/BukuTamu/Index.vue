<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    invitations: { type: Array, default: () => [] },
});

const statusConfig = {
    draft:     { label: 'Draft',        cls: 'bg-stone-100 text-stone-500 ring-1 ring-stone-200' },
    published: { label: 'Aktif',        cls: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' },
    expired:   { label: 'Kedaluwarsa',  cls: 'bg-red-50 text-red-500 ring-1 ring-red-100' },
};
</script>

<template>
    <Head title="Buku Tamu" />

    <DashboardLayout>
        <template #header>
            <div>
                <h2 class="text-base font-semibold text-stone-800">Buku Tamu</h2>
                <p class="hidden sm:block text-sm text-stone-400 mt-0.5">Ucapan dan doa dari tamu undanganmu.</p>
            </div>
        </template>

        <!-- Empty state: no invitations -->
        <div v-if="!invitations.length"
             class="bg-white rounded-2xl border border-dashed border-stone-200 p-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-[#92A89C]/10">
                <svg class="w-8 h-8 text-[#92A89C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-stone-600 mb-1">Belum ada undangan</p>
            <p class="text-xs text-stone-400 mb-5">Buat undangan terlebih dahulu untuk melihat Buku Tamu.</p>
            <Link
                :href="route('dashboard.templates')"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#92A89C] hover:bg-[#73877C] transition-colors"
            >
                Buat Undangan
            </Link>
        </div>

        <!-- Invitation grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="inv in invitations"
                :key="inv.id"
                class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden hover:shadow-md transition-all hover:-translate-y-0.5 group"
            >
                <!-- Preview header -->
                <div
                    class="h-28 relative flex items-center justify-center"
                    :style="`background: linear-gradient(135deg, ${inv.template_color}22, ${inv.template_color}44)`"
                >
                    <div class="absolute top-3 right-3">
                        <div class="w-3 h-3 rounded-full opacity-60" :style="`background-color: ${inv.template_color}`"/>
                    </div>

                    <!-- Message count badge -->
                    <div class="text-center px-4">
                        <div class="w-8 h-px mx-auto mb-2" :style="`background-color: ${inv.template_color}`"/>
                        <p class="text-3xl font-bold" :style="`color: ${inv.template_color}`">
                            {{ inv.total_messages }}
                        </p>
                        <p class="text-xs text-stone-500 mt-0.5">ucapan masuk</p>
                        <div class="w-8 h-px mx-auto mt-2" :style="`background-color: ${inv.template_color}`"/>
                    </div>

                    <span :class="[
                        'absolute top-3 left-3 px-2 py-0.5 rounded-full text-xs font-semibold',
                        statusConfig[inv.status]?.cls ?? 'bg-stone-100 text-stone-500',
                    ]">
                        {{ statusConfig[inv.status]?.label ?? inv.status }}
                    </span>
                </div>

                <!-- Card body -->
                <div class="p-4">
                    <p class="text-sm font-semibold text-stone-800 truncate mb-3">
                        {{ inv.title || '(Tanpa judul)' }}
                    </p>

                    <!-- Mini stats -->
                    <div class="flex items-center gap-3 text-xs mb-4">
                        <span class="flex items-center gap-1 text-emerald-600">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{ inv.visible_count }} tampil
                        </span>
                        <span v-if="inv.hidden_count" class="flex items-center gap-1 text-stone-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                            {{ inv.hidden_count }} tersembunyi
                        </span>
                        <span v-if="inv.pinned_count" class="flex items-center gap-1 text-violet-600">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            {{ inv.pinned_count }} pinned
                        </span>
                    </div>

                    <!-- Single CTA -->
                    <Link
                        :href="route('dashboard.buku-tamu.show', inv.id)"
                        class="block w-full text-center py-2.5 rounded-xl text-sm font-semibold text-white bg-[#92A89C] hover:bg-[#73877C] transition-colors"
                    >
                        Lihat Buku Tamu
                    </Link>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
