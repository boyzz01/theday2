<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    publish:      { type: Object,   required: true },
    invitationId: { type: String,   default: null },
    isSaving:     { type: Boolean,  default: false },
    saveStep6:    { type: Function, required: true },
    template:     { type: Object,   required: true },
    basic:        { type: Object,   required: true },
    details:      { type: Object,   default: null },
});

const publishStatus = ref(null); // 'draft' | 'published'
const publishError  = ref(null);

// ── Slug availability ─────────────────────────────────────────────
const slugStatus    = ref(null); // null | 'checking' | 'available' | 'taken'
const slugSuggestion = ref(null);
let   slugCheckTimer = null;

function toSlug(str) {
    return (str ?? '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function generateDefaultSlug() {
    if (props.basic.event_type === 'pernikahan') {
        const bride = toSlug(props.details?.bride_name ?? '');
        const groom = toSlug(props.details?.groom_name ?? '');
        if (bride && groom) return `${bride}-${groom}`;
        return bride || groom;
    } else {
        return toSlug(props.details?.birthday_person_name ?? '');
    }
}

async function checkSlug(slug) {
    if (!slug || !props.invitationId) { slugStatus.value = null; return; }
    slugStatus.value    = 'checking';
    slugSuggestion.value = null;
    try {
        const res = await axios.get('/api/invitations/check-slug', {
            params: { slug, exclude_id: props.invitationId },
        });
        slugStatus.value     = res.data.available ? 'available' : 'taken';
        slugSuggestion.value = res.data.suggestion ?? null;

        // If taken, also try reversed order (groom-bride)
        if (!res.data.available && props.basic.event_type === 'pernikahan') {
            const groom = toSlug(props.details?.groom_name ?? '');
            const bride = toSlug(props.details?.bride_name ?? '');
            const reversed = `${groom}-${bride}`;
            if (reversed !== slug && groom && bride) {
                const res2 = await axios.get('/api/invitations/check-slug', {
                    params: { slug: reversed, exclude_id: props.invitationId },
                });
                if (res2.data.available) {
                    slugSuggestion.value = reversed;
                }
            }
        }
    } catch {
        slugStatus.value = null;
    }
}

// Debounce check on manual input
watch(() => props.publish.slug, (val) => {
    slugStatus.value = null;
    clearTimeout(slugCheckTimer);
    if (!val) return;
    slugCheckTimer = setTimeout(() => checkSlug(val), 500);
});

// Only check existing slug on mount (no auto-generate)
onMounted(async () => {
    if (props.publish.slug) await checkSlug(props.publish.slug);
});

function applySuggestion() {
    if (slugSuggestion.value) {
        props.publish.slug = slugSuggestion.value;
        slugSuggestion.value = null;
    }
}

const invitationUrl = computed(() => {
    if (!props.publish.slug) return null;
    return `${window.location.origin}/i/${props.publish.slug}`;
});

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
    if (invitationUrl.value) {
        navigator.clipboard.writeText(invitationUrl.value);
    }
}

function goToDashboard() {
    router.visit(route('dashboard'));
}
</script>

<template>
    <div class="p-6 space-y-6">

        <div>
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                Review & Publikasi
            </h2>
            <p class="text-sm text-stone-400 mt-0.5">Atur URL, keamanan, dan jadwal undangan Anda</p>
        </div>

        <!-- Success state -->
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
                    <p class="text-sm text-green-600 mt-0.5">Bagikan link di bawah kepada tamu undangan Anda</p>
                </div>
                <div v-if="invitationUrl"
                     class="flex items-center gap-2 bg-white rounded-xl border border-green-200 px-4 py-2.5 text-sm">
                    <span class="flex-1 truncate text-stone-700 font-mono text-xs">{{ invitationUrl }}</span>
                    <button @click="copyLink"
                            class="flex items-center gap-1 text-xs font-medium text-amber-700 hover:text-amber-800 transition-colors flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Salin
                    </button>
                </div>
                <button @click="goToDashboard"
                        class="px-5 py-2 rounded-xl text-sm font-semibold text-white transition-all"
                        style="background-color: #D4A373">
                    Kembali ke Dashboard
                </button>
            </div>

            <div v-else-if="publishStatus === 'draft'"
                 class="rounded-2xl border border-stone-200 bg-stone-50 p-5 text-center space-y-3">
                <div class="w-14 h-14 rounded-full bg-stone-100 flex items-center justify-center mx-auto">
                    <svg class="w-7 h-7 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-base font-semibold text-stone-700">Disimpan sebagai draft</p>
                    <p class="text-sm text-stone-400 mt-0.5">Anda dapat mempublikasikannya kapan saja dari dashboard</p>
                </div>
                <button @click="goToDashboard"
                        class="px-5 py-2 rounded-xl text-sm font-semibold text-stone-700 bg-stone-200 hover:bg-stone-300 transition-all">
                    Kembali ke Dashboard
                </button>
            </div>
        </Transition>

        <!-- Form (only show if not yet published) -->
        <template v-if="!publishStatus">

            <!-- Summary card -->
            <div class="rounded-xl border border-stone-200 bg-stone-50/50 divide-y divide-stone-100 overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden border border-stone-200 flex-shrink-0 bg-stone-100">
                        <img v-if="template.thumbnail_url" :src="template.thumbnail_url"
                             class="w-full h-full object-cover" alt="Template"/>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-stone-800">{{ basic.title || 'Judul Undangan' }}</p>
                        <p class="text-xs text-stone-400">{{ template.name }} · {{ basic.event_type === 'pernikahan' ? 'Pernikahan' : 'Ulang Tahun' }}</p>
                    </div>
                    <span :class="[
                        'ml-auto text-xs font-medium px-2.5 py-1 rounded-lg',
                        invitationId ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-stone-100 text-stone-500',
                    ]">
                        {{ invitationId ? 'Tersimpan' : 'Belum disimpan' }}
                    </span>
                </div>
            </div>

            <!-- Slug / URL -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-stone-700">URL Undangan</label>
                <div :class="[
                    'flex rounded-xl border overflow-hidden focus-within:ring-2 focus-within:ring-amber-300',
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
                    <!-- Status icon -->
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

                <!-- Status messages -->
                <p v-if="slugStatus === 'available'" class="text-xs text-green-600">URL tersedia</p>
                <div v-else-if="slugStatus === 'taken'" class="flex items-center gap-2 flex-wrap">
                    <p class="text-xs text-red-500">URL sudah dipakai.</p>
                    <button v-if="slugSuggestion"
                            @click="applySuggestion"
                            class="text-xs font-semibold text-amber-700 underline hover:text-amber-900 transition-colors">
                        Pakai "/{{ slugSuggestion }}"
                    </button>
                </div>
                <p v-else class="text-xs text-stone-400">Hanya huruf, angka, dan tanda hubung. Contoh: budi-dan-ani-2025</p>
            </div>

            <!-- Password protection -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-stone-700">Proteksi Password</p>
                        <p class="text-xs text-stone-400">Tamu harus memasukkan password untuk membuka undangan</p>
                    </div>
                    <button
                        @click="publish.is_password_protected = !publish.is_password_protected"
                        :class="[
                            'relative w-10 h-6 rounded-full transition-all duration-200 flex-shrink-0',
                            publish.is_password_protected ? 'bg-amber-400' : 'bg-stone-200',
                        ]"
                    >
                        <span :class="[
                            'absolute top-1 left-1 w-4 h-4 rounded-full bg-white shadow-sm transition-transform duration-200',
                            publish.is_password_protected ? 'translate-x-4' : '',
                        ]"/>
                    </button>
                </div>
                <Transition name="slide-down">
                    <input
                        v-if="publish.is_password_protected"
                        v-model="publish.password"
                        type="text"
                        placeholder="Masukkan password (min. 4 karakter)"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition"
                    />
                </Transition>
            </div>

            <!-- Expiry date -->
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-stone-700">Tanggal Kedaluwarsa <span class="text-stone-400 font-normal">(opsional)</span></label>
                <input
                    v-model="publish.expires_at"
                    type="datetime-local"
                    class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent transition"
                />
                <p class="text-xs text-stone-400">Undangan akan otomatis tidak dapat diakses setelah tanggal ini</p>
            </div>

            <!-- Error -->
            <p v-if="publishError" class="text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-2.5">
                {{ publishError }}
            </p>

            <!-- Action buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
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
                    style="background-color: #D4A373"
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

        </template>
    </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
