<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    invitations: { type: Array, default: () => [] },
});

const statusConfig = {
    draft:     { label: 'Draft',        bg: '#F3F4F6', color: '#6B7280' },
    published: { label: 'Aktif',        bg: '#D1FAE5', color: '#059669' },
    expired:   { label: 'Kedaluwarsa',  bg: '#FEE2E2', color: '#DC2626' },
};

const eventTypeLabel = {
    pernikahan:  '💍 Pernikahan',
    ulang_tahun: '🎂 Ulang Tahun',
};

const templateColor = (inv) => inv.template?.default_config?.primary_color ?? '#D4A373';
</script>

<template>
    <Head title="Undangan Saya" />

    <DashboardLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-base font-semibold text-stone-800">Undangan Saya</h2>
                    <p class="text-sm text-stone-400 mt-0.5">Kelola semua undangan digitalmu.</p>
                </div>
                <Link
                    :href="route('dashboard.templates')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm transition-all hover:opacity-90 hover:-translate-y-px"
                    style="background-color: #D4A373"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Undangan Baru
                </Link>
            </div>
        </template>

        <!-- Empty state -->
        <div v-if="!invitations.length"
             class="bg-white rounded-2xl border border-stone-100 border-dashed p-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background-color: #FEF3C7">
                <svg class="w-8 h-8" style="color: #D97706" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-stone-600 mb-1">Belum ada undangan</p>
            <p class="text-xs text-stone-400 mb-5">Buat undangan pertamamu sekarang!</p>
            <Link
                :href="route('dashboard.templates')"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
                style="background-color: #D4A373"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Sekarang
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
                    :style="`background: linear-gradient(135deg, ${templateColor(inv)}22, ${templateColor(inv)}44)`"
                >
                    <div class="absolute top-3 right-3 flex gap-1.5">
                        <div class="w-3 h-3 rounded-full opacity-60" :style="`background-color: ${templateColor(inv)}`"/>
                        <div class="w-3 h-3 rounded-full opacity-40" :style="`background-color: ${inv.template?.default_config?.secondary_color ?? '#FEFAE0'}`"/>
                        <div class="w-3 h-3 rounded-full opacity-40" :style="`background-color: ${inv.template?.default_config?.accent_color ?? '#CCD5AE'}`"/>
                    </div>

                    <div class="text-center px-4">
                        <div class="w-8 h-px mx-auto mb-2" :style="`background-color: ${templateColor(inv)}`"/>
                        <p class="text-xs font-medium text-stone-500">{{ eventTypeLabel[inv.event_type] }}</p>
                        <p class="text-sm font-semibold text-stone-700 mt-0.5 leading-tight line-clamp-2">
                            {{ inv.title || '(Tanpa judul)' }}
                        </p>
                        <div class="w-8 h-px mx-auto mt-2" :style="`background-color: ${templateColor(inv)}`"/>
                    </div>

                    <span
                        class="absolute top-3 left-3 px-2 py-0.5 rounded-full text-xs font-semibold"
                        :style="`background-color: ${statusConfig[inv.status].bg}; color: ${statusConfig[inv.status].color}`"
                    >
                        {{ statusConfig[inv.status].label }}
                    </span>
                </div>

                <!-- Card body -->
                <div class="p-4">
                    <p class="text-sm font-semibold text-stone-800 truncate mb-1">
                        {{ inv.title || '(Tanpa judul)' }}
                    </p>
                    <p class="text-xs text-stone-400 mb-3" v-if="inv.template">
                        Template: {{ inv.template.name }}
                    </p>

                    <!-- Stats -->
                    <div class="flex items-center gap-4 text-xs text-stone-400 mb-4">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{ inv.view_count.toLocaleString('id-ID') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ inv.rsvps_count }} RSVP
                        </span>
                        <span v-if="inv.expires_at" class="ml-auto">Exp {{ inv.expires_at }}</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <Link
                            :href="route('dashboard.invitations.edit', inv.id)"
                            class="flex-1 text-center py-2 rounded-xl text-xs font-semibold border border-stone-200 text-stone-600 hover:bg-stone-50 transition-colors"
                        >
                            Edit
                        </Link>
                        <a
                            :href="`/${inv.slug}`"
                            target="_blank"
                            class="flex-1 text-center py-2 rounded-xl text-xs font-semibold text-white transition-all hover:opacity-90"
                            :style="`background-color: ${templateColor(inv)}`"
                        >
                            Lihat
                        </a>
                    </div>
                </div>
            </div>

            <!-- Add new placeholder -->
            <Link
                :href="route('dashboard.templates')"
                class="flex flex-col items-center justify-center bg-white rounded-2xl border border-dashed border-stone-200 p-8 text-center hover:border-amber-300 hover:bg-amber-50/30 transition-all group min-h-[220px]"
            >
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-3 transition-colors group-hover:bg-amber-100"
                     style="background-color: #FEF3C7">
                    <svg class="w-6 h-6" style="color: #D4A373" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-stone-500 group-hover:text-stone-700 transition-colors">
                    Buat Undangan Baru
                </p>
            </Link>
        </div>
    </DashboardLayout>
</template>
