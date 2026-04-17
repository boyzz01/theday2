<script setup>
// Step 1 — Informasi
// Sections: cover (req), konten_utama (req), couple (req), quote (opt)

import { ref } from 'vue';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';
import PremiumUpsellCard from '@/Components/Wizard/PremiumUpsellCard.vue';

const props = defineProps({
    basic:             { type: Object,   required: true },
    details:           { type: Object,   required: true },
    sections:          { type: Object,   required: true }, // sectionMap keyed by section_key
    uploadPhotoField:  { type: Function, required: true },
    deletePhotoField:  { type: Function, required: true },
    onToggleSection:   { type: Function, required: true },
    canUsePremium:     { type: Boolean,  default: false },
});

// Which card is expanded (only one at a time)
const expanded = ref(getFirstIncompleteOrFirst());

function getFirstIncompleteOrFirst() {
    const order = ['cover', 'konten_utama', 'couple', 'quote'];
    for (const key of order) {
        const s = props.sections[key];
        if (s?.is_enabled && s?.completion_status !== 'complete') return key;
    }
    return 'cover';
}

function toggle(key) {
    expanded.value = expanded.value === key ? null : key;
}

function handlePhotoUpload(event, field) {
    const file = event.target.files?.[0];
    if (!file) return;
    props.uploadPhotoField(file, field.replace('_url', ''));
    event.target.value = '';
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Informasi</h2>
            <p class="text-sm text-stone-400 mt-0.5">Detail dasar undangan dan identitas pasangan</p>
        </div>

        <!-- Cover -->
        <SectionAccordionCard
            title="Cover"
            description="Tampilan pertama sebelum undangan dibuka"
            :is-required="sections.cover?.is_required ?? true"
            :is-enabled="sections.cover?.is_enabled ?? true"
            :status="sections.cover?.completion_status ?? 'empty'"
            :expanded="expanded === 'cover'"
            @toggle-expand="toggle('cover')"
            @toggle-enabled="onToggleSection('cover')"
        >
            <div class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Foto Cover <span class="text-xs font-normal text-stone-400">(opsional)</span></label>
                    <div class="flex items-center gap-3 flex-wrap">
                        <div v-if="details.cover_photo_url"
                             class="w-20 h-14 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                            <img :src="details.cover_photo_url" class="w-full h-full object-cover" alt="Cover"/>
                        </div>
                        <label class="flex items-center gap-2 px-4 py-2 rounded-xl border border-stone-200 text-sm font-medium text-stone-600 hover:bg-stone-50 cursor-pointer transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ details.cover_photo_url ? 'Ganti Foto' : 'Upload Foto Cover' }}
                            <input type="file" accept="image/*" class="sr-only"
                                   @change="handlePhotoUpload($event, 'cover_photo_url')"/>
                        </label>
                        <button
                            v-if="details.cover_photo_url"
                            type="button"
                            @click="deletePhotoField('cover_photo')"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-red-100 text-sm font-medium text-red-400 hover:bg-red-50 hover:border-red-200 transition-all"
                            title="Hapus foto cover"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus
                        </button>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Teks Tombol Buka</label>
                    <input
                        v-model="sections.cover.data_json.button_text"
                        type="text"
                        placeholder="Buka Undangan"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                    />
                    <p class="text-xs text-stone-400">Teks pada tombol cover. Default: "Buka Undangan"</p>
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Konten Utama -->
        <SectionAccordionCard
            title="Konten Utama"
            description="Judul dan teks pembuka / penutup undangan"
            :is-required="sections.konten_utama?.is_required ?? true"
            :is-enabled="sections.konten_utama?.is_enabled ?? true"
            :status="sections.konten_utama?.completion_status ?? 'empty'"
            :expanded="expanded === 'konten_utama'"
            @toggle-expand="toggle('konten_utama')"
            @toggle-enabled="onToggleSection('konten_utama')"
        >
            <div class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Judul Undangan <span class="text-red-400">*</span></label>
                    <input
                        v-model="basic.title"
                        type="text"
                        placeholder="Contoh: Pernikahan Budi & Ani"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Kata Pembuka</label>
                    <textarea
                        v-model="details.opening_text"
                        rows="3"
                        placeholder="Dengan memohon rahmat dan ridho Allah SWT…"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition resize-none"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Kata Penutup</label>
                    <textarea
                        v-model="details.closing_text"
                        rows="3"
                        placeholder="Merupakan suatu kehormatan dan kebahagiaan…"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition resize-none"
                    />
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Couple -->
        <SectionAccordionCard
            title="Pasangan"
            description="Data mempelai pria dan wanita"
            :is-required="sections.couple?.is_required ?? true"
            :is-enabled="sections.couple?.is_enabled ?? true"
            :status="sections.couple?.completion_status ?? 'empty'"
            :expanded="expanded === 'couple'"
            @toggle-expand="toggle('couple')"
            @toggle-enabled="onToggleSection('couple')"
        >
            <div class="space-y-6">
                <!-- Mempelai Pria -->
                <div class="space-y-3">
                    <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wide">Mempelai Pria</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">Nama Lengkap</label>
                            <input v-model="details.groom_name" type="text" placeholder="Ahmad Budi"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">Nama Panggilan</label>
                            <input v-model="details.groom_nickname" type="text" placeholder="Budi"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-medium text-stone-600">Nama Orang Tua</label>
                            <input v-model="details.groom_parent_names" type="text" placeholder="Bapak & Ibu Hasan"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <div v-if="details.groom_photo_url"
                             class="w-16 h-16 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                            <img :src="details.groom_photo_url" class="w-full h-full object-cover" alt="Foto pria"/>
                        </div>
                        <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-stone-200 text-sm font-medium text-stone-600 hover:bg-stone-50 cursor-pointer transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ details.groom_photo_url ? 'Ganti Foto' : 'Upload Foto' }}
                            <input type="file" accept="image/*" class="sr-only"
                                   @change="handlePhotoUpload($event, 'groom_photo_url')"/>
                        </label>
                        <button
                            v-if="details.groom_photo_url"
                            type="button"
                            @click="deletePhotoField('groom_photo')"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-red-100 text-sm font-medium text-red-400 hover:bg-red-50 hover:border-red-200 transition-all"
                            title="Hapus foto pria"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus
                        </button>
                    </div>
                </div>

                <!-- Mempelai Wanita -->
                <div class="space-y-3">
                    <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wide">Mempelai Wanita</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">Nama Lengkap</label>
                            <input v-model="details.bride_name" type="text" placeholder="Siti Ani"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-stone-600">Nama Panggilan</label>
                            <input v-model="details.bride_nickname" type="text" placeholder="Ani"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-medium text-stone-600">Nama Orang Tua</label>
                            <input v-model="details.bride_parent_names" type="text" placeholder="Bapak & Ibu Rasyid"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <div v-if="details.bride_photo_url"
                             class="w-16 h-16 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                            <img :src="details.bride_photo_url" class="w-full h-full object-cover" alt="Foto wanita"/>
                        </div>
                        <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-stone-200 text-sm font-medium text-stone-600 hover:bg-stone-50 cursor-pointer transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ details.bride_photo_url ? 'Ganti Foto' : 'Upload Foto' }}
                            <input type="file" accept="image/*" class="sr-only"
                                   @change="handlePhotoUpload($event, 'bride_photo_url')"/>
                        </label>
                        <button
                            v-if="details.bride_photo_url"
                            type="button"
                            @click="deletePhotoField('bride_photo')"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-red-100 text-sm font-medium text-red-400 hover:bg-red-50 hover:border-red-200 transition-all"
                            title="Hapus foto wanita"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Quote — Premium only (Pattern B: visible locked teaser for free users) -->
        <SectionAccordionCard
            v-if="canUsePremium"
            title="Kutipan"
            description="Kata-kata inspiratif atau ayat pilihan (opsional)"
            :is-required="sections.quote?.is_required ?? false"
            :is-enabled="sections.quote?.is_enabled ?? false"
            :status="sections.quote?.completion_status ?? 'disabled'"
            :expanded="expanded === 'quote'"
            @toggle-expand="toggle('quote')"
            @toggle-enabled="onToggleSection('quote')"
        >
            <div class="space-y-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Kutipan</label>
                    <textarea
                        v-model="sections.quote.data_json.text"
                        rows="3"
                        placeholder="Dan di antara tanda-tanda kekuasaan-Nya ialah Dia menciptakan untukmu pasangan hidup dari jenismu sendiri…"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition resize-none"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Sumber</label>
                    <input
                        v-model="sections.quote.data_json.source"
                        type="text"
                        placeholder="QS. Ar-Rum: 21"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                    />
                </div>
            </div>
        </SectionAccordionCard>
        <PremiumUpsellCard
            v-else
            title="Kutipan"
            description="Tampilkan kutipan inspiratif atau ayat pilihan yang mempercantik undangan kalian."
        />

    </div>
</template>
