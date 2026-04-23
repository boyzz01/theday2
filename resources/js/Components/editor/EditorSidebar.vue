<script setup>
import { computed } from 'vue';

const props = defineProps({
    sections:         { type: Array,  required: true },
    activeSectionKey: { type: String, required: true },
    collapsed:        { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'toggle-section', 'update:collapsed']);

// Group by category per spec
const groups = [
    {
        label: 'Wajib',
        keys: ['envelope', 'cover', 'couple', 'events', 'closing'],
    },
    {
        label: 'Cerita & Visual',
        keys: ['opening', 'quote', 'love_story', 'gallery', 'video'],
    },
    {
        label: 'Interaksi',
        keys: ['rsvp', 'wishes', 'live_streaming'],
    },
    {
        label: 'Tambahan',
        keys: ['gift', 'additional_info'],
    },
];

const sectionMap = computed(() =>
    Object.fromEntries(props.sections.map(s => [s.section_key, s]))
);

function groupSections(keys) {
    return keys
        .map(k => sectionMap.value[k])
        .filter(Boolean);
}

// Status badge config
const statusConfig = {
    complete:   { dot: 'bg-emerald-400', label: null },
    incomplete: { dot: 'bg-amber-400',   label: null },
    warning:    { dot: 'bg-amber-400',   label: null },
    error:      { dot: 'bg-red-400',     label: null },
    empty:      { dot: 'bg-stone-200',   label: null },
};

function statusDot(section) {
    if (!section.is_enabled) return 'bg-stone-200';
    return statusConfig[section.completion_status]?.dot ?? 'bg-stone-200';
}
</script>

<template>
    <aside
        :class="[
            'flex-shrink-0 flex flex-col border-r border-stone-100 bg-white transition-all duration-200 overflow-hidden',
            collapsed ? 'w-12' : 'w-56',
        ]"
    >
        <!-- Header -->
        <div class="flex items-center justify-between px-3 py-3 border-b border-stone-100 flex-shrink-0">
            <span v-if="!collapsed" class="text-xs font-semibold text-stone-400 uppercase tracking-wider">
                Sections
            </span>
            <button
                @click="emit('update:collapsed', !collapsed)"
                class="p-1 rounded-md text-stone-400 hover:text-stone-600 hover:bg-stone-50 transition-colors ml-auto"
                :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <svg class="w-3.5 h-3.5 transition-transform" :class="collapsed ? 'rotate-180' : ''"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        <!-- Section groups -->
        <nav class="flex-1 overflow-y-auto py-2">
            <template v-for="group in groups" :key="group.label">
                <div v-if="groupSections(group.keys).length">

                    <!-- Group label (hidden when collapsed) -->
                    <p v-if="!collapsed" class="px-3 pt-3 pb-1 text-[10px] font-semibold text-stone-400 uppercase tracking-wider">
                        {{ group.label }}
                    </p>
                    <div v-else class="h-3"/>

                    <!-- Section items -->
                    <button
                        v-for="section in groupSections(group.keys)"
                        :key="section.section_key"
                        @click="emit('select', section.section_key)"
                        :class="[
                            'w-full flex items-center gap-2.5 px-3 py-2 text-sm transition-all duration-100 group',
                            activeSectionKey === section.section_key
                                ? 'bg-amber-50 text-amber-800 font-medium'
                                : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900',
                            !section.is_enabled ? 'opacity-50' : '',
                        ]"
                        :title="collapsed ? section.label : undefined"
                    >
                        <!-- Status dot -->
                        <span
                            class="w-2 h-2 rounded-full flex-shrink-0 transition-colors"
                            :class="statusDot(section)"
                        />

                        <!-- Label -->
                        <span v-if="!collapsed" class="flex-1 text-left truncate text-xs">
                            {{ section.label }}
                        </span>

                        <!-- Required badge -->
                        <span
                            v-if="!collapsed && section.is_required"
                            class="text-[9px] text-stone-300 font-medium flex-shrink-0"
                            title="Wajib"
                        >
                            *
                        </span>

                        <!-- Enable/disable toggle (non-required, non-collapsed) -->
                        <button
                            v-if="!collapsed && !section.is_required"
                            @click.stop="emit('toggle-section', section)"
                            class="opacity-0 group-hover:opacity-100 p-0.5 rounded transition-all hover:bg-stone-200 flex-shrink-0"
                            :title="section.is_enabled ? 'Nonaktifkan' : 'Aktifkan'"
                        >
                            <svg class="w-3 h-3 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path v-if="section.is_enabled"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                <path v-else
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </button>
                </div>
            </template>
        </nav>

        <!-- Legend (only expanded) -->
        <div v-if="!collapsed" class="border-t border-stone-100 px-3 py-2.5 flex gap-3 flex-shrink-0">
            <div class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"/>
                <span class="text-[10px] text-stone-400">Lengkap</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"/>
                <span class="text-[10px] text-stone-400">Perlu isi</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-stone-200"/>
                <span class="text-[10px] text-stone-400">Kosong</span>
            </div>
        </div>
    </aside>
</template>
