<script setup>
// Step 3 — Media
// Sections: gallery (opt), video (opt), love_story (opt)

import { ref, computed } from 'vue';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';
import PremiumUpsellCard from '@/Components/Wizard/PremiumUpsellCard.vue';

const props = defineProps({
    galleries:          { type: Object,   required: true }, // ref([])
    sections:           { type: Object,   required: true },
    invitationId:       { type: String,   default: null },
    uploadGalleryFile:  { type: Function, required: true },
    removeGallery:      { type: Function, required: true },
    moveGallery:        { type: Function, required: true },
    onToggleSection:    { type: Function, required: true },
    canUsePremium:      { type: Boolean,  default: false },
    maxGalleryPhotos:   { type: Number,   default: 5 },
});

const galleryAtLimit = computed(() => !props.canUsePremium && props.galleries.length >= props.maxGalleryPhotos);

const expanded      = ref(null);
const uploadingGallery = ref(false);

function toggle(key) {
    expanded.value = expanded.value === key ? null : key;
}

async function handleGalleryUpload(event) {
    const files = Array.from(event.target.files ?? []);
    event.target.value = '';
    if (!files.length) return;
    uploadingGallery.value = true;
    try {
        for (const file of files) {
            await props.uploadGalleryFile(file);
        }
    } finally {
        uploadingGallery.value = false;
    }
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Media</h2>
            <p class="text-sm text-stone-400 mt-0.5">Galeri foto, video, dan kisah cinta (semua opsional)</p>
        </div>

        <!-- Gallery (optional) -->
        <SectionAccordionCard
            title="Galeri Foto"
            description="Koleksi foto untuk ditampilkan di undangan"
            :is-required="sections.gallery?.is_required ?? false"
            :is-enabled="sections.gallery?.is_enabled ?? false"
            :status="sections.gallery?.completion_status ?? 'disabled'"
            :expanded="expanded === 'gallery'"
            @toggle-expand="toggle('gallery')"
            @toggle-enabled="onToggleSection('gallery')"
        >
            <div class="space-y-4">
                <!-- Photo grid -->
                <div v-if="galleries.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <div
                        v-for="(item, index) in galleries"
                        :key="item._key"
                        class="relative group aspect-square rounded-xl overflow-hidden border border-stone-200"
                    >
                        <img :src="item.image_url" class="w-full h-full object-cover" :alt="item.caption"/>
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100">
                            <button v-if="index > 0" @click="moveGallery(index, index - 1)"
                                    class="p-1 rounded-lg bg-white/80 text-stone-700">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <button @click="removeGallery(index)"
                                    class="p-1 rounded-lg bg-red-500 text-white">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <button v-if="index < galleries.length - 1" @click="moveGallery(index, index + 1)"
                                    class="p-1 rounded-lg bg-white/80 text-stone-700">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Gallery photo limit indicator for free users -->
                <div v-if="!canUsePremium" class="flex items-center justify-between text-xs text-stone-400">
                    <span>{{ galleries.length }} / {{ maxGalleryPhotos }} foto</span>
                    <a v-if="galleryAtLimit" href="/upgrade"
                       class="font-semibold text-[#73877C] hover:underline">
                        Upgrade untuk lebih
                    </a>
                </div>

                <label v-if="!galleryAtLimit" :class="[
                    'w-full py-4 rounded-xl border-2 border-dashed text-sm font-medium flex items-center justify-center gap-2 cursor-pointer transition-all',
                    uploadingGallery
                        ? 'border-[#B8C7BF] text-[#73877C] bg-[#92A89C]/10'
                        : 'border-stone-200 text-stone-500 hover:border-[#92A89C]/50 hover:text-[#73877C] hover:bg-[#92A89C]/10',
                ]">
                    <svg v-if="uploadingGallery" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ uploadingGallery ? 'Mengunggah…' : 'Upload Foto' }}
                    <input type="file" accept="image/*" multiple class="sr-only"
                           :disabled="uploadingGallery || !invitationId"
                           @change="handleGalleryUpload"/>
                </label>
                <!-- Limit reached state -->
                <div v-else class="w-full py-4 rounded-xl border-2 border-dashed border-[#B8C7BF]/60 bg-[#92A89C]/5 text-sm text-center text-stone-500">
                    Batas {{ maxGalleryPhotos }} foto tercapai.
                    <a href="/upgrade" class="font-semibold text-[#73877C] hover:underline">Upgrade ke Premium</a>
                    untuk foto tidak terbatas.
                </div>
                <p v-if="!invitationId" class="text-xs text-[#92A89C]">Simpan Step 1 terlebih dahulu untuk upload foto.</p>
            </div>
        </SectionAccordionCard>

        <!-- Video — Premium only (Pattern A: hidden for free users) -->
        <SectionAccordionCard
            v-if="canUsePremium"
            title="Video"
            description="Link video YouTube atau Vimeo untuk ditampilkan"
            :is-required="sections.video?.is_required ?? false"
            :is-enabled="sections.video?.is_enabled ?? false"
            :status="sections.video?.completion_status ?? 'disabled'"
            :expanded="expanded === 'video'"
            @toggle-expand="toggle('video')"
            @toggle-enabled="onToggleSection('video')"
        >
            <div class="space-y-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">URL Video</label>
                    <input
                        v-model="sections.video.data_json.url"
                        type="url"
                        placeholder="https://youtube.com/watch?v=..."
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Keterangan</label>
                    <input
                        v-model="sections.video.data_json.caption"
                        type="text"
                        placeholder="Our Story..."
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                    />
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Love Story — Premium only (Pattern A: hidden for free users) -->
        <SectionAccordionCard
            v-if="canUsePremium"
            title="Kisah Cinta"
            description="Ceritakan perjalanan kisah cinta kalian"
            :is-required="sections.love_story?.is_required ?? false"
            :is-enabled="sections.love_story?.is_enabled ?? false"
            :status="sections.love_story?.completion_status ?? 'disabled'"
            :expanded="expanded === 'love_story'"
            @toggle-expand="toggle('love_story')"
            @toggle-enabled="onToggleSection('love_story')"
        >
            <div class="space-y-4">
                <div
                    v-for="(story, index) in sections.love_story.data_json.stories ?? []"
                    :key="index"
                    class="rounded-xl border border-stone-200 p-4 space-y-3 bg-stone-50/50"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-[#73877C] bg-[#92A89C]/10 border border-[#B8C7BF]/50 px-2 py-0.5 rounded-lg">Momen {{ index + 1 }}</span>
                        <button
                            @click="sections.love_story.data_json.stories.splice(index, 1)"
                            class="p-1 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-2">
                        <input v-model="story.year" type="text" placeholder="Tahun (e.g. 2020)"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        <input v-model="story.title" type="text" placeholder="Judul momen"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        <textarea v-model="story.description" rows="2" placeholder="Ceritakan momen ini..."
                                  class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition resize-none bg-white"/>
                    </div>
                </div>

                <button
                    @click="() => {
                        if (!sections.love_story.data_json.stories) sections.love_story.data_json.stories = [];
                        sections.love_story.data_json.stories.push({ year: '', title: '', description: '' });
                    }"
                    class="w-full py-3 rounded-xl border-2 border-dashed border-stone-200 text-sm font-medium text-stone-500 hover:text-[#73877C] hover:border-[#92A89C]/50 hover:bg-[#92A89C]/10 transition-all flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Momen
                </button>
            </div>
        </SectionAccordionCard>

    </div>
</template>
