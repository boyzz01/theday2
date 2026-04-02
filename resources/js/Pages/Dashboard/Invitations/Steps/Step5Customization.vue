<script setup>
import { computed } from 'vue';

const props = defineProps({
    customConfig: { type: Object, required: true },
    fonts:        { type: Array,  default: () => [] },
});

const colorPresets = [
    { label: 'Golden Sand',  value: '#D4A373' },
    { label: 'Rose Gold',    value: '#B76E79' },
    { label: 'Sage Green',   value: '#7D9B76' },
    { label: 'Dusty Blue',   value: '#7798AB' },
    { label: 'Mauve',        value: '#9A7B8A' },
    { label: 'Champagne',    value: '#C9A96E' },
    { label: 'Terracotta',   value: '#C47A5A' },
    { label: 'Navy',         value: '#3D5A80' },
];

// Group fonts by category
const fontsByCategory = computed(() => {
    const groups = {};
    for (const f of props.fonts) {
        if (!groups[f.category]) groups[f.category] = [];
        groups[f.category].push(f);
    }
    return groups;
});

const fontCategories = computed(() => Object.keys(fontsByCategory.value));
</script>

<template>
    <div class="p-6 space-y-8">

        <div>
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                Kustomisasi Tampilan
            </h2>
            <p class="text-sm text-stone-400 mt-0.5">Sesuaikan warna dan gaya tipografi undangan</p>
        </div>

        <!-- ── Warna utama ─────────────────────────────────── -->
        <div class="space-y-3">
            <label class="block text-sm font-medium text-stone-700">Warna Utama</label>

            <!-- Preset swatches -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="preset in colorPresets"
                    :key="preset.value"
                    @click="customConfig.primary_color = preset.value"
                    :title="preset.label"
                    :class="[
                        'w-9 h-9 rounded-xl border-2 transition-all',
                        customConfig.primary_color === preset.value
                            ? 'border-stone-600 scale-110 shadow-md'
                            : 'border-transparent hover:scale-105',
                    ]"
                    :style="{ backgroundColor: preset.value }"
                />
            </div>

            <!-- Custom color picker -->
            <div class="flex items-center gap-3">
                <div class="relative w-10 h-10 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                    <input
                        v-model="customConfig.primary_color"
                        type="color"
                        class="absolute inset-0 w-full h-full cursor-pointer opacity-0"
                    />
                    <div class="w-full h-full" :style="{ backgroundColor: customConfig.primary_color }"/>
                </div>
                <div class="flex-1">
                    <input
                        v-model="customConfig.primary_color"
                        type="text"
                        placeholder="#D4A373"
                        maxlength="7"
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition"
                    />
                </div>
                <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-stone-200 text-xs font-medium text-stone-600 hover:bg-stone-50 cursor-pointer transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Pilih
                    <input v-model="customConfig.primary_color" type="color" class="sr-only"/>
                </label>
            </div>

            <!-- Preview blob -->
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-stone-50 border border-stone-100">
                <div class="w-6 h-6 rounded-full flex-shrink-0" :style="{ backgroundColor: customConfig.primary_color }"/>
                <span class="text-sm text-stone-600">Preview warna utama</span>
                <div class="ml-auto h-2 flex-1 max-w-24 rounded-full" :style="{ backgroundColor: customConfig.primary_color + '40' }">
                    <div class="h-full rounded-full w-2/3" :style="{ backgroundColor: customConfig.primary_color }"/>
                </div>
            </div>
        </div>

        <!-- ── Font ───────────────────────────────────────── -->
        <div class="space-y-3">
            <label class="block text-sm font-medium text-stone-700">Font Utama</label>

            <select
                v-model="customConfig.font"
                class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition bg-white appearance-none"
            >
                <template v-for="cat in fontCategories" :key="cat">
                    <optgroup :label="cat">
                        <option v-for="f in fontsByCategory[cat]" :key="f.value" :value="f.value">
                            {{ f.label }}
                        </option>
                    </optgroup>
                </template>
            </select>

            <!-- Font preview tiles -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                    v-for="f in fonts"
                    :key="f.value"
                    @click="customConfig.font = f.value"
                    :class="[
                        'px-3 py-3 rounded-xl border text-center transition-all',
                        customConfig.font === f.value
                            ? 'border-amber-400 bg-amber-50'
                            : 'border-stone-200 hover:border-stone-300 hover:bg-stone-50',
                    ]"
                >
                    <p :style="{ fontFamily: f.value }" class="text-base text-stone-800 leading-tight truncate">
                        Aa
                    </p>
                    <p class="text-xs text-stone-400 mt-0.5 truncate">{{ f.label }}</p>
                </button>
            </div>

            <!-- Live preview -->
            <div class="px-5 py-4 rounded-xl bg-stone-50 border border-stone-100 text-center">
                <p :style="{ fontFamily: customConfig.font, color: customConfig.primary_color }"
                   class="text-2xl leading-snug">
                    Undangan Pernikahan
                </p>
                <p :style="{ fontFamily: customConfig.font }" class="text-sm text-stone-500 mt-1">
                    Ahmad Budi & Siti Ani
                </p>
            </div>
        </div>

    </div>
</template>
