<script setup>
import { ref, computed, watch, onUnmounted, nextTick } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SectionBgControl from '@/Components/invitation/customize/SectionBgControl.vue';
import { TEMPLATE_MAP } from '@/Components/invitation/templates/registry';
import GalleryLayoutPicker from '@/Components/invitation/customize/GalleryLayoutPicker.vue';
import PhoneMockup from '@/Components/ui/PhoneMockup.vue';
import ContentModal from '@/Components/invitation/customize/ContentModal.vue';
import SectionGalleryPhotos from '@/Components/invitation/customize/SectionGalleryPhotos.vue';

const props = defineProps({
    invitation:    { type: Object,  required: true },
    canUsePremium: { type: Boolean, default: false },
});

const STORYBOOK_SLUG = 'storybook';

// ── Local form: mirrors config.section_backgrounds ────────────────────────
const form = ref(
    JSON.parse(JSON.stringify(props.invitation.config?.section_backgrounds ?? {}))
);

const uploadingKey = ref(null); // which section is currently uploading
const saveStatus   = ref('saved'); // 'saved' | 'saving' | 'error'
const activeTab    = ref('edit');  // mobile only: 'edit' | 'preview'
const activeKey    = ref(
    props.invitation.template_category_slug === STORYBOOK_SLUG ? 'gallery' : 'cover'
);

const groomName = computed(() => props.invitation.details?.groom_name ?? '—');
const brideName = computed(() => props.invitation.details?.bride_name ?? '—');

const previewTemplate = computed(() => TEMPLATE_MAP[props.invitation.template_slug] ?? null);

const isStorybook = computed(() =>
    props.invitation.template_category_slug === STORYBOOK_SLUG
)

const galleryLayout = ref(
    props.invitation.config?.gallery_layout ?? 'polaroid'
)

const galleries    = ref([...(props.invitation.galleries ?? [])])
const events       = ref([...(props.invitation.events     ?? [])])
const details      = ref({ ...(props.invitation.details   ?? {}) })
const sectionsData = ref(
    JSON.parse(JSON.stringify(props.invitation.sections ?? {}))
)
const modalSection = ref(null)

const previewInvitation = computed(() => ({
    ...props.invitation,
    config: {
        ...props.invitation.config,
        section_backgrounds: form.value,
        gallery_layout:      galleryLayout.value,
    },
    galleries:    galleries.value,
    events:       events.value,
    details:      details.value,
    sections:     sectionsData.value,
}));

// ── Sections config ───────────────────────────────────────────────────────
const SECTIONS_REGULAR = [
    { key: 'cover',   label: 'Cover'   },
    { key: 'opening', label: 'Opening' },
    { key: 'events',  label: 'Acara'   },
    { key: 'gallery', label: 'Galeri'  },
    { key: 'music',   label: 'Musik'   },
    { key: 'closing', label: 'Penutup' },
]

const SECTIONS_STORYBOOK = [
    { key: 'gallery',    label: 'Galeri'       },
    { key: 'events',     label: 'Date & Venue' },
    { key: 'love_story', label: 'Love Story'   },
    { key: 'couple',     label: 'Tentang Kami' },
    { key: 'rsvp',       label: 'RSVP'         },
    { key: 'gift',       label: 'Hadiah'       },
    { key: 'music',      label: 'Musik'        },
]

const sections = computed(() =>
    isStorybook.value ? SECTIONS_STORYBOOK : SECTIONS_REGULAR
)

// ── Background change handler ─────────────────────────────────────────────
function onBgChange(key, bg) {
    form.value = { ...form.value, [key]: bg };
}

// ── File upload ───────────────────────────────────────────────────────────
async function uploadBg(sectionKey, file) {
    uploadingKey.value = sectionKey;
    try {
        const fd = new FormData();
        fd.append('file', file);
        const res = await axios.post(
            `/dashboard/invitations/${props.invitation.id}/sections/${sectionKey}/background`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        onBgChange(sectionKey, {
            ...(form.value[sectionKey] ?? {}),
            type:  'image',
            value: res.data.url,
        });
        return true;
    } catch {
        alert('Upload gagal. Coba lagi.');
        return false;
    } finally {
        uploadingKey.value = null;
    }
}

// ── Save ──────────────────────────────────────────────────────────────────
async function save() {
    saveStatus.value = 'saving';
    try {
        const payload = isStorybook.value
            ? { gallery_layout: galleryLayout.value }
            : { section_backgrounds: form.value }

        await axios.post(`/dashboard/invitations/${props.invitation.id}/customize`, payload);
        saveStatus.value = 'saved';
    } catch {
        saveStatus.value = 'error';
    }
}

function openModal(key)   { modalSection.value = key }
function closeModal()     { modalSection.value = null }

function sectionBadge(key) {
    switch (key) {
        case 'gallery':    return galleries.value.length ? `${galleries.value.length} foto` : null
        case 'events':     return events.value.length    ? `${events.value.length} acara`   : null
        case 'love_story': {
            const count = sectionsData.value.love_story?.data?.stories?.length ?? 0
            return count ? `${count} chapter` : null
        }
        case 'couple':
            return (details.value.groom_name || details.value.bride_name) ? 'terisi' : null
        case 'gift': {
            const count = sectionsData.value.gift?.data?.accounts?.length ?? 0
            return count ? `${count} rekening` : null
        }
        default: return null
    }
}

async function toggleRsvp() {
    try {
        const res = await axios.patch(
            `/api/invitations/${props.invitation.id}/sections/rsvp/toggle`
        )
        if (!sectionsData.value.rsvp) sectionsData.value.rsvp = {}
        sectionsData.value.rsvp.is_enabled = res.data.is_enabled
    } catch {
        alert('Gagal mengubah RSVP.')
    }
}

// Auto-save 1.5s after last change
let autoSaveTimer = null;
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(save, 1500);
}

onUnmounted(() => clearTimeout(autoSaveTimer));

// ── Preview scroll-to-section ─────────────────────────────────────────────
const SECTION_SELECTORS = {
    cover:   '.pearl-gate, .n-cover',
    opening: '.pearl-opening, .n-opening',
    events:  '.pearl-events, .n-events',
    gallery: '.pearl-gallery, .n-gallery',
    closing: '.pearl-closing, .n-closing',
    music:   null,
};

watch(galleryLayout, () => scheduleAutoSave())

watch(activeKey, async (key) => {
    if (!key) return;
    await nextTick();
    const selector = SECTION_SELECTORS[key];
    if (!selector) return;
});
</script>

<template>
    <Head title="Kustomisasi Undangan" />
    <DashboardLayout>
        <!-- Premium lock overlay -->
        <div v-if="!canUsePremium" class="min-h-screen flex items-center justify-center p-8">
            <div class="max-w-sm text-center space-y-4">
                <div class="text-4xl">🔒</div>
                <h2 class="text-lg font-semibold text-stone-800">Fitur Premium</h2>
                <p class="text-sm text-stone-500">Kustomisasi tampilan per-section tersedia di paket Premium.</p>
                <Link href="/dashboard/paket" class="inline-block px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-[#92A89C] hover:opacity-90 transition-opacity">
                    Upgrade ke Premium
                </Link>
            </div>
        </div>

        <!-- Main content (premium users) -->
        <div v-else class="flex h-full min-h-screen">

            <!-- ── Left: Editor ──────────────────────────────────── -->
            <div class="w-full lg:w-[420px] flex-shrink-0 flex flex-col border-r border-stone-100 bg-white">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h1 class="text-sm font-bold text-stone-800">Kustomisasi</h1>
                        <p class="text-xs text-stone-400 mt-0.5">{{ groomName }} & {{ brideName }}</p>
                    </div>
                    <!-- Mobile tab toggle -->
                    <div class="flex lg:hidden bg-stone-100 rounded-lg p-0.5">
                        <button
                            type="button"
                            @click="activeTab = 'edit'"
                            :class="['text-xs px-3 py-1.5 rounded-md font-medium transition-all', activeTab === 'edit' ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500']"
                        >Edit</button>
                        <button
                            type="button"
                            @click="activeTab = 'preview'"
                            :class="['text-xs px-3 py-1.5 rounded-md font-medium transition-all', activeTab === 'preview' ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500']"
                        >Preview</button>
                    </div>
                </div>

                <!-- Mobile preview panel -->
                <div v-if="activeTab === 'preview'" class="lg:hidden flex-1 flex items-center justify-center bg-stone-100 p-6 overflow-y-auto">
                    <PhoneMockup screen-bg="#111">
                        <component
                            v-if="previewTemplate"
                            :is="previewTemplate"
                            :invitation="previewInvitation"
                            :is-demo="true"
                            :auto-open="true"
                        />
                        <div v-else class="flex items-center justify-center h-full text-stone-400 text-sm">
                            Template tidak ditemukan
                        </div>
                    </PhoneMockup>
                </div>

                <!-- Section accordion -->
                <div :class="['flex-1 overflow-y-auto divide-y divide-stone-50', activeTab === 'preview' ? 'hidden lg:block' : '']">
                    <div v-for="section in sections" :key="section.key">

                        <!-- Row button -->
                        <button
                            type="button"
                            @click="activeKey = activeKey === section.key ? null : section.key"
                            class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-stone-50 transition-colors"
                        >
                            <span class="text-sm font-medium text-stone-700">{{ section.label }}</span>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="isStorybook && sectionBadge(section.key)"
                                    class="text-xs px-2 py-0.5 rounded-full bg-stone-100 text-stone-500"
                                >{{ sectionBadge(section.key) }}</span>
                                <svg
                                    :class="['w-4 h-4 text-stone-400 transition-transform', activeKey === section.key ? 'rotate-180' : '']"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        <!-- Expanded content -->
                        <div v-if="activeKey === section.key" class="px-5 pb-5 space-y-3 bg-stone-50/50">

                            <!-- ── Storybook sections ─────────────────────── -->
                            <template v-if="isStorybook">

                                <!-- Gallery: layout picker + edit photos button -->
                                <template v-if="section.key === 'gallery'">
                                    <GalleryLayoutPicker
                                        :model-value="galleryLayout"
                                        @update:model-value="galleryLayout = $event"
                                    />
                                    <button
                                        type="button"
                                        @click="openModal('gallery')"
                                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl border border-stone-200 bg-white text-sm text-stone-600 hover:bg-stone-50 transition-colors"
                                    >
                                        <span>Edit Foto</span>
                                        <span class="text-stone-400">→</span>
                                    </button>
                                </template>

                                <!-- RSVP: inline toggle -->
                                <template v-else-if="section.key === 'rsvp'">
                                    <div class="flex items-center justify-between pt-1">
                                        <span class="text-sm text-stone-600">Aktifkan RSVP</span>
                                        <button
                                            type="button"
                                            @click="toggleRsvp"
                                            :class="[
                                                'w-10 h-6 rounded-full transition-colors relative',
                                                sectionsData.rsvp?.is_enabled ? 'bg-[#92A89C]' : 'bg-stone-200'
                                            ]"
                                        >
                                            <span :class="[
                                                'block w-4 h-4 bg-white rounded-full absolute top-1 transition-transform',
                                                sectionsData.rsvp?.is_enabled ? 'translate-x-5' : 'translate-x-1'
                                            ]" />
                                        </button>
                                    </div>
                                </template>

                                <!-- Music: link to edit page -->
                                <template v-else-if="section.key === 'music'">
                                    <p class="text-xs text-stone-500">Upload file musik (MP3, maks 10MB). Gunakan fitur upload musik di halaman edit undangan.</p>
                                    <Link
                                        :href="route('dashboard.invitations.edit', invitation.id)"
                                        class="inline-block text-xs px-3 py-2 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                                    >
                                        Buka Editor Musik →
                                    </Link>
                                </template>

                                <!-- Other sections: edit button -->
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="openModal(section.key)"
                                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl border border-stone-200 bg-white text-sm text-stone-600 hover:bg-stone-50 transition-colors"
                                    >
                                        <span>Edit {{ section.label }}</span>
                                        <span class="text-stone-400">→</span>
                                    </button>
                                </template>

                            </template>

                            <!-- ── Regular template sections ─────────────── -->
                            <template v-else>
                                <template v-if="section.key !== 'music'">
                                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider pt-2">Background</p>
                                    <SectionBgControl
                                        :model-value="form[section.key]"
                                        :section-key="section.key"
                                        :invitation-id="invitation.id"
                                        :uploading="uploadingKey === section.key"
                                        @update:model-value="(bg) => { onBgChange(section.key, bg); scheduleAutoSave(); }"
                                        @upload="(file) => uploadBg(section.key, file).then(ok => ok && scheduleAutoSave())"
                                    />
                                </template>
                                <template v-if="section.key === 'music'">
                                    <p class="text-xs text-stone-500">Upload file musik (MP3, maks 10MB). Gunakan fitur upload musik di halaman edit undangan.</p>
                                    <Link
                                        :href="route('dashboard.invitations.edit', invitation.id)"
                                        class="inline-block text-xs px-3 py-2 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                                    >
                                        Buka Editor Musik →
                                    </Link>
                                </template>
                            </template>

                        </div>
                    </div>
                </div>

                <!-- Content modals (editors wired in Tasks 4–8) -->
                <ContentModal
                    :open="modalSection !== null"
                    :title="sections.find(s => s.key === modalSection)?.label ?? ''"
                    @close="closeModal"
                >
                    <SectionGalleryPhotos
                        v-if="modalSection === 'gallery'"
                        :invitation-id="invitation.id"
                        :model-value="galleries"
                        @update:model-value="galleries = $event"
                    />
                    <div v-else class="text-sm text-stone-400 text-center py-8">Editor segera hadir.</div>
                </ContentModal>

                <!-- Footer: Save -->
                <div :class="['px-5 py-4 border-t border-stone-100 flex items-center gap-3', activeTab === 'preview' ? 'hidden lg:flex' : '']">
                    <button
                        type="button"
                        @click="save"
                        :disabled="saveStatus === 'saving'"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-[#92A89C] hover:opacity-90 disabled:opacity-60 transition-all"
                    >
                        {{ saveStatus === 'saving' ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <span v-if="saveStatus === 'saved'" class="text-xs text-emerald-500 font-medium">✓ Tersimpan</span>
                    <span v-if="saveStatus === 'error'"  class="text-xs text-red-400 font-medium">Gagal simpan</span>
                </div>
            </div>

            <!-- ── Right: Live preview (desktop only) ────────────── -->
            <div class="hidden lg:flex flex-1 items-center justify-center bg-stone-100 p-8">
                <PhoneMockup screen-bg="#111">
                    <component
                        v-if="previewTemplate"
                        :is="previewTemplate"
                        :invitation="previewInvitation"
                        :is-demo="true"
                        :auto-open="true"
                    />
                    <div v-else class="flex items-center justify-center h-full text-stone-400 text-sm">
                        Template tidak ditemukan
                    </div>
                </PhoneMockup>
            </div>

        </div>

    </DashboardLayout>
</template>

