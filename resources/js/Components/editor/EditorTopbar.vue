<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    invitation:   { type: Object, required: true },
    saveStatus:   { type: String, default: 'saved' }, // saved | saving | unsaved | error
    previewMode:  { type: String, default: 'mobile' }, // mobile | desktop
});

const emit = defineEmits(['toggle-preview-mode', 'publish']);

const saveStatusLabel = {
    saved:   'Tersimpan',
    saving:  'Menyimpan...',
    unsaved: 'Belum disimpan',
    error:   'Gagal menyimpan',
};

const saveStatusColor = {
    saved:   'text-emerald-600',
    saving:  'text-amber-500',
    unsaved: 'text-stone-400',
    error:   'text-red-500',
};
</script>

<template>
    <header class="flex-shrink-0 h-12 bg-white border-b border-stone-100 flex items-center gap-3 px-4 z-20">

        <!-- Back to dashboard -->
        <Link
            :href="route('dashboard.invitations.index')"
            class="flex items-center gap-1.5 text-stone-500 hover:text-stone-800 transition-colors text-sm font-medium flex-shrink-0"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="hidden sm:inline">Dashboard</span>
        </Link>

        <div class="w-px h-5 bg-stone-200 flex-shrink-0"/>

        <!-- Invitation title -->
        <p class="flex-1 min-w-0 text-sm font-semibold text-stone-800 truncate"
           style="font-family: 'Playfair Display', serif">
            {{ invitation.title || 'Undangan' }}
        </p>

        <!-- Save status -->
        <div class="hidden sm:flex items-center gap-1.5 flex-shrink-0">
            <!-- Spinner for saving -->
            <svg v-if="saveStatus === 'saving'"
                 class="w-3.5 h-3.5 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <!-- Checkmark for saved -->
            <svg v-else-if="saveStatus === 'saved'"
                 class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-xs font-medium" :class="saveStatusColor[saveStatus]">
                {{ saveStatusLabel[saveStatus] }}
            </span>
        </div>

        <!-- Preview mode toggle -->
        <div class="hidden md:flex items-center rounded-lg bg-stone-100 p-0.5 gap-0.5 flex-shrink-0">
            <button
                @click="emit('toggle-preview-mode', 'mobile')"
                :class="['p-1.5 rounded-md transition-all', previewMode === 'mobile' ? 'bg-white shadow-sm text-stone-800' : 'text-stone-400 hover:text-stone-600']"
                title="Preview Mobile"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </button>
            <button
                @click="emit('toggle-preview-mode', 'desktop')"
                :class="['p-1.5 rounded-md transition-all', previewMode === 'desktop' ? 'bg-white shadow-sm text-stone-800' : 'text-stone-400 hover:text-stone-600']"
                title="Preview Desktop"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </button>
        </div>

        <!-- Publish button -->
        <button
            @click="emit('publish')"
            class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all active:scale-95"
            style="background-color: #D4A373"
        >
            {{ invitation.status === 'published' ? 'Dipublikasi ✓' : 'Publikasi' }}
        </button>

    </header>
</template>
