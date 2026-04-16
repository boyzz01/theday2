<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    invitations: { type: Array, default: () => [] },
});

const statusConfig = {
    draft:     { label: 'Draft',       bg: '#F3F4F6', color: '#6B7280' },
    published: { label: 'Aktif',       bg: '#D1FAE5', color: '#059669' },
    expired:   { label: 'Kedaluwarsa', bg: '#FEE2E2', color: '#DC2626' },
};
</script>

<template>
    <Head title="RSVP" />

    <DashboardLayout>
        <template #header>
            <div>
                <h2 class="text-base font-semibold text-stone-800">RSVP</h2>
                <p class="text-sm text-stone-400 mt-0.5">Konfirmasi kehadiran tamu undanganmu.</p>
            </div>
        </template>

        <!-- Empty state -->
        <div v-if="!invitations.length"
             class="bg-white rounded-2xl border border-dashed border-stone-200 p-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background-color: #FEF3C7">
                <svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-stone-600 mb-1">Belum ada undangan</p>
            <p class="text-xs text-stone-400 mb-5">Buat undangan terlebih dahulu untuk melihat RSVP.</p>
            <Link
                :href="route('dashboard.templates')"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
                style="background-color: #D4A373"
            >
                Buat Undangan
            </Link>
        </div>

        <!-- Invitation grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="inv in invitations"
                :key="inv.id"
                class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden hover:shadow-md transition-all hover:-translate-y-0.5"
            >
                <!-- Preview header -->
                <div
                    class="h-28 relative flex items-center justify-center"
                    :style="`background: linear-gradient(135deg, ${inv.template_color}22, ${inv.template_color}44)`"
                >
                    <div class="absolute top-3 right-3">
                        <div class="w-3 h-3 rounded-full opacity-60" :style="`background-color: ${inv.template_color}`"/>
                    </div>

                    <!-- RSVP count -->
                    <div class="text-center px-4">
                        <div class="w-8 h-px mx-auto mb-2" :style="`background-color: ${inv.template_color}`"/>
                        <p class="text-3xl font-bold" :style="`color: ${inv.template_color}`">
                            {{ inv.total_rsvps }}
                        </p>
                        <p class="text-xs text-stone-500 mt-0.5">konfirmasi masuk</p>
                        <div class="w-8 h-px mx-auto mt-2" :style="`background-color: ${inv.template_color}`"/>
                    </div>

                    <span
                        class="absolute top-3 left-3 px-2 py-0.5 rounded-full text-xs font-semibold"
                        :style="`background-color: ${statusConfig[inv.status]?.bg ?? '#F3F4F6'}; color: ${statusConfig[inv.status]?.color ?? '#6B7280'}`"
                    >
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
                            <span>🎉</span>
                            {{ inv.hadir_count }} hadir
                        </span>
                        <span v-if="inv.tidak_hadir_count" class="flex items-center gap-1 text-red-500">
                            <span>😢</span>
                            {{ inv.tidak_hadir_count }} tidak
                        </span>
                        <span v-if="inv.ragu_count" class="flex items-center gap-1 text-stone-400">
                            <span>🤔</span>
                            {{ inv.ragu_count }} ragu
                        </span>
                        <span v-if="inv.total_tamu" class="ml-auto text-stone-500 font-medium">
                            {{ inv.total_tamu }} tamu
                        </span>
                    </div>

                    <!-- CTA -->
                    <Link
                        :href="route('dashboard.rsvp.show', inv.id)"
                        class="block w-full text-center py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                        :style="`background-color: ${inv.template_color}`"
                    >
                        Lihat RSVP
                    </Link>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
