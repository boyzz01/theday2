<script setup>
// Step 6 — Publikasi
// Sections: slug_settings (req), password_protection (opt), preview_and_publish (req)

import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';

const props = defineProps({
    publish:         { type: Object,   required: true },
    sections:        { type: Object,   required: true },
    invitationId:    { type: String,   default: null },
    isSaving:        { type: Boolean,  default: false },
    saveStep6:       { type: Function, required: true },
    template:        { type: Object,   required: true },
    basic:           { type: Object,   required: true },
    details:         { type: Object,   default: null },
    onToggleSection: { type: Function, required: true },
    canUsePremium:   { type: Boolean,  default: false },
});

const expanded       = ref('slug_settings');
const publishStatus  = ref(null); // null | 'draft' | 'published'
const publishError   = ref(null);

function toggle(key) {
    expanded.value = expanded.value === key ? null : key;
}

// ── Slug check ────────────────────────────────────────────────
const slugStatus     = ref(null);   // null | 'checking' | 'available' | 'taken'
const slugSuggestion = ref(null);
let slugTimer = null;

function toSlug(str) {
    return (str ?? '').toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

async function checkSlug(slug) {
    if (!slug || !props.invitationId) { slugStatus.value = null; return; }
    slugStatus.value     = 'checking';
    slugSuggestion.value = null;
    try {
        const res = await axios.get('/api/invitations/check-slug', {
            params: { slug, exclude_id: props.invitationId },
        });
        slugStatus.value = res.data.available ? 'available' : 'taken';
        slugSuggestion.value = res.data.suggestion ?? null;
    } catch {
        slugStatus.value = null;
    }
}

watch(() => props.publish.slug, (val) => {
    slugStatus.value = null;
    clearTimeout(slugTimer);
    if (!val) return;
    slugTimer = setTimeout(() => checkSlug(val), 500);
});
onMounted(() => { if (props.publish.slug) checkSlug(props.publish.slug); });

const invitationUrl = computed(() =>
    props.publish.slug ? `${window.location.origin}/i/${props.publish.slug}` : null
);

// ── Publish actions ───────────────────────────────────────────
async function handleAction(action) {
    publishError.value = null;
    try {
        const result = await props.saveStep6(action);
        publishStatus.value = result?.status ?? action;
    } catch (e) {
        publishError.value = e.response?.data?.message ?? 'Terjadi kesalahan.';
    }
}

function copyLink() {
    if (invitationUrl.value) navigator.clipboard.writeText(invitationUrl.value);
}

function goToDashboard() {
    router.visit(route('dashboard'));
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Publikasi</h2>
            <p class="text-sm text-stone-400 mt-0.5">Atur URL dan publikasikan undangan Anda</p>
        </div>

        <!-- Published success state -->
        <Transition name="slide-down">
            <div v-if="publishStatus === 'published'"
                 class="rounded-2xl border border-green-200 bg-green-50 p-5 text-center space-y-3">
                <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto">
                    <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-base font-semibold text-green-800">Undangan berhasil dipublikasikan!</p>
                    <p class="text-sm text-green-600 mt-0.5">Bagikan link di bawah kepada tamu undangan</p>
                </div>
                <div v-if="invitationUrl"
                     class="flex items-center gap-2 bg-white rounded-xl border border-green-200 px-4 py-2.5 text-sm">
                    <span class="flex-1 truncate text-stone-700 font-mono text-xs">{{ invitationUrl }}</span>
                    <button @click="copyLink"
                            class="flex items-center gap-1 text-xs font-medium text-[#73877C] hover:text-[#2C2417] transition-colors flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Salin
                    </button>
                </div>
                <button @click="goToDashboard"
                        class="px-5 py-2 rounded-xl text-sm font-semibold text-white transition-all"
                        style="background-color: #92A89C">
                    Kembali ke Dashboard
                </button>
            </div>
        </Transition>

        <template v-if="publishStatus !== 'published'">

            <!-- Slug Settings (required) -->
            <SectionAccordionCard
                title="URL Undangan"
                description="Alamat unik undangan yang akan dibagikan"
                :is-required="sections.slug_settings?.is_required ?? true"
                :is-enabled="sections.slug_settings?.is_enabled ?? true"
                :status="sections.slug_settings?.completion_status ?? 'empty'"
                :expanded="expanded === 'slug_settings'"
                @toggle-expand="toggle('slug_settings')"
                @toggle-enabled="onToggleSection('slug_settings')"
            >
                <div class="space-y-3">
                    <div :class="[
                        'flex rounded-xl border overflow-hidden focus-within:ring-2 focus-within:ring-[#92A89C]/50',
                        slugStatus === 'taken'     ? 'border-red-300'   :
                        slugStatus === 'available' ? 'border-green-300' : 'border-stone-200',
                    ]">
                        <span class="flex items-center px-3 bg-stone-50 border-r border-stone-200 text-xs text-stone-400 whitespace-nowrap">
                            {{ window?.location?.origin ?? '' }}/i/
                        </span>
                        <input
                            v-model="publish.slug"
                            type="text"
                            placeholder="nama-undangan-anda"
                            class="flex-1 px-3 py-2.5 text-sm bg-white focus:outline-none"
                        />
                        <span class="flex items-center pr-3">
                            <svg v-if="slugStatus === 'checking'" class="w-4 h-4 text-stone-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else-if="slugStatus === 'available'" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg v-else-if="slugStatus === 'taken'" class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    </div>
                    <p v-if="slugStatus === 'available'" class="text-xs text-green-600">URL tersedia</p>
                    <div v-else-if="slugStatus === 'taken'" class="flex items-center gap-2 flex-wrap">
                        <p class="text-xs text-red-500">URL sudah dipakai.</p>
                        <button v-if="slugSuggestion"
                                @click="publish.slug = slugSuggestion; slugSuggestion = null"
                                class="text-xs font-semibold text-[#73877C] underline hover:text-[#2C2417]">
                            Pakai "/{{ slugSuggestion }}"
                        </button>
                    </div>
                    <p v-else class="text-xs text-stone-400">Hanya huruf, angka, dan tanda hubung. Contoh: budi-dan-ani-2025</p>

                    <!-- Expiry -->
                    <div class="space-y-1.5 pt-1">
                        <label class="block text-sm font-medium text-stone-700">
                            Tanggal Kedaluwarsa <span class="text-stone-400 font-normal text-xs">(opsional)</span>
                        </label>
                        <input
                            v-model="publish.expires_at"
                            type="datetime-local"
                            class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                        />
                    </div>
                </div>
            </SectionAccordionCard>

            <!-- Password Protection — Premium only (Pattern A: hidden for free users) -->
            <SectionAccordionCard
                v-if="canUsePremium"
                title="Proteksi Password"
                description="Tamu harus memasukkan password untuk membuka undangan"
                :is-required="sections.password_protection?.is_required ?? false"
                :is-enabled="sections.password_protection?.is_enabled ?? false"
                :status="sections.password_protection?.completion_status ?? 'disabled'"
                :expanded="expanded === 'password_protection'"
                @toggle-expand="toggle('password_protection')"
                @toggle-enabled="onToggleSection('password_protection')"
            >
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Password</label>
                    <input
                        v-model="publish.password"
                        type="text"
                        placeholder="Masukkan password (min. 4 karakter)"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                    />
                    <p class="text-xs text-stone-400">Password akan diminta sebelum undangan terbuka.</p>
                </div>
            </SectionAccordionCard>

            <!-- Preview & Publish (required) -->
            <SectionAccordionCard
                title="Preview & Publikasi"
                description="Tinjau dan publikasikan undangan Anda"
                :is-required="sections.preview_and_publish?.is_required ?? true"
                :is-enabled="sections.preview_and_publish?.is_enabled ?? true"
                :status="sections.preview_and_publish?.completion_status ?? 'empty'"
                :expanded="expanded === 'preview_and_publish'"
                @toggle-expand="toggle('preview_and_publish')"
                @toggle-enabled="onToggleSection('preview_and_publish')"
            >
                <div class="space-y-4">
                    <!-- Summary -->
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg overflow-hidden border border-stone-200 flex-shrink-0 bg-stone-100">
                            <img v-if="template.thumbnail_url" :src="template.thumbnail_url"
                                 class="w-full h-full object-cover" alt="Template"/>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-stone-800">{{ basic.title || 'Judul Undangan' }}</p>
                            <p class="text-xs text-stone-400">{{ template.name }} · Pernikahan</p>
                        </div>
                        <span :class="[
                            'ml-auto text-xs font-medium px-2.5 py-1 rounded-lg',
                            invitationId ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-stone-100 text-stone-500',
                        ]">{{ invitationId ? 'Tersimpan' : 'Belum disimpan' }}</span>
                    </div>

                    <!-- Preview link -->
                    <a v-if="invitationUrl && publishStatus !== 'published'"
                       :href="`/i/${publish.slug}?preview=1`"
                       target="_blank"
                       class="w-full py-2.5 rounded-xl border border-stone-200 text-sm font-medium text-stone-700 hover:bg-stone-50 transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Preview Undangan
                    </a>

                    <!-- Error -->
                    <p v-if="publishError" class="text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-2.5">
                        {{ publishError }}
                    </p>

                    <!-- Action buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            @click="handleAction('draft')"
                            :disabled="isSaving || !invitationId"
                            class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-stone-200 text-sm font-semibold text-stone-700 hover:bg-stone-50 disabled:opacity-50 transition-all"
                        >
                            <svg v-if="isSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Simpan Draft
                        </button>
                        <button
                            @click="handleAction('publish')"
                            :disabled="isSaving || !invitationId"
                            class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold text-white disabled:opacity-50 transition-all"
                            style="background-color: #92A89C"
                        >
                            <svg v-if="isSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Publikasikan Sekarang
                        </button>
                    </div>
                </div>
            </SectionAccordionCard>

        </template>
    </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
