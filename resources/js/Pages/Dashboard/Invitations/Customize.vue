<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SectionBgControl from '@/Components/invitation/customize/SectionBgControl.vue';
import { TEMPLATE_MAP } from '@/Components/invitation/templates/registry';

const props = defineProps({
    invitation:    { type: Object,  required: true },
    canUsePremium: { type: Boolean, default: false },
});

// ── Local form: mirrors config.section_backgrounds ────────────────────────
const form = ref(
    JSON.parse(JSON.stringify(props.invitation.config?.section_backgrounds ?? {}))
);

const uploadingKey = ref(null); // which section is currently uploading
const saveStatus   = ref('saved'); // 'saved' | 'saving' | 'error'
const activeKey    = ref('cover');

const groomName = computed(() => props.invitation.details?.groom_name ?? '—');
const brideName = computed(() => props.invitation.details?.bride_name ?? '—');

const previewTemplate = computed(() => TEMPLATE_MAP[props.invitation.template_slug] ?? null);

const previewInvitation = computed(() => ({
    ...props.invitation,
    config: {
        ...props.invitation.config,
        section_backgrounds: form.value,
    },
}));

// ── Sections config ───────────────────────────────────────────────────────
const SECTIONS = [
    { key: 'cover',   label: 'Cover',   icon: '🖼️' },
    { key: 'opening', label: 'Opening', icon: '✉️' },
    { key: 'events',  label: 'Acara',   icon: '📅' },
    { key: 'gallery', label: 'Galeri',  icon: '🖼️' },
    { key: 'music',   label: 'Musik',   icon: '🎵' },
    { key: 'closing', label: 'Penutup', icon: '💌' },
];

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
        await axios.post(`/dashboard/invitations/${props.invitation.id}/customize`, {
            section_backgrounds: form.value,
        });
        saveStatus.value = 'saved';
    } catch {
        saveStatus.value = 'error';
    }
}

// Auto-save 1.5s after last change
let autoSaveTimer = null;
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(save, 1500);
}

onUnmounted(() => clearTimeout(autoSaveTimer));
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
                <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                    <div>
                        <h1 class="text-sm font-bold text-stone-800">Kustomisasi</h1>
                        <p class="text-xs text-stone-400 mt-0.5">{{ groomName }} & {{ brideName }}</p>
                    </div>
                    <Link
                        :href="`/${invitation.slug}`"
                        target="_blank"
                        class="text-xs px-3 py-1.5 rounded-lg border border-stone-200 text-stone-500 hover:bg-stone-50 transition-colors lg:hidden"
                    >
                        Lihat Preview
                    </Link>
                </div>

                <!-- Section accordion -->
                <div class="flex-1 overflow-y-auto divide-y divide-stone-50">
                    <div v-for="section in SECTIONS" :key="section.key">
                        <button
                            type="button"
                            @click="activeKey = activeKey === section.key ? null : section.key"
                            class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-stone-50 transition-colors"
                        >
                            <span class="text-sm font-medium text-stone-700">{{ section.label }}</span>
                            <svg
                                :class="['w-4 h-4 text-stone-400 transition-transform', activeKey === section.key ? 'rotate-180' : '']"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div v-if="activeKey === section.key" class="px-5 pb-5 space-y-4 bg-stone-50/50">
                            <!-- Background control (all sections except music) -->
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

                            <!-- Music upload -->
                            <template v-if="section.key === 'music'">
                                <p class="text-xs text-stone-500">Upload file musik (MP3, maks 10MB). Gunakan fitur upload musik di halaman edit undangan.</p>
                                <Link
                                    :href="route('dashboard.invitations.edit', invitation.id)"
                                    class="inline-block text-xs px-3 py-2 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                                >
                                    Buka Editor Musik →
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer: Save -->
                <div class="px-5 py-4 border-t border-stone-100 flex items-center gap-3">
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
            <div class="hidden lg:flex flex-1 bg-stone-100 overflow-y-auto">
                <div class="preview-panel-wrapper">
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
                </div>
            </div>

        </div>

        <!-- Mobile: floating preview button -->
        <a
            :href="`/${invitation.slug}`"
            target="_blank"
            class="lg:hidden fixed bottom-6 right-4 z-50 px-4 py-2.5 rounded-full shadow-lg text-xs font-bold text-white bg-[#92A89C]"
        >
            Lihat Preview
        </a>
    </DashboardLayout>
</template>

<style scoped>
.preview-panel-wrapper {
    width: 390px;
    min-height: 100%;
    transform-origin: top left;
    margin: 0 auto;
    background: white;
    box-shadow: 0 0 40px rgba(0,0,0,0.12);
}
</style>
