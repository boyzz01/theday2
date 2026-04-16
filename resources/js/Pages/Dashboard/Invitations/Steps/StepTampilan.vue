<script setup>
// Step 5 — Tampilan
// Sections: music (opt), theme_settings (req)

import { ref, computed, watch, onMounted } from 'vue';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';
import TemplatePicker from '@/Components/Wizard/TemplatePicker.vue';

const props = defineProps({
    customConfig:     { type: Object,   required: true },
    fonts:            { type: Array,    default: () => [] },
    sections:         { type: Object,   required: true },
    selectedMusic:    { type: Object,   default: null },
    defaultMusic:     { type: Array,    default: () => [] },
    invitationId:     { type: String,   default: null },
    uploadAudio:      { type: Function, required: true },
    onToggleSection:  { type: Function, required: true },
    // Template picker
    template:         { type: Object,   required: true },
    templates:        { type: Array,    default: () => [] },
    canUsePremium:    { type: Boolean,  default: false },
    invitationStatus: { type: String,   default: 'draft' },
});

const emit = defineEmits(['update:selectedMusic', 'template-changed']);

const showPicker = ref(false);

function onTemplateChanged(newTemplate) {
    emit('template-changed', newTemplate);
}

const expanded = ref('theme_settings');
const uploadingAudio = ref(false);

function toggle(key) {
    expanded.value = expanded.value === key ? null : key;
}

// ── Font loading ──────────────────────────────────────────────
const loadedFonts = new Set();
function loadGoogleFont(fontFamily) {
    if (!fontFamily || loadedFonts.has(fontFamily)) return;
    loadedFonts.add(fontFamily);
    const id = `gfont-${fontFamily.replace(/\s+/g, '-')}`;
    if (document.getElementById(id)) return;
    const link = document.createElement('link');
    link.id   = id;
    link.rel  = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(fontFamily)}:wght@400;600&display=swap`;
    document.head.appendChild(link);
}
onMounted(() => props.fonts.forEach(f => loadGoogleFont(f.value)));
watch(() => props.customConfig.font, loadGoogleFont, { immediate: true });

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

const fontsByCategory = computed(() => {
    const groups = {};
    for (const f of props.fonts) {
        if (!groups[f.category]) groups[f.category] = [];
        groups[f.category].push(f);
    }
    return groups;
});
const fontCategories = computed(() => Object.keys(fontsByCategory.value));

// ── Music upload ──────────────────────────────────────────────
async function handleAudioUpload(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file || !props.invitationId) return;
    uploadingAudio.value = true;
    try {
        const result = await props.uploadAudio(file);
        emit('update:selectedMusic', { title: result.name, file_url: result.url });
    } finally {
        uploadingAudio.value = false;
    }
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Tampilan</h2>
            <p class="text-sm text-stone-400 mt-0.5">Kustomisasi visual dan musik latar undangan</p>
        </div>

        <!-- Current Template Card -->
        <div class="rounded-2xl border border-stone-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-stone-100 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-stone-800">Template Aktif</p>
                    <p class="text-xs text-stone-400 mt-0.5">{{ template.name }}</p>
                </div>
                <button
                    @click="showPicker = true"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Ganti Template
                </button>
            </div>
            <div class="relative h-32 bg-stone-100 overflow-hidden">
                <img v-if="template.thumbnail_url" :src="template.thumbnail_url" :alt="template.name"
                     class="w-full h-full object-cover object-top"/>
                <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span v-if="template.tier === 'premium'"
                    class="absolute top-2 right-2 px-2 py-0.5 bg-stone-800 text-amber-300 text-xs font-semibold rounded-full">
                    Premium
                </span>
            </div>
        </div>

        <!-- Template Picker modal -->
        <Teleport to="body">
            <TemplatePicker
                v-if="showPicker"
                :invitation-id="invitationId"
                :current-template-id="template.id"
                :templates="templates"
                :can-use-premium="canUsePremium"
                :invitation-status="invitationStatus"
                @changed="onTemplateChanged"
                @close="showPicker = false"
            />
        </Teleport>

        <!-- Theme Settings (required) -->
        <SectionAccordionCard
            title="Tema & Tipografi"
            description="Warna utama dan font undangan"
            :is-required="sections.theme_settings?.is_required ?? true"
            :is-enabled="sections.theme_settings?.is_enabled ?? true"
            :status="sections.theme_settings?.completion_status ?? 'empty'"
            :expanded="expanded === 'theme_settings'"
            @toggle-expand="toggle('theme_settings')"
            @toggle-enabled="onToggleSection('theme_settings')"
        >
            <div class="space-y-6">
                <!-- Warna -->
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-stone-700">Warna Utama</label>
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
                    <div class="flex items-center gap-2">
                        <div class="relative w-10 h-10 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                            <input v-model="customConfig.primary_color" type="color"
                                   class="absolute inset-0 w-full h-full cursor-pointer opacity-0"/>
                            <div class="w-full h-full" :style="{ backgroundColor: customConfig.primary_color }"/>
                        </div>
                        <input
                            v-model="customConfig.primary_color"
                            type="text" placeholder="#D4A373" maxlength="7"
                            class="flex-1 px-3 py-2 rounded-xl border border-stone-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition"
                        />
                    </div>
                </div>

                <!-- Font -->
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-stone-700">Font Utama</label>
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
                            <p :style="{ fontFamily: f.value }" class="text-base text-stone-800 leading-tight">Aa</p>
                            <p class="text-xs text-stone-400 mt-0.5 truncate">{{ f.label }}</p>
                        </button>
                    </div>
                    <!-- Live preview -->
                    <div class="px-5 py-4 rounded-xl bg-stone-50 border border-stone-100 text-center">
                        <p :style="{ fontFamily: customConfig.font, color: customConfig.primary_color }"
                           class="text-2xl leading-snug">Undangan Pernikahan</p>
                        <p :style="{ fontFamily: customConfig.font }" class="text-sm text-stone-500 mt-1">
                            Ahmad Budi & Siti Ani
                        </p>
                    </div>
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Music (optional) -->
        <SectionAccordionCard
            title="Musik Latar"
            description="Musik yang diputar saat undangan dibuka"
            :is-required="sections.music?.is_required ?? false"
            :is-enabled="sections.music?.is_enabled ?? false"
            :status="sections.music?.completion_status ?? 'disabled'"
            :expanded="expanded === 'music'"
            @toggle-expand="toggle('music')"
            @toggle-enabled="onToggleSection('music')"
        >
            <div class="space-y-4">
                <!-- Selected music badge -->
                <div v-if="selectedMusic"
                     class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl">
                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                    </svg>
                    <span class="text-sm font-medium text-green-800 flex-1 truncate">{{ selectedMusic.title }}</span>
                    <button @click="$emit('update:selectedMusic', null)"
                            class="text-green-600 hover:text-red-500 transition-colors text-xs font-medium">Hapus</button>
                </div>

                <!-- Default music list -->
                <div class="space-y-2">
                    <p class="text-xs font-medium text-stone-500 uppercase tracking-wide">Pilihan Musik</p>
                    <div class="space-y-1.5">
                        <button
                            v-for="music in defaultMusic"
                            :key="music.id"
                            @click="$emit('update:selectedMusic', music)"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border text-left transition-all',
                                selectedMusic?.file_url === music.file_url
                                    ? 'border-amber-300 bg-amber-50'
                                    : 'border-stone-200 hover:border-stone-300 hover:bg-stone-50',
                            ]"
                        >
                            <div :class="[
                                'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                                selectedMusic?.file_url === music.file_url ? 'bg-amber-400' : 'bg-stone-100',
                            ]">
                                <svg :class="['w-4 h-4', selectedMusic?.file_url === music.file_url ? 'text-white' : 'text-stone-400']"
                                     fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                            <span class="text-sm text-stone-700 truncate">{{ music.title }}</span>
                        </button>
                    </div>
                </div>

                <!-- Upload custom -->
                <div>
                    <p class="text-xs font-medium text-stone-500 uppercase tracking-wide mb-2">Upload Sendiri</p>
                    <label :class="[
                        'w-full py-3 rounded-xl border-2 border-dashed text-sm font-medium flex items-center justify-center gap-2 cursor-pointer transition-all',
                        uploadingAudio ? 'border-amber-200 text-amber-600' : 'border-stone-200 text-stone-500 hover:border-amber-300 hover:text-amber-700',
                    ]">
                        <svg v-if="uploadingAudio" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        {{ uploadingAudio ? 'Mengunggah…' : 'Upload MP3 / WAV' }}
                        <input type="file" accept="audio/*" class="sr-only"
                               :disabled="uploadingAudio || !invitationId"
                               @change="handleAudioUpload"/>
                    </label>
                    <p v-if="!invitationId" class="text-xs text-orange-500 mt-1">Simpan Step 1 terlebih dahulu untuk upload audio.</p>
                </div>
            </div>
        </SectionAccordionCard>

    </div>
</template>
