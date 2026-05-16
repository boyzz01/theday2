<!-- resources/js/Components/invitation/customize/GalleryLayoutPicker.vue -->
<script setup>
defineProps({
    modelValue: { type: String, default: 'polaroid' },
})
defineEmits(['update:modelValue'])

const layouts = [
    { key: 'polaroid', label: 'Polaroid', icon: '🃏', desc: 'Tersebar acak' },
    { key: 'masonry',  label: 'Masonry',  icon: '⬜', desc: 'Grid dinamis' },
    { key: 'grid',     label: 'Grid',     icon: '⊞', desc: 'Seragam 2 kolom' },
    { key: 'scroll',   label: 'Scroll',   icon: '↔', desc: 'Horizontal geser' },
]
</script>

<template>
    <div class="space-y-2">
        <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Layout Galeri</p>
        <div class="grid grid-cols-2 gap-2">
            <button
                v-for="layout in layouts"
                :key="layout.key"
                type="button"
                :class="[
                    'flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 text-center transition-all',
                    modelValue === layout.key
                        ? 'border-[#92A89C] bg-[#92A89C]/10 text-stone-800'
                        : 'border-stone-200 bg-white text-stone-500 hover:border-stone-300',
                ]"
                @click="$emit('update:modelValue', layout.key)"
            >
                <span class="text-xl">{{ layout.icon }}</span>
                <span class="text-xs font-semibold">{{ layout.label }}</span>
                <span class="text-[10px] leading-tight text-stone-400">{{ layout.desc }}</span>
            </button>
        </div>
    </div>
</template>
