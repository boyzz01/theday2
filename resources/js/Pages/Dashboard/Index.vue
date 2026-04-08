<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    recentInvitations: Array,
    activePlan: Object,
});

const statCards = computed(() => [
    {
        label: 'Total Undangan',
        value: props.stats.total_invitations,
        sub: `${props.stats.draft_count} draft · ${props.stats.published_count} aktif`,
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>`,
        bg: '#FEF3C7',
        iconColor: '#D97706',
    },
    {
        label: 'Total Dilihat',
        value: props.stats.total_views.toLocaleString('id-ID'),
        sub: 'Semua undangan',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`,
        bg: '#EDE9FE',
        iconColor: '#7C3AED',
    },
    {
        label: 'Total RSVP',
        value: props.stats.total_rsvps.toLocaleString('id-ID'),
        sub: 'Konfirmasi kehadiran',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>`,
        bg: '#D1FAE5',
        iconColor: '#059669',
    },
    {
        label: 'Paket Aktif',
        value: props.activePlan?.name ?? 'Free',
        sub: props.activePlan ? `Maks ${props.activePlan.max_invitations === 9999 ? '∞' : props.activePlan.max_invitations} undangan` : 'Gratis selamanya',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>`,
        bg: '#FEE2E2',
        iconColor: '#DC2626',
    },
]);

const statusConfig = {
    draft:     { label: 'Draft',     bg: '#F3F4F6', color: '#6B7280' },
    published: { label: 'Aktif',     bg: '#D1FAE5', color: '#059669' },
    expired:   { label: 'Kedaluwarsa', bg: '#FEE2E2', color: '#DC2626' },
};

const eventTypeLabel = {
    pernikahan: '💍 Pernikahan',
    ulang_tahun: '🎂 Ulang Tahun',
};

const templateColor = (inv) => inv.template?.default_config?.primary_color ?? '#D4A373';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-base font-semibold text-stone-800 truncate">Dashboard</h1>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- ── Greeting ──────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-stone-800">
                        Selamat datang kembali 👋
                    </h2>
                    <p class="text-sm text-stone-400 mt-0.5">
                        Kelola undangan digitalmu dari sini.
                    </p>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm transition-all hover:opacity-90 hover:-translate-y-px"
                    style="background-color: #D4A373"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Undangan Baru
                </Link>
            </div>

            <!-- ── Stat Cards ─────────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="bg-white rounded-2xl p-5 border border-stone-100 shadow-sm hover:shadow-md transition-shadow"
                >
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-xs font-medium text-stone-400 uppercase tracking-wide">{{ card.label }}</p>
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             :style="{ backgroundColor: card.bg }">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                 :style="{ color: card.iconColor }" v-html="card.icon"/>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-stone-800">{{ card.value }}</p>
                    <p class="text-xs text-stone-400 mt-1">{{ card.sub }}</p>
                </div>
            </div>

            <!-- ── Recent Invitations ─────────────────────────────── -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-stone-700">Undangan Terbaru</h3>
                    <Link :href="route('dashboard')"
                          class="text-xs font-medium transition-colors hover:opacity-80"
                          style="color: #D4A373">
                        Lihat semua →
                    </Link>
                </div>

                <!-- Empty state -->
                <div v-if="!recentInvitations.length"
                     class="bg-white rounded-2xl border border-stone-100 border-dashed p-12 text-center">
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
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
                        style="background-color: #D4A373"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Buat Sekarang
                    </Link>
                </div>

                <!-- Invitation cards -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="inv in recentInvitations"
                        :key="inv.id"
                        class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden hover:shadow-md transition-all hover:-translate-y-0.5 group"
                    >
                        <!-- Template color preview -->
                        <div
                            class="h-28 relative flex items-center justify-center"
                            :style="`background: linear-gradient(135deg, ${templateColor(inv)}22, ${templateColor(inv)}44)`"
                        >
                            <!-- Color swatch dots -->
                            <div class="absolute top-3 right-3 flex gap-1.5">
                                <div class="w-3 h-3 rounded-full opacity-60"
                                     :style="`background-color: ${templateColor(inv)}`"/>
                                <div class="w-3 h-3 rounded-full opacity-40"
                                     :style="`background-color: ${inv.template?.default_config?.secondary_color ?? '#FEFAE0'}`"/>
                                <div class="w-3 h-3 rounded-full opacity-40"
                                     :style="`background-color: ${inv.template?.default_config?.accent_color ?? '#CCD5AE'}`"/>
                            </div>

                            <!-- Mini invitation preview -->
                            <div class="text-center px-4">
                                <div class="w-8 h-px mx-auto mb-2" :style="`background-color: ${templateColor(inv)}`"/>
                                <p class="text-xs font-medium text-stone-500">{{ eventTypeLabel[inv.event_type] }}</p>
                                <p class="text-sm font-semibold text-stone-700 mt-0.5 leading-tight line-clamp-2">{{ inv.title }}</p>
                                <div class="w-8 h-px mx-auto mt-2" :style="`background-color: ${templateColor(inv)}`"/>
                            </div>

                            <!-- Status badge -->
                            <span
                                class="absolute top-3 left-3 px-2 py-0.5 rounded-full text-xs font-semibold"
                                :style="`background-color: ${statusConfig[inv.status].bg}; color: ${statusConfig[inv.status].color}`"
                            >
                                {{ statusConfig[inv.status].label }}
                            </span>
                        </div>

                        <!-- Card body -->
                        <div class="p-4">
                            <p class="text-sm font-semibold text-stone-800 truncate mb-1">{{ inv.title }}</p>
                            <p class="text-xs text-stone-400 mb-3" v-if="inv.template">
                                Template: {{ inv.template.name }}
                            </p>

                            <!-- Stats row -->
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
                                <span v-if="inv.expires_at" class="ml-auto">
                                    Exp {{ inv.expires_at }}
                                </span>
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

                    <!-- "Buat baru" placeholder card (jika < 3 undangan) -->
                    <Link
                        v-if="recentInvitations.length < 3"
                        :href="route('dashboard')"
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
            </div>

            <!-- ── Quick tips (only shown when 0 invitations) ─────── -->
            <div v-if="!recentInvitations.length"
                 class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="(tip, i) in [
                        { icon: '🎨', title: 'Pilih Template', desc: 'Pilih dari 50+ template cantik untuk pernikahan & ulang tahun.' },
                        { icon: '✏️', title: 'Isi Detail Acara', desc: 'Masukkan nama, tanggal, lokasi, dan foto acaramu.' },
                        { icon: '🚀', title: 'Bagikan ke Tamu', desc: 'Publikasikan dan bagikan link via WhatsApp dalam detik.' },
                    ]"
                    :key="i"
                    class="bg-white rounded-2xl p-5 border border-stone-100 shadow-sm"
                >
                    <div class="text-2xl mb-3">{{ tip.icon }}</div>
                    <p class="text-sm font-semibold text-stone-700 mb-1">{{ i + 1 }}. {{ tip.title }}</p>
                    <p class="text-xs text-stone-400 leading-relaxed">{{ tip.desc }}</p>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>
