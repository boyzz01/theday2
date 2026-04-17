<script setup>
import { computed, reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { useInvitationEditor } from '@/Composables/useInvitationEditor.js';
import StepInformasi  from './Steps/StepInformasi.vue';
import StepAcara      from './Steps/StepAcara.vue';
import StepMedia      from './Steps/StepMedia.vue';
import StepInteraksi  from './Steps/StepInteraksi.vue';
import StepTampilan   from './Steps/StepTampilan.vue';
import StepPublikasi  from './Steps/StepPublikasi.vue';

const props = defineProps({
    template:      { type: Object,  required: true },
    invitation:    { type: Object,  default: null },
    defaultMusic:  { type: Array,   default: () => [] },
    fonts:         { type: Array,   default: () => [] },
    templates:     { type: Array,   default: () => [] },
    canUsePremium: { type: Boolean, default: false },
});

const editor = reactive(useInvitationEditor(props.template, props.invitation));

// Local reactive template — updated when user changes template
const currentTemplate = ref({ ...props.template });

function onTemplateChanged(newTemplate) {
    currentTemplate.value = newTemplate;
}

const steps = [
    { number: 1, label: 'Informasi',  key: 'informasi' },
    { number: 2, label: 'Acara',      key: 'acara' },
    { number: 3, label: 'Media',      key: 'media' },
    { number: 4, label: 'Interaksi',  key: 'interaksi' },
    { number: 5, label: 'Tampilan',   key: 'tampilan' },
    { number: 6, label: 'Publikasi',  key: 'publikasi' },
];

const stepSaveMap = {
    1: () => editor.saveStep1(),
    2: () => editor.saveStep2(),
    3: () => editor.saveStep3(),
    4: () => editor.saveStep4(),
    5: () => editor.saveStep5(),
};

async function next() {
    const save = stepSaveMap[editor.currentStep];
    if (save) {
        try {
            await save();
        } catch {
            return;
        }
    }
    if (editor.currentStep < 6) editor.currentStep++;
}

function back() {
    if (editor.currentStep > 1) editor.currentStep--;
}

function goTo(n) {
    if (n <= (editor.lastSavedStep ?? 0) + 1 || n <= editor.currentStep) {
        editor.currentStep = n;
    }
}

const progressPercent = computed(() => Math.round(((editor.currentStep - 1) / 5) * 100));
</script>

<template>
    <Head title="Buat Undangan" />

    <DashboardLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <span class="text-sm text-stone-400">Buat Undangan</span>
                <span class="text-stone-300">/</span>
                <span class="text-sm font-medium text-stone-700 truncate max-w-xs">{{ currentTemplate.name }}</span>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">

            <!-- ── Step indicator ──────────────────────────────── -->
            <div class="mb-6">
                <!-- Progress bar -->
                <div class="h-1.5 bg-stone-100 rounded-full mb-4 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        style="background-color: #92A89C"
                        :style="{ width: progressPercent + '%' }"
                    />
                </div>

                <!-- Step dots -->
                <div class="flex items-center justify-between">
                    <button
                        v-for="step in steps"
                        :key="step.number"
                        @click="goTo(step.number)"
                        :class="[
                            'flex flex-col items-center gap-1 transition-all duration-200',
                            step.number <= (editor.lastSavedStep ?? 0) + 1
                                ? 'cursor-pointer'
                                : 'cursor-default opacity-40 pointer-events-none',
                        ]"
                    >
                        <div :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-200 border-2',
                            editor.currentStep === step.number
                                ? 'border-[#92A89C] text-white'
                                : (editor.lastSavedStep ?? 0) >= step.number
                                    ? 'border-green-300 bg-green-50 text-green-700'
                                    : 'border-stone-200 bg-stone-50 text-stone-400',
                        ]"
                        :style="editor.currentStep === step.number ? 'background-color: #92A89C' : ''"
                        >
                            <svg v-if="(editor.lastSavedStep ?? 0) >= step.number && editor.currentStep !== step.number"
                                 class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span v-else>{{ step.number }}</span>
                        </div>
                        <span :class="[
                            'hidden sm:block text-xs font-medium',
                            editor.currentStep === step.number ? 'text-[#73877C]' : 'text-stone-400',
                        ]">{{ step.label }}</span>
                    </button>
                </div>
            </div>

            <!-- ── Step content card ───────────────────────────── -->
            <div class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden">

                <!-- Error banner -->
                <Transition name="slide-down">
                    <div v-if="editor.saveError"
                         class="bg-red-50 border-b border-red-100 px-6 py-3 flex items-center gap-2 text-sm text-red-700">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ editor.saveError }}
                    </div>
                </Transition>

                <!-- Step panels -->
                <Transition name="step-fade" mode="out-in">
                    <StepInformasi
                        v-if="editor.currentStep === 1"
                        :basic="editor.basic"
                        :details="editor.details"
                        :sections="editor.sections"
                        :upload-photo-field="editor.uploadPhotoField"
                        :delete-photo-field="editor.deletePhotoField"
                        :on-toggle-section="editor.toggleSection"
                    />
                    <StepAcara
                        v-else-if="editor.currentStep === 2"
                        :events="editor.events"
                        :sections="editor.sections"
                        :add-event="editor.addEvent"
                        :remove-event="editor.removeEvent"
                        :move-event="editor.moveEvent"
                        :on-toggle-section="editor.toggleSection"
                    />
                    <StepMedia
                        v-else-if="editor.currentStep === 3"
                        :galleries="editor.galleries"
                        :sections="editor.sections"
                        :invitation-id="editor.invitationId"
                        :upload-gallery-file="editor.uploadGalleryFile"
                        :remove-gallery="editor.removeGallery"
                        :move-gallery="editor.moveGallery"
                        :on-toggle-section="editor.toggleSection"
                    />
                    <StepInteraksi
                        v-else-if="editor.currentStep === 4"
                        :sections="editor.sections"
                        :on-toggle-section="editor.toggleSection"
                    />
                    <StepTampilan
                        v-else-if="editor.currentStep === 5"
                        :custom-config="editor.customConfig"
                        :fonts="fonts"
                        :sections="editor.sections"
                        :selected-music="editor.selectedMusic"
                        :default-music="defaultMusic"
                        :invitation-id="editor.invitationId"
                        :upload-audio="editor.uploadAudio"
                        :on-toggle-section="editor.toggleSection"
                        :template="currentTemplate"
                        :templates="templates"
                        :can-use-premium="canUsePremium"
                        :invitation-status="editor.publish?.status ?? invitation?.status ?? 'draft'"
                        @update:selected-music="editor.selectedMusic = $event"
                        @template-changed="onTemplateChanged"
                    />
                    <StepPublikasi
                        v-else-if="editor.currentStep === 6"
                        :publish="editor.publish"
                        :sections="editor.sections"
                        :invitation-id="editor.invitationId"
                        :is-saving="editor.isSaving"
                        :save-step6="editor.saveStep6"
                        :template="currentTemplate"
                        :basic="editor.basic"
                        :details="editor.details"
                        :on-toggle-section="editor.toggleSection"
                    />
                </Transition>

                <!-- ── Footer nav ──────────────────────────────── -->
                <div v-if="editor.currentStep < 6"
                     class="border-t border-stone-100 px-4 py-4 flex items-center justify-between bg-stone-50/50 sticky bottom-0">
                    <button
                        v-if="editor.currentStep > 1"
                        @click="back"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-stone-600 hover:text-stone-900 rounded-xl hover:bg-stone-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Kembali
                    </button>
                    <div v-else />

                    <button
                        @click="next"
                        :disabled="editor.isSaving"
                        class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition-all disabled:opacity-60"
                        style="background-color: #92A89C"
                    >
                        <svg v-if="editor.isSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ editor.isSaving ? 'Menyimpan…' : editor.currentStep === 5 ? 'Lanjut ke Publikasi' : 'Simpan & Lanjut' }}
                        <svg v-if="!editor.isSaving" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.step-fade-enter-active,
.step-fade-leave-active { transition: opacity 0.18s, transform 0.18s; }
.step-fade-enter-from   { opacity: 0; transform: translateX(12px); }
.step-fade-leave-to     { opacity: 0; transform: translateX(-12px); }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
