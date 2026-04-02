<script setup>
import { computed }         from 'vue';
import { Head }             from '@inertiajs/vue3';
import PublicLayout         from '@/Layouts/PublicLayout.vue';
import AuthModal            from '@/Components/auth/AuthModal.vue';
import { useAuthWall }      from '@/Composables/useAuthWall.js';
import { useInvitationEditor } from '@/Composables/useInvitationEditor.js';
import Step1BasicInfo     from '@/Pages/Dashboard/Invitations/Steps/Step1BasicInfo.vue';
import Step2Events        from '@/Pages/Dashboard/Invitations/Steps/Step2Events.vue';
import Step3Gallery       from '@/Pages/Dashboard/Invitations/Steps/Step3Gallery.vue';
import Step4Music         from '@/Pages/Dashboard/Invitations/Steps/Step4Music.vue';
import Step5Customization from '@/Pages/Dashboard/Invitations/Steps/Step5Customization.vue';
import Step6Review        from '@/Pages/Dashboard/Invitations/Steps/Step6Review.vue';

const props = defineProps({
    template:     { type: Object, required: true },
    defaultMusic: { type: Array,  default: () => [] },
    fonts:        { type: Array,  default: () => [] },
    isGuest:      { type: Boolean, default: true },
    guestDraft:   { type: Object, default: null },
});

// ── Auth wall ─────────────────────────────────────────────────────────────────
const { isAuthModalOpen, authModalTab, requireAuth, onAuthenticated, onDismiss } = useAuthWall();

// ── Editor ────────────────────────────────────────────────────────────────────
const editor = useInvitationEditor(props.template);
const { currentStep, isSaving, saveError } = editor;

const steps = [
    { number: 1, label: 'Informasi', shortLabel: '1' },
    { number: 2, label: 'Acara',     shortLabel: '2' },
    { number: 3, label: 'Galeri',    shortLabel: '3' },
    { number: 4, label: 'Musik',     shortLabel: '4' },
    { number: 5, label: 'Tampilan',  shortLabel: '5' },
    { number: 6, label: 'Publikasi', shortLabel: '6' },
];

// ── Save actions (guarded for guest) ─────────────────────────────────────────

const stepSaveMap = {
    1: () => editor.saveStep1(),
    2: () => editor.saveStep2(),
    3: () => editor.saveStep3(),
    4: () => editor.saveStep4(),
    5: () => editor.saveStep5(),
};

async function next() {
    // All save actions require auth (API calls need session)
    if (props.isGuest) {
        const user = await requireAuth('register');
        if (! user) return;
        // After reload isGuest becomes false — the save will proceed on next call
        return;
    }

    const save = stepSaveMap[currentStep.value];
    if (save) {
        try { await save(); } catch { return; }
    }
    if (currentStep.value < 6) currentStep.value++;
}

function back() {
    if (currentStep.value > 1) currentStep.value--;
}

function goTo(n) {
    if (n <= editor.lastSavedStep.value + 1 || n <= currentStep.value) {
        currentStep.value = n;
    }
}

// Upload wrappers — require auth before file upload
async function guardedUploadPhotoField(file, field) {
    if (props.isGuest) {
        const user = await requireAuth('register');
        if (! user) return;
    }
    return editor.uploadPhotoField(file, field);
}

async function guardedUploadGalleryFile(file) {
    if (props.isGuest) {
        const user = await requireAuth('register');
        if (! user) return;
    }
    return editor.uploadGalleryFile(file);
}

async function guardedUploadAudio(file) {
    if (props.isGuest) {
        const user = await requireAuth('register');
        if (! user) return;
    }
    return editor.uploadAudio(file);
}

const progressPercent = computed(() => Math.round(((currentStep.value - 1) / 5) * 100));
</script>

<template>
    <Head title="Buat Undangan — TheDay" />

    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 py-8">

            <!-- Guest banner -->
            <div
                v-if="isGuest"
                class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl border text-sm"
                style="background-color: #FEF3C7; border-color: #FDE68A; color: #92400E"
            >
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    Mode preview — kamu belum login.
                    <button
                        type="button"
                        @click="requireAuth('register')"
                        class="font-semibold underline ml-1 hover:opacity-80"
                    >
                        Daftar gratis
                    </button>
                    untuk menyimpan & mempublikasikan.
                </span>
            </div>

            <!-- Step indicator -->
            <div class="mb-6">
                <div class="h-1.5 bg-stone-100 rounded-full mb-4 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        style="background-color: #D4A373"
                        :style="{ width: progressPercent + '%' }"
                    />
                </div>
                <div class="flex items-center justify-between">
                    <button
                        v-for="step in steps"
                        :key="step.number"
                        @click="goTo(step.number)"
                        :class="[
                            'flex flex-col items-center gap-1 transition-all duration-200',
                            step.number <= (editor.lastSavedStep.value ?? 0) + 1
                                ? 'cursor-pointer'
                                : 'cursor-default opacity-40 pointer-events-none',
                        ]"
                    >
                        <div
                            :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-200 border-2',
                                currentStep === step.number
                                    ? 'border-amber-400 text-white'
                                    : (editor.lastSavedStep.value ?? 0) >= step.number
                                        ? 'border-green-300 bg-green-50 text-green-700'
                                        : 'border-stone-200 bg-stone-50 text-stone-400',
                            ]"
                            :style="currentStep === step.number ? 'background-color: #D4A373' : ''"
                        >
                            <svg v-if="(editor.lastSavedStep.value ?? 0) >= step.number && currentStep !== step.number"
                                 class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span v-else>{{ step.shortLabel }}</span>
                        </div>
                        <span :class="[
                            'hidden sm:block text-xs font-medium',
                            currentStep === step.number ? 'text-amber-700' : 'text-stone-400',
                        ]">{{ step.label }}</span>
                    </button>
                </div>
            </div>

            <!-- Step content card -->
            <div class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden">
                <Transition name="slide-down">
                    <div v-if="saveError"
                         class="bg-red-50 border-b border-red-100 px-6 py-3 flex items-center gap-2 text-sm text-red-700">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ saveError }}
                    </div>
                </Transition>

                <Transition name="step-fade" mode="out-in">
                    <Step1BasicInfo
                        v-if="currentStep === 1"
                        :basic="editor.basic"
                        :details="editor.details"
                        :template="template"
                        :upload-photo-field="guardedUploadPhotoField"
                        :invitation-id="editor.invitationId"
                    />
                    <Step2Events
                        v-else-if="currentStep === 2"
                        :events="editor.events"
                        :add-event="editor.addEvent"
                        :remove-event="editor.removeEvent"
                        :move-event="editor.moveEvent"
                    />
                    <Step3Gallery
                        v-else-if="currentStep === 3"
                        :galleries="editor.galleries"
                        :invitation-id="editor.invitationId"
                        :upload-gallery-file="guardedUploadGalleryFile"
                        :remove-gallery="editor.removeGallery"
                        :move-gallery="editor.moveGallery"
                    />
                    <Step4Music
                        v-else-if="currentStep === 4"
                        :selected-music="editor.selectedMusic"
                        :default-music="defaultMusic"
                        :invitation-id="editor.invitationId"
                        :upload-audio="guardedUploadAudio"
                        @select="editor.selectedMusic.value = $event"
                    />
                    <Step5Customization
                        v-else-if="currentStep === 5"
                        :custom-config="editor.customConfig"
                        :fonts="fonts"
                    />
                    <Step6Review
                        v-else-if="currentStep === 6"
                        :publish="editor.publish"
                        :invitation-id="editor.invitationId"
                        :is-saving="isSaving"
                        :save-step6="editor.saveStep6"
                        :template="template"
                        :basic="editor.basic"
                    />
                </Transition>

                <!-- Footer nav -->
                <div v-if="currentStep < 6"
                     class="border-t border-stone-100 px-6 py-4 flex items-center justify-between bg-stone-50/50">
                    <button
                        v-if="currentStep > 1"
                        @click="back"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-stone-600 hover:text-stone-900 rounded-xl hover:bg-stone-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Kembali
                    </button>
                    <div v-else />

                    <button
                        @click="next"
                        :disabled="isSaving"
                        class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-xl transition-all disabled:opacity-60"
                        style="background-color: #D4A373"
                    >
                        <svg v-if="isSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ isSaving ? 'Menyimpan…' : currentStep === 5 ? 'Lanjut ke Review' : 'Simpan & Lanjut' }}
                        <svg v-if="!isSaving" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Auth Wall Modal -->
        <AuthModal
            :is-open="isAuthModalOpen"
            :initial-tab="authModalTab"
            @close="onDismiss"
            @authenticated="onAuthenticated"
        />
    </PublicLayout>
</template>

<style scoped>
.step-fade-enter-active, .step-fade-leave-active { transition: opacity 0.18s, transform 0.18s; }
.step-fade-enter-from   { opacity: 0; transform: translateX(12px); }
.step-fade-leave-to     { opacity: 0; transform: translateX(-12px); }
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
