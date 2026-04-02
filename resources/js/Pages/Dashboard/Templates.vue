<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    categories: Array,
    templates:  Array,
    filters:    Object,
});

// ── Filter state ──────────────────────────────────────────────────
const activeCategory = ref(props.filters.category);
const activeTier     = ref(props.filters.tier);

// Debounced router push when filters change
watch([activeCategory, activeTier], ([cat, tier]) => {
    router.get(
        route('dashboard.templates'),
        { category: cat === 'all' ? undefined : cat, tier: tier === 'all' ? undefined : tier },
        { preserveState: true, preserveScroll: true, replace: true }
    );
});

// ── Preview modal ─────────────────────────────────────────────────
const previewTemplate = ref(null);
const showPreview     = ref(false);

const openPreview = (template) => {
    previewTemplate.value = template;
    showPreview.value = true;
};
const closePreview = () => { showPreview.value = false; };

// Close on Escape
const onKeydown = (e) => { if (e.key === 'Escape') closePreview(); };
if (typeof window !== 'undefined') {
    window.addEventListener('keydown', onKeydown);
}

// ── Helpers ───────────────────────────────────────────────────────
const tierConfig = {
    free:    { label: 'Gratis',  bg: '#D1FAE5', color: '#065F46' },
    premium: { label: 'Premium', bg: '#FEF3C7', color: '#92400E' },
};

const primaryColor   = (t) => t.default_config?.primary_color   ?? '#D4A373';
const secondaryColor = (t) => t.default_config?.secondary_color ?? '#FEFAE0';
const accentColor    = (t) => t.default_config?.accent_color    ?? '#CCD5AE';
const fontTitle      = (t) => t.default_config?.font_title      ?? 'serif';

const useTemplate = (templateId) => {
    try {
        router.visit(route('dashboard.invitations.create', { template: templateId }));
    } catch {
        // Fallback if Ziggy doesn't have the route yet (needs cache clear)
        window.location.href = `/dashboard/invitations/create?template=${templateId}`;
    }
};

const allCategories = computed(() => [
    { slug: 'all', name: 'Semua' },
    ...props.categories,
]);

const tiers = [
    { value: 'all',     label: 'Semua' },
    { value: 'free',    label: 'Gratis' },
    { value: 'premium', label: 'Premium' },
];

const filteredCount = computed(() => props.templates.length);
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-base font-semibold text-stone-800">Pilih Template</h1>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- ── Page heading ─────────────────────────────────── -->
            <div>
                <h2 class="text-xl font-semibold text-stone-800">Template Undangan</h2>
                <p class="text-sm text-stone-400 mt-1">
                    Pilih template yang sesuai dengan acaramu. Semua bisa dikustomisasi warna & font-nya.
                </p>
            </div>

            <!-- ── Filters ──────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                <!-- Category tabs -->
                <div class="flex items-center gap-1 bg-stone-100 rounded-xl p-1">
                    <button
                        v-for="cat in allCategories"
                        :key="cat.slug"
                        @click="activeCategory = cat.slug"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                            activeCategory === cat.slug
                                ? 'bg-white text-stone-800 shadow-sm'
                                : 'text-stone-500 hover:text-stone-700',
                        ]"
                    >
                        {{ cat.name }}
                    </button>
                </div>

                <!-- Tier pills -->
                <div class="flex items-center gap-2 sm:ml-auto">
                    <span class="text-xs text-stone-400 font-medium">Tier:</span>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="tier in tiers"
                            :key="tier.value"
                            @click="activeTier = tier.value"
                            :class="[
                                'px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150 border',
                                activeTier === tier.value
                                    ? 'text-white border-transparent shadow-sm'
                                    : 'bg-white text-stone-500 border-stone-200 hover:border-stone-300',
                            ]"
                            :style="activeTier === tier.value ? 'background-color: #D4A373; border-color: #D4A373' : ''"
                        >
                            {{ tier.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Result count -->
            <p class="text-xs text-stone-400">
                Menampilkan <span class="font-semibold text-stone-600">{{ filteredCount }}</span> template
            </p>

            <!-- ── Template Grid ─────────────────────────────────── -->
            <div v-if="templates.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="template in templates"
                    :key="template.id"
                    class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden group hover:shadow-lg hover:-translate-y-1 transition-all duration-200"
                >
                    <!-- Thumbnail / Color preview -->
                    <div
                        class="relative overflow-hidden"
                        style="height: 200px"
                        :style="`background: linear-gradient(160deg, ${secondaryColor(template)}, ${primaryColor(template)}33)`"
                    >
                        <!-- Thumbnail image if available -->
                        <img
                            v-if="template.thumbnail_url"
                            :src="template.thumbnail_url"
                            :alt="template.name"
                            class="w-full h-full object-cover"
                        />

                        <!-- Generated preview (no thumbnail) -->
                        <template v-else>
                            <!-- Decorative circles -->
                            <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full opacity-20"
                                 :style="`background-color: ${primaryColor(template)}`"/>
                            <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full opacity-15"
                                 :style="`background-color: ${accentColor(template)}`"/>

                            <!-- Mock invitation content -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6">
                                <div class="w-10 h-px mb-3" :style="`background-color: ${primaryColor(template)}`"/>
                                <p class="text-xs tracking-widest uppercase font-medium mb-1"
                                   :style="`color: ${primaryColor(template)}; font-family: '${fontTitle(template)}', serif`">
                                    {{ template.category.name }}
                                </p>
                                <p class="text-lg font-semibold text-stone-700 leading-tight"
                                   :style="`font-family: '${fontTitle(template)}', serif`">
                                    Nama & Nama
                                </p>
                                <div class="flex items-center gap-2 my-2">
                                    <div class="w-6 h-px" :style="`background-color: ${primaryColor(template)}`"/>
                                    <div class="w-1.5 h-1.5 rounded-full" :style="`background-color: ${primaryColor(template)}`"/>
                                    <div class="w-6 h-px" :style="`background-color: ${primaryColor(template)}`"/>
                                </div>
                                <p class="text-xs text-stone-500">Sabtu, 12 Juli 2025</p>
                                <div class="w-10 h-px mt-3" :style="`background-color: ${primaryColor(template)}`"/>
                            </div>
                        </template>

                        <!-- Tier badge -->
                        <span
                            class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-semibold"
                            :style="`background-color: ${tierConfig[template.tier].bg}; color: ${tierConfig[template.tier].color}`"
                        >
                            {{ tierConfig[template.tier].label }}
                        </span>

                        <!-- Color swatches -->
                        <div class="absolute top-3 right-3 flex gap-1.5">
                            <div class="w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm"
                                 :style="`background-color: ${primaryColor(template)}`"/>
                            <div class="w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm"
                                 :style="`background-color: ${accentColor(template)}`"/>
                        </div>

                        <!-- Hover overlay -->
                        <div class="absolute inset-0 flex items-center justify-center gap-3 opacity-0 group-hover:opacity-100 transition-all duration-200"
                             style="background: rgba(0,0,0,0.4); backdrop-filter: blur(2px)">
                            <button
                                @click="openPreview(template)"
                                class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white text-stone-800 text-xs font-semibold shadow-md hover:bg-stone-50 transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Preview
                            </button>
                            <button
                                @click="useTemplate(template.id)"
                                class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-white text-xs font-semibold shadow-md transition-all hover:opacity-90"
                                :style="`background-color: ${primaryColor(template)}`"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Gunakan
                            </button>
                        </div>
                    </div>

                    <!-- Card footer -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <p class="text-sm font-semibold text-stone-800 leading-tight">{{ template.name }}</p>
                            <span class="text-xs text-stone-400 flex-shrink-0">{{ template.category.name }}</span>
                        </div>
                        <p v-if="template.description" class="text-xs text-stone-400 leading-relaxed line-clamp-2 mb-3">
                            {{ template.description }}
                        </p>

                        <div class="flex gap-2">
                            <button
                                @click="openPreview(template)"
                                class="flex-1 py-2 rounded-xl text-xs font-medium border border-stone-200 text-stone-600 hover:bg-stone-50 transition-colors"
                            >
                                Preview
                            </button>
                            <button
                                @click="useTemplate(template.id)"
                                class="flex-1 py-2 rounded-xl text-xs font-semibold text-white transition-all hover:opacity-90"
                                :style="`background-color: ${primaryColor(template)}`"
                            >
                                Gunakan Template
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Empty state ───────────────────────────────────── -->
            <div v-else class="py-24 text-center">
                <div class="text-5xl mb-4">🎨</div>
                <p class="text-sm font-medium text-stone-600 mb-1">Tidak ada template ditemukan</p>
                <p class="text-xs text-stone-400">Coba ubah filter kategori atau tier.</p>
                <button
                    @click="activeCategory = 'all'; activeTier = 'all'"
                    class="mt-5 px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background-color: #D4A373"
                >
                    Reset Filter
                </button>
            </div>

        </div>

        <!-- ── Preview Modal ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showPreview && previewTemplate"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px)"
                    @click.self="closePreview"
                >
                    <div class="bg-white rounded-3xl overflow-hidden shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">

                        <!-- Modal header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-stone-100 flex-shrink-0">
                            <div class="flex items-center gap-3">
                                <span
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :style="`background-color: ${tierConfig[previewTemplate.tier].bg}; color: ${tierConfig[previewTemplate.tier].color}`"
                                >
                                    {{ tierConfig[previewTemplate.tier].label }}
                                </span>
                                <h3 class="text-base font-semibold text-stone-800">{{ previewTemplate.name }}</h3>
                                <span class="text-xs text-stone-400">· {{ previewTemplate.category.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="useTemplate(previewTemplate.id); closePreview()"
                                    class="px-5 py-2 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                                    :style="`background-color: ${primaryColor(previewTemplate)}`"
                                >
                                    Gunakan Template
                                </button>
                                <button
                                    @click="closePreview"
                                    class="p-2 rounded-xl text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Preview area: two-panel -->
                        <div class="flex-1 overflow-auto flex flex-col md:flex-row">

                            <!-- Phone mockup preview -->
                            <div
                                class="flex-1 flex items-center justify-center p-8"
                                :style="`background: linear-gradient(135deg, ${secondaryColor(previewTemplate)}, ${primaryColor(previewTemplate)}22)`"
                            >
                                <div class="w-64 bg-white rounded-3xl shadow-2xl overflow-hidden border border-stone-100">
                                    <!-- Phone notch -->
                                    <div class="bg-stone-900 h-6 flex items-center justify-center">
                                        <div class="w-16 h-3 bg-stone-800 rounded-full"/>
                                    </div>
                                    <!-- Invitation preview content -->
                                    <div
                                        class="flex flex-col items-center text-center px-5 py-8"
                                        :style="`background: linear-gradient(160deg, ${secondaryColor(previewTemplate)}, ${primaryColor(previewTemplate)}11); min-height: 420px`"
                                    >
                                        <div class="w-12 h-px mb-4" :style="`background-color: ${primaryColor(previewTemplate)}`"/>
                                        <p class="text-xs tracking-widest uppercase font-medium mb-3"
                                           :style="`color: ${primaryColor(previewTemplate)}`">
                                            {{ previewTemplate.category.name }}
                                        </p>
                                        <p class="text-2xl font-semibold text-stone-700 mb-1 leading-tight"
                                           :style="`font-family: '${fontTitle(previewTemplate)}', serif`">
                                            Rini
                                        </p>
                                        <div class="flex items-center gap-2 my-2">
                                            <div class="w-8 h-px" :style="`background-color: ${primaryColor(previewTemplate)}`"/>
                                            <span class="text-xs" :style="`color: ${primaryColor(previewTemplate)}`">&amp;</span>
                                            <div class="w-8 h-px" :style="`background-color: ${primaryColor(previewTemplate)}`"/>
                                        </div>
                                        <p class="text-2xl font-semibold text-stone-700 mb-5 leading-tight"
                                           :style="`font-family: '${fontTitle(previewTemplate)}', serif`">
                                            Budi
                                        </p>
                                        <div class="w-full rounded-xl px-4 py-3 mb-3 text-left"
                                             :style="`background: ${primaryColor(previewTemplate)}15`">
                                            <p class="text-xs text-stone-400 mb-0.5">Tanggal</p>
                                            <p class="text-xs font-semibold text-stone-700">Sabtu, 12 Juli 2025</p>
                                        </div>
                                        <div class="w-full rounded-xl px-4 py-3 text-left"
                                             :style="`background: ${primaryColor(previewTemplate)}15`">
                                            <p class="text-xs text-stone-400 mb-0.5">Lokasi</p>
                                            <p class="text-xs font-semibold text-stone-700">Gedung Serbaguna</p>
                                            <p class="text-xs text-stone-400">Jakarta Selatan</p>
                                        </div>
                                        <button
                                            class="mt-5 w-full py-2.5 rounded-xl text-xs font-semibold text-white"
                                            :style="`background-color: ${primaryColor(previewTemplate)}`"
                                        >
                                            Konfirmasi Kehadiran
                                        </button>
                                        <div class="w-12 h-px mt-5" :style="`background-color: ${primaryColor(previewTemplate)}`"/>
                                    </div>
                                </div>
                            </div>

                            <!-- Template info panel -->
                            <div class="w-full md:w-72 flex-shrink-0 border-t md:border-t-0 md:border-l border-stone-100 p-6 space-y-5">
                                <div>
                                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-2">Deskripsi</p>
                                    <p class="text-sm text-stone-600 leading-relaxed">
                                        {{ previewTemplate.description ?? 'Template elegan yang dapat dikustomisasi sesuai kebutuhanmu.' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-2">Palet Warna</p>
                                    <div class="flex gap-2">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-10 h-10 rounded-xl shadow-sm border border-stone-100"
                                                 :style="`background-color: ${primaryColor(previewTemplate)}`"/>
                                            <span class="text-xs text-stone-400">Primer</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-10 h-10 rounded-xl shadow-sm border border-stone-100"
                                                 :style="`background-color: ${secondaryColor(previewTemplate)}`"/>
                                            <span class="text-xs text-stone-400">Sekunder</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-10 h-10 rounded-xl shadow-sm border border-stone-100"
                                                 :style="`background-color: ${accentColor(previewTemplate)}`"/>
                                            <span class="text-xs text-stone-400">Aksen</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-2">Tipografi</p>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-stone-400">Judul</span>
                                            <span class="font-medium text-stone-700">{{ previewTemplate.default_config?.font_title }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-stone-400">Teks</span>
                                            <span class="font-medium text-stone-700">{{ previewTemplate.default_config?.font_body }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-stone-400">Animasi</span>
                                            <span class="font-medium text-stone-700">{{ previewTemplate.default_config?.animation }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-2">Fitur</p>
                                    <ul class="space-y-1.5">
                                        <li v-for="feat in ['Responsif mobile', 'Musik latar', 'RSVP online', 'Peta lokasi', 'Galeri foto']"
                                            :key="feat"
                                            class="flex items-center gap-2 text-xs text-stone-600">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" :style="`color: ${primaryColor(previewTemplate)}`"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ feat }}
                                        </li>
                                    </ul>
                                </div>

                                <button
                                    @click="useTemplate(previewTemplate.id); closePreview()"
                                    class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
                                    :style="`background-color: ${primaryColor(previewTemplate)}`"
                                >
                                    Gunakan Template Ini
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </DashboardLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
    transform: scale(0.96);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
