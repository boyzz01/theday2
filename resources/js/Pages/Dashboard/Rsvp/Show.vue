<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    invitation: { type: Object, required: true },
    rsvps:      { type: Array,  default: () => [] },
    summary:    { type: Object, required: true },
    filter:     { type: String, default: 'semua' },
});

const color = computed(() => props.invitation.template_color);

const filters = [
    { key: 'semua',       label: 'Semua',       emoji: '📋' },
    { key: 'hadir',       label: 'Hadir',       emoji: '🎉' },
    { key: 'tidak_hadir', label: 'Tidak Hadir', emoji: '😢' },
    { key: 'ragu',        label: 'Ragu',        emoji: '🤔' },
];

const attendanceBadge = {
    hadir:       { label: 'Hadir',       bg: '#D1FAE5', color: '#059669' },
    tidak_hadir: { label: 'Tidak Hadir', bg: '#FEE2E2', color: '#DC2626' },
    ragu:        { label: 'Masih Ragu',  bg: '#FEF3C7', color: '#D97706' },
};

function setFilter(key) {
    router.get(route('dashboard.rsvp.show', props.invitation.id), { filter: key }, {
        preserveState: true, preserveScroll: true, replace: true,
    });
}

function doExport() {
    window.location.href = route('dashboard.rsvp.export', props.invitation.id);
}
</script>

<template>
    <Head :title="`RSVP – ${invitation.title || 'Undangan'}`" />

    <DashboardLayout>
        <template #header>
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="route('dashboard.rsvp.index')"
                      class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-stone-800 truncate">RSVP — {{ invitation.title || '(Tanpa judul)' }}</h2>
                    <p class="text-sm text-stone-400 mt-0.5">Daftar konfirmasi kehadiran tamu.</p>
                </div>
            </div>
        </template>

        <!-- Stats cards -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
            <div class="bg-white rounded-2xl border border-stone-100 p-4 text-center">
                <p class="text-2xl font-bold text-stone-800">{{ summary.total }}</p>
                <p class="text-xs text-stone-400 mt-0.5">Total</p>
            </div>
            <div class="bg-white rounded-2xl border border-stone-100 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600">{{ summary.hadir }}</p>
                <p class="text-xs text-stone-400 mt-0.5">Hadir</p>
            </div>
            <div class="bg-white rounded-2xl border border-stone-100 p-4 text-center">
                <p class="text-2xl font-bold text-red-500">{{ summary.tidak_hadir }}</p>
                <p class="text-xs text-stone-400 mt-0.5">Tidak Hadir</p>
            </div>
            <div class="bg-white rounded-2xl border border-stone-100 p-4 text-center">
                <p class="text-2xl font-bold text-amber-500">{{ summary.ragu }}</p>
                <p class="text-xs text-stone-400 mt-0.5">Masih Ragu</p>
            </div>
            <div class="bg-white rounded-2xl border border-stone-100 p-4 text-center col-span-2 sm:col-span-1"
                 :style="`background: linear-gradient(135deg, ${color}12, ${color}22); border-color: ${color}33`">
                <p class="text-2xl font-bold" :style="`color: ${color}`">{{ summary.total_tamu }}</p>
                <p class="text-xs text-stone-400 mt-0.5">Est. Tamu Hadir</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="sm:flex sm:items-center sm:gap-3 mb-4">
            <!-- Filter pills: 2×2 on mobile, single row on sm+ -->
            <div class="grid grid-cols-2 sm:flex gap-1.5 flex-1 mb-2 sm:mb-0">
                <button
                    v-for="f in filters"
                    :key="f.key"
                    @click="setFilter(f.key)"
                    class="px-3 py-2 rounded-xl text-xs font-medium transition-colors text-center"
                    :class="filter === f.key
                        ? 'bg-amber-100 text-amber-800 border border-amber-200'
                        : 'bg-stone-50 text-stone-600 border border-stone-100 hover:bg-stone-100'"
                >
                    {{ f.emoji }} {{ f.label }}
                </button>
            </div>

            <!-- Export: full-width on mobile (row 3), inline on sm+ -->
            <button
                @click="doExport"
                class="w-full sm:w-auto flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium bg-stone-50 border border-stone-100 text-stone-600 hover:bg-stone-100 transition-colors flex-shrink-0"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export Excel
            </button>
        </div>

        <!-- Empty state for filtered -->
        <div v-if="!rsvps.length"
             class="bg-white rounded-2xl border border-dashed border-stone-200 p-12 text-center">
            <p class="text-sm text-stone-500">Belum ada konfirmasi untuk filter ini.</p>
        </div>

        <!-- RSVP list -->
        <div v-else class="space-y-2">
            <div
                v-for="r in rsvps"
                :key="r.id"
                class="bg-white rounded-2xl border border-stone-100 p-4 flex items-start gap-4"
            >
                <!-- Avatar initials -->
                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white text-sm font-bold"
                     :style="`background-color: ${color}`">
                    {{ r.guest_name.charAt(0).toUpperCase() }}
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-semibold text-stone-800">{{ r.guest_name }}</p>
                        <span
                            class="px-2 py-0.5 rounded-full text-xs font-semibold"
                            :style="`background-color: ${attendanceBadge[r.attendance]?.bg}; color: ${attendanceBadge[r.attendance]?.color}`"
                        >
                            {{ attendanceBadge[r.attendance]?.label ?? r.attendance }}
                        </span>
                        <span v-if="r.attendance === 'hadir'" class="text-xs text-stone-400">
                            {{ r.guest_count }} orang
                        </span>
                    </div>
                    <p v-if="r.phone" class="text-xs text-stone-400 mt-0.5">{{ r.phone }}</p>
                    <p v-if="r.notes" class="text-xs text-stone-500 mt-1 italic">{{ r.notes }}</p>
                </div>

                <!-- Time -->
                <p class="text-xs text-stone-400 flex-shrink-0 mt-0.5">{{ r.created_at }}</p>
            </div>
        </div>
    </DashboardLayout>
</template>
