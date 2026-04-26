<script setup>
// Step 2 — Tampilan
// Section: template picker only

import { ref } from 'vue';
import TemplatePicker from '@/Components/Wizard/TemplatePicker.vue';

const props = defineProps({
    customConfig:    { type: Object,  required: true },
    sections:        { type: Object,  required: true },
    invitationId:    { type: String,  default: null },
    onToggleSection: { type: Function, required: true },
    template:        { type: Object,  required: true },
    templates:       { type: Array,   default: () => [] },
    canUsePremium:   { type: Boolean, default: false },
    invitationStatus:{ type: String,  default: 'draft' },
});

const emit = defineEmits(['update:selectedMusic', 'template-changed']);

const showPicker = ref(false);

function onTemplateChanged(newTemplate) {
    emit('template-changed', newTemplate);
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Tampilan</h2>
            <p class="text-sm text-stone-400 mt-0.5">Pilih template untuk undangan kamu</p>
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
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#73877C] bg-[#92A89C]/10 border border-[#B8C7BF] rounded-lg hover:bg-[#92A89C]/20 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Ganti Template
                </button>
            </div>
            <div class="relative h-40 bg-stone-100 overflow-hidden">
                <img v-if="template.thumbnail_url" :src="template.thumbnail_url" :alt="template.name"
                     class="w-full h-full object-cover object-top"/>
                <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span v-if="template.tier === 'premium'"
                    class="absolute top-2 right-2 px-2 py-0.5 bg-stone-800 text-[#B8C7BF] text-xs font-semibold rounded-full">
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

    </div>
</template>
