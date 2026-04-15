<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

import EditorLayout       from '@/Layouts/EditorLayout.vue';
import EditorTopbar       from '@/Components/editor/EditorTopbar.vue';
import EditorSidebar      from '@/Components/editor/EditorSidebar.vue';
import SectionFormRenderer from '@/Components/editor/SectionFormRenderer.vue';
import InvitationRenderer  from '@/Components/invitation/InvitationRenderer.vue';

// ── Props from Inertia ────────────────────────────────────────────────────
const props = defineProps({
    invitation:       { type: Object, required: true },
    sections:         { type: Array,  required: true },
    activeSectionKey: { type: String, required: true },
});

// ── Local state ───────────────────────────────────────────────────────────
const localSections   = ref(props.sections.map(s => ({ ...s })));
const activeKey       = ref(props.activeSectionKey);
const saveStatus      = ref('saved');   // saved | saving | unsaved | error
const previewMode     = ref('mobile');
const sidebarCollapsed = ref(false);
const showPreview     = ref(true);      // mobile: toggle preview
const dirty           = ref(false);

let   autoSaveTimer   = null;
const AUTOSAVE_DELAY  = 1800; // ms

// ── Computed ──────────────────────────────────────────────────────────────
const activeSection = computed(() =>
    localSections.value.find(s => s.section_key === activeKey.value) ?? null
);

// Build a live invitation object for the preview, merging section data back
// into the shape InvitationRenderer expects.
const previewInvitation = computed(() => {
    const sectionData = Object.fromEntries(
        localSections.value.map(s => [s.section_type, s.data ?? {}])
    );
    const cover   = sectionData.cover   ?? {};
    const couple  = sectionData.couple  ?? {};
    const closing = sectionData.closing ?? {};

    return {
        ...props.invitation,
        template_slug: props.invitation.template?.slug,
        // Cover section data (opening gate)
        cover: {
            pretitle:               cover.pretitle               ?? 'The Wedding Of',
            couple_names:           cover.couple_names           ?? '',
            event_date_text:        cover.event_date_text        ?? '',
            intro_text:             cover.intro_text             ?? '',
            button_text:            cover.button_text            ?? 'Buka Undangan',
            guest_name_mode:        cover.guest_name_mode        ?? 'query_param',
            guest_name:             cover.guest_name             ?? null,
            guest_query_key:        cover.guest_query_key        ?? 'to',
            fallback_guest_text:    cover.fallback_guest_text    ?? 'Tamu Undangan',
            show_guest_name:        cover.show_guest_name        ?? true,
            background_image:       cover.background_image       ?? null,
            background_mobile_image: cover.background_mobile_image ?? null,
            background_position:    cover.background_position    ?? 'center',
            background_size:        cover.background_size        ?? 'cover',
            text_align:             cover.text_align             ?? 'center',
            content_position:       cover.content_position       ?? 'center',
            overlay_opacity:        cover.overlay_opacity        ?? 0.35,
            show_ornament:          cover.show_ornament          ?? true,
            show_date:              cover.show_date              ?? true,
            show_pretitle:          cover.show_pretitle          ?? true,
            music_on_open:          cover.music_on_open          ?? true,
            show_music_button:      cover.show_music_button      ?? false,
            open_action:            cover.open_action            ?? 'enter_content',
        },
        details: {
            groom_name:      props.invitation.details?.groom_name  ?? '',
            bride_name:      props.invitation.details?.bride_name  ?? '',
            groom_nickname:  couple.groom?.nickname   ?? '',
            bride_nickname:  couple.bride?.nickname   ?? '',
            groom_photo_url: couple.groom?.photo?.url ?? props.invitation.details?.groom_photo_url,
            bride_photo_url: couple.bride?.photo?.url ?? props.invitation.details?.bride_photo_url,
            cover_photo_url: cover.background_image?.url          ?? props.invitation.details?.cover_photo_url,
            opening_text:    sectionData.opening?.body             ?? props.invitation.details?.opening_text ?? '',
            closing_text:    closing.body                          ?? props.invitation.details?.closing_text ?? '',
        },
        events: (sectionData.events?.items ?? props.invitation.events ?? []).map(e => ({
            title:               e.title,
            event_date:          e.date,
            event_date_formatted: e.date,
            start_time:          e.start_time,
            end_time:            e.end_time,
            venue_name:          e.venue_name,
            address:             e.address,
            maps_url:            e.maps_url,
        })),
        galleries: (sectionData.gallery?.items ?? props.invitation.galleries ?? []).map(g => ({
            photo_url: g.url ?? g.image_url ?? '',
            caption:   g.caption ?? '',
        })),
        config: props.invitation.config ?? {},
        music: props.invitation.music ?? null,
    };
});

// ── Navigation ────────────────────────────────────────────────────────────
function selectSection(key) {
    activeKey.value = key;
    // Update URL without full reload
    window.history.replaceState({}, '',
        route('editor.invitations.section', {
            invitation: props.invitation.id,
            sectionKey: key,
        })
    );
}

// ── Data editing ─────────────────────────────────────────────────────────
function onSectionDataUpdate(newData) {
    const idx = localSections.value.findIndex(s => s.section_key === activeKey.value);
    if (idx === -1) return;
    localSections.value[idx] = { ...localSections.value[idx], data: newData };
    dirty.value = true;
    saveStatus.value = 'unsaved';
    scheduleAutosave();
}

function scheduleAutosave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(saveActiveSection, AUTOSAVE_DELAY);
}

async function saveActiveSection() {
    if (!activeSection.value || !dirty.value) return;
    saveStatus.value = 'saving';
    try {
        const { data } = await axios.patch(
            route('editor.sections.save', {
                invitation: props.invitation.id,
                section:    activeSection.value.id,
            }),
            { data: activeSection.value.data }
        );
        // Update local section with server response (updated completion_status etc.)
        const idx = localSections.value.findIndex(s => s.section_key === activeKey.value);
        if (idx !== -1) localSections.value[idx] = { ...localSections.value[idx], ...data.section };
        dirty.value = false;
        saveStatus.value = 'saved';
    } catch {
        saveStatus.value = 'error';
    }
}

// ── Toggle section enabled ────────────────────────────────────────────────
async function toggleSection(section) {
    const idx = localSections.value.findIndex(s => s.section_key === section.section_key);
    if (idx === -1) return;
    const newEnabled = !localSections.value[idx].is_enabled;
    localSections.value[idx] = { ...localSections.value[idx], is_enabled: newEnabled };
    try {
        await axios.patch(
            route('editor.sections.save', {
                invitation: props.invitation.id,
                section:    section.id,
            }),
            { is_enabled: newEnabled }
        );
    } catch {
        // Revert on failure
        localSections.value[idx] = { ...localSections.value[idx], is_enabled: !newEnabled };
    }
}

// ── Preview mode ──────────────────────────────────────────────────────────
function togglePreviewMode(mode) {
    previewMode.value = mode;
}

// ── Publish ───────────────────────────────────────────────────────────────
function handlePublish() {
    // Save first, then navigate to publish flow
    saveActiveSection().then(() => {
        router.post(route('dashboard.invitations.publish', { invitation: props.invitation.id }), {}, {
            onSuccess: () => {},
        });
    });
}

// ── Warn on unsaved changes ───────────────────────────────────────────────
function handleBeforeUnload(e) {
    if (dirty.value) {
        e.preventDefault();
        e.returnValue = '';
    }
}

onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload));
onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    clearTimeout(autoSaveTimer);
});

// Watch section change → save pending changes on current section first
watch(activeKey, async (newKey, oldKey) => {
    if (dirty.value && oldKey) {
        await saveActiveSection();
    }
});
</script>

<template>
    <EditorLayout>
        <!-- ── Top bar ──────────────────────────────────────────── -->
        <template #topbar>
            <EditorTopbar
                :invitation="invitation"
                :save-status="saveStatus"
                :preview-mode="previewMode"
                @toggle-preview-mode="togglePreviewMode"
                @publish="handlePublish"
            />
        </template>

        <!-- ── Left sidebar: section navigation ────────────────── -->
        <template #sidebar>
            <EditorSidebar
                :sections="localSections"
                :active-section-key="activeKey"
                v-model:collapsed="sidebarCollapsed"
                @select="selectSection"
                @toggle-section="toggleSection"
            />
        </template>

        <!-- ── Center: form panel ───────────────────────────────── -->
        <template #form>
            <div class="flex-1 min-w-0 flex flex-col min-h-0 border-r border-stone-100">

                <!-- Form scroll area -->
                <div class="flex-1 overflow-y-auto p-5">
                    <SectionFormRenderer
                        v-if="activeSection"
                        :section="activeSection"
                        :invitation-id="invitation.id"
                        @update:data="onSectionDataUpdate"
                    />
                    <div v-else class="py-16 text-center text-stone-400 text-sm">
                        Pilih section untuk mulai edit.
                    </div>
                </div>

                <!-- Save button (manual fallback) -->
                <div class="border-t border-stone-100 px-5 py-3 flex items-center justify-between flex-shrink-0 bg-white">
                    <span class="text-xs text-stone-400">
                        Autosave aktif — perubahan disimpan otomatis
                    </span>
                    <button
                        @click="saveActiveSection"
                        :disabled="!dirty"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            dirty
                                ? 'text-white active:scale-95'
                                : 'bg-stone-100 text-stone-400 cursor-not-allowed',
                        ]"
                        :style="dirty ? { backgroundColor: '#D4A373' } : {}"
                    >
                        Simpan
                    </button>
                </div>
            </div>
        </template>

        <!-- ── Right: live preview ──────────────────────────────── -->
        <template #preview>
            <div class="hidden lg:flex flex-col bg-stone-50 border-l border-stone-100 overflow-hidden"
                 :class="previewMode === 'mobile' ? 'w-80' : 'flex-1'">

                <!-- Preview header -->
                <div class="flex-shrink-0 px-4 py-2 border-b border-stone-100 bg-white flex items-center justify-between">
                    <span class="text-xs font-medium text-stone-500">Preview</span>
                    <span class="text-xs text-stone-400">
                        {{ previewMode === 'mobile' ? '📱 Mobile' : '🖥️ Desktop' }}
                    </span>
                </div>

                <!-- Preview content -->
                <div class="flex-1 overflow-y-auto flex items-start justify-center p-4">
                    <!-- transform creates new stacking context → contains position:fixed children -->
                    <div
                        :class="[
                            'transition-all duration-300 overflow-hidden rounded-2xl shadow-xl bg-white',
                            previewMode === 'mobile'
                                ? 'w-72 h-[580px]'
                                : 'w-full max-w-3xl',
                        ]"
                        style="transform: translateZ(0)"
                    >
                        <div :class="previewMode === 'mobile' ? 'overflow-y-auto h-full' : ''">
                            <InvitationRenderer
                                :invitation="previewInvitation"
                                :messages="[]"
                                :is-demo="activeKey !== 'cover'"
                                :auto-open="activeKey !== 'cover'"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </EditorLayout>
</template>
