<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import TemplatePreviewModal from '@/Components/templates/TemplatePreviewModal.vue';
import { useLocale } from '@/Composables/useLocale';

const props = defineProps({
    categories: Array,
    templates:  Array,
    filters:    Object,
    isGuest:    { type: Boolean, default: true },
});

// ── Filter state ──────────────────────────────────────────────────
const activeCategory = ref(props.filters.category);
const activeTier     = ref(props.filters.tier);

function applyFilters() {
    router.get(
        '/templates',
        {
            category: activeCategory.value === 'all' ? undefined : activeCategory.value,
            tier:     activeTier.value     === 'all' ? undefined : activeTier.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

// ── Preview modal ─────────────────────────────────────────────────
const previewTemplate = ref(null);
const showPreview     = ref(false);

const openPreview  = (t) => { previewTemplate.value = t; showPreview.value = true; };
const closePreview = () => { showPreview.value = false; };

// ── Helpers ───────────────────────────────────────────────────────
const tierConfig = {
    free:    { label: 'Gratis',  bg: '#D1FAE5', color: '#065F46' },
    premium: { label: 'Premium', bg: '#EFF2F0', color: '#2C2417' },
};

const primaryColor   = (t) => t.default_config?.primary_color   ?? '#92A89C';
const secondaryColor = (t) => t.default_config?.secondary_color ?? '#FEFAE0';
const accentColor    = (t) => t.default_config?.accent_color    ?? '#CCD5AE';
const fontTitle      = (t) => t.default_config?.font_title      ?? 'serif';

function useTemplate(templateId) {
    router.visit(`/use-template/${templateId}`);
}

const { locale, t } = useLocale();

const allCategories = computed(() => [
    { slug: 'all', name: t('Semua', 'All') },
    ...props.categories.map(c => ({ ...c, name: locale.value === 'en' ? (c.name_en || c.name) : c.name })),
]);

const tiers = computed(() => [
    { value: 'all',     label: t('Semua', 'All') },
    { value: 'free',    label: t('Gratis', 'Free') },
    { value: 'premium', label: 'Premium' },
]);

const tName  = (tmpl) => locale.value === 'en' ? (tmpl.name_en        || tmpl.name)        : tmpl.name;
const tDesc  = (tmpl) => locale.value === 'en' ? (tmpl.description_en || tmpl.description) : tmpl.description;
const tCat   = (tmpl) => locale.value === 'en' ? (tmpl.category.name_en || tmpl.category.name) : tmpl.category.name;
const tTier  = (tier) => tier === 'free' ? t('Gratis', 'Free') : 'Premium';
</script>

<template>
    <Head title="Pilih Template — TheDay" />

    <PublicLayout>
        <div class="max-w-6xl mx-auto px-6 py-10 space-y-6">

            <!-- Heading -->
            <div class="text-center max-w-xl mx-auto mb-8">
                <h1 class="text-2xl font-semibold text-stone-800 mb-2" style="font-family: 'Cormorant Garamond', serif; font-size: 2rem">
                    {{ t('Pilih Template Undanganmu', 'Choose Your Invitation Template') }}
                </h1>
                <p class="text-sm text-stone-400">
                    {{ t('Semua template bisa dikustomisasi warna, font, dan isinya.', 'All templates are customizable in color, font, and content.') }}
                    <a href="/register" class="underline hover:text-stone-600 transition-colors">{{ t('Daftar gratis', 'Register free') }}</a>
                    {{ t('untuk mulai membuat undanganmu.', 'to start creating your invitation.') }}
                </p>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex items-center gap-1 bg-stone-100 rounded-xl p-1">
                    <button
                        v-for="cat in allCategories"
                        :key="cat.slug"
                        @click="activeCategory = cat.slug; applyFilters()"
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

                <div class="flex items-center gap-2 sm:ml-auto">
                    <span class="text-xs text-stone-400 font-medium">Tier:</span>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="tier in tiers"
                            :key="tier.value"
                            @click="activeTier = tier.value; applyFilters()"
                            :class="[
                                'px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150 border',
                                activeTier === tier.value
                                    ? 'text-white border-transparent shadow-sm'
                                    : 'bg-white text-stone-500 border-stone-200 hover:border-stone-300',
                            ]"
                            :style="activeTier === tier.value ? 'background-color: #92A89C; border-color: #92A89C' : ''"
                        >
                            {{ tier.label }}
                        </button>
                    </div>
                </div>
            </div>

            <p class="text-xs text-stone-400">
                {{ t('Menampilkan', 'Showing') }}
                <span class="font-semibold text-stone-600">{{ templates.length }}</span>
                template
            </p>

            <!-- Template Grid -->
            <div v-if="templates.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="template in templates"
                    :key="template.id"
                    class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden group hover:shadow-lg hover:-translate-y-1 transition-all duration-200"
                >
                    <div
                        class="relative overflow-hidden"
                        style="height: 200px"
                        :style="`background: linear-gradient(160deg, ${secondaryColor(template)}, ${primaryColor(template)}33)`"
                    >
                        <img
                            v-if="template.thumbnail_url"
                            :src="template.thumbnail_url"
                            :alt="template.name"
                            class="w-full h-full object-cover"
                        />
                        <template v-else>
                            <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full opacity-20"
                                 :style="`background-color: ${primaryColor(template)}`"/>
                            <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full opacity-15"
                                 :style="`background-color: ${accentColor(template)}`"/>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6">
                                <div class="w-10 h-px mb-3" :style="`background-color: ${primaryColor(template)}`"/>
                                <p class="text-xs tracking-widest uppercase font-medium mb-1"
                                   :style="`color: ${primaryColor(template)}; font-family: '${fontTitle(template)}', serif`">
                                    {{ tCat(template) }}
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

                        <span
                            class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-semibold"
                            :style="`background-color: ${tierConfig[template.tier].bg}; color: ${tierConfig[template.tier].color}`"
                        >
                            {{ tTier(template.tier) }}
                        </span>

                        <div class="absolute top-3 right-3 flex gap-1.5">
                            <div class="w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm"
                                 :style="`background-color: ${primaryColor(template)}`"/>
                            <div class="w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm"
                                 :style="`background-color: ${accentColor(template)}`"/>
                        </div>

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
                                {{ t('Gunakan', 'Use') }}
                            </button>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <p class="text-sm font-semibold text-stone-800 leading-tight">{{ tName(template) }}</p>
                            <span class="text-xs text-stone-400 flex-shrink-0">{{ tCat(template) }}</span>
                        </div>
                        <p v-if="template.description" class="text-xs text-stone-400 leading-relaxed line-clamp-2 mb-3">
                            {{ tDesc(template) }}
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
                                {{ t('Gunakan', 'Use') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="py-24 text-center">
                <div class="text-5xl mb-4">🎨</div>
                <p class="text-sm font-medium text-stone-600 mb-1">{{ t('Tidak ada template ditemukan', 'No templates found') }}</p>
                <p class="text-xs text-stone-400">{{ t('Coba ubah filter kategori atau tier.', 'Try changing the category or tier filter.') }}</p>
                <button
                    @click="activeCategory = 'all'; activeTier = 'all'; applyFilters()"
                    class="mt-5 px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background-color: #92A89C"
                >
                    {{ t('Reset Filter', 'Reset Filter') }}
                </button>
            </div>
        </div>

        <!-- Preview Modal -->
        <TemplatePreviewModal
            :is-open="showPreview"
            :template="previewTemplate"
            @close="closePreview"
            @use-template="(id) => { useTemplate(id); closePreview(); }"
        />
    </PublicLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
