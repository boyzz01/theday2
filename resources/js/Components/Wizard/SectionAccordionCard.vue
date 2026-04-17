<script setup>
// SectionAccordionCard.vue
// Accordion card for wizard section. Required sections cannot be toggled off.
// Optional sections show a toggle switch. Status badges reflect completion.

const props = defineProps({
    title:            { type: String,  required: true },
    description:      { type: String,  default: '' },
    isRequired:       { type: Boolean, default: false },
    isEnabled:        { type: Boolean, default: true },
    status:           { type: String,  default: 'empty' }, // empty|incomplete|complete|warning|disabled|error
    expanded:         { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-enabled', 'toggle-expand']);

// Status badge config
const statusConfig = {
    empty:      { label: 'Belum diisi',  cls: 'bg-stone-100 text-stone-500 border-stone-200' },
    incomplete: { label: 'Belum lengkap', cls: 'bg-[#92A89C]/10 text-[#73877C] border-[#B8C7BF]' },
    complete:   { label: 'Lengkap',       cls: 'bg-green-50 text-green-700 border-green-200' },
    warning:    { label: 'Perlu cek',     cls: 'bg-[#C8A26B]/10 text-[#C8A26B] border-[#C8A26B]/30' },
    disabled:   { label: 'Nonaktif',      cls: 'bg-stone-100 text-stone-400 border-stone-200' },
    error:      { label: 'Error',         cls: 'bg-red-50 text-red-600 border-red-200' },
};

function resolvedStatus() {
    if (!props.isEnabled) return 'disabled';
    return props.status;
}
</script>

<template>
    <div :class="[
        'rounded-2xl border transition-all duration-200',
        !isEnabled
            ? 'border-stone-100 bg-stone-50/30 opacity-70'
            : expanded
                ? 'border-stone-200 bg-white shadow-sm'
                : 'border-stone-200 bg-white hover:border-stone-300',
    ]">
        <!-- ── Card Header ──────────────────────────────── -->
        <div
            :class="[
                'flex items-center gap-3 px-4 py-3.5',
                isEnabled ? 'cursor-pointer' : 'cursor-default',
            ]"
            @click="isEnabled && emit('toggle-expand')"
        >
            <!-- Title + description -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-stone-800 leading-tight">{{ title }}</span>

                    <!-- Required badge — only shown when required; optional sections show nothing -->
                    <span
                        v-if="isRequired"
                        class="text-xs font-medium px-1.5 py-0.5 rounded-md border bg-rose-50 text-rose-600 border-rose-200"
                    >Wajib</span>

                    <!-- Completion status badge -->
                    <span :class="[
                        'text-xs font-medium px-1.5 py-0.5 rounded-md border',
                        statusConfig[resolvedStatus()]?.cls ?? statusConfig.empty.cls,
                    ]">{{ statusConfig[resolvedStatus()]?.label }}</span>
                </div>
                <p v-if="description" class="text-xs text-stone-400 mt-0.5 leading-snug">{{ description }}</p>
            </div>

            <!-- Toggle switch (optional only) -->
            <button
                v-if="!isRequired"
                @click.stop="emit('toggle-enabled')"
                :class="[
                    'relative w-9 h-5 rounded-full transition-all duration-200 flex-shrink-0',
                    isEnabled ? 'bg-[#92A89C]' : 'bg-stone-200',
                ]"
                :title="isEnabled ? 'Nonaktifkan bagian ini' : 'Aktifkan bagian ini'"
            >
                <span :class="[
                    'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform duration-200',
                    isEnabled ? 'translate-x-4' : '',
                ]"/>
            </button>

            <!-- Expand/collapse chevron -->
            <svg
                v-if="isEnabled"
                :class="['w-4 h-4 text-stone-400 flex-shrink-0 transition-transform duration-200', expanded ? 'rotate-180' : '']"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        <!-- ── Card Body ────────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out overflow-hidden"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[2000px] opacity-100"
            leave-active-class="transition-all duration-150 ease-in overflow-hidden"
            leave-from-class="max-h-[2000px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-if="expanded && isEnabled" class="border-t border-stone-100 px-4 py-4">
                <slot />
            </div>
        </Transition>
    </div>
</template>
