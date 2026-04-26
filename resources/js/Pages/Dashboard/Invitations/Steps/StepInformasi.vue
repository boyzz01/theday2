<script setup>
// Step 1 — Informasi
// Section: couple

import { ref } from 'vue';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';

const props = defineProps({
    basic:             { type: Object,   required: true },
    details:           { type: Object,   required: true },
    sections:          { type: Object,   required: true },
    errors:            { type: Object,   default: () => ({}) },
    uploadPhotoField:  { type: Function, required: true },
    deletePhotoField:  { type: Function, required: true },
    onToggleSection:   { type: Function, required: true },
    canUsePremium:     { type: Boolean,  default: false },
});

const expanded = ref(new Set(['couple']));

function toggle(key) {
    const s = new Set(expanded.value);
    if (s.has(key)) s.delete(key); else s.add(key);
    expanded.value = s;
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

        <!-- Couple -->
        <SectionAccordionCard
            title="Pasangan"
            description="Data mempelai pria dan wanita"
            :is-required="true"
            :is-enabled="true"
            :status="sections.couple?.completion_status ?? 'empty'"
            :expanded="expanded.has('couple')"
            @toggle-expand="toggle('couple')"
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
                                   :class="['w-full px-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:border-transparent transition',
                                            errors.groom_nickname ? 'border-red-300 focus:ring-red-200' : 'border-stone-200 focus:ring-[#92A89C]/50']"
                                   @input="delete errors.groom_nickname"/>
                            <p v-if="errors.groom_nickname" class="text-xs text-red-500">{{ errors.groom_nickname }}</p>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-medium text-stone-600">Instagram <span class="font-normal text-stone-400">(opsional)</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-stone-400">@</span>
                                <input v-model="details.groom_instagram" type="text" placeholder="username"
                                       :class="['w-full pl-7 pr-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:border-transparent transition',
                                                errors.groom_instagram ? 'border-red-300 focus:ring-red-200' : 'border-stone-200 focus:ring-[#92A89C]/50']"
                                       @input="delete errors.groom_instagram"/>
                            </div>
                            <p v-if="errors.groom_instagram" class="text-xs text-red-500">{{ errors.groom_instagram }}</p>
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
                                   :class="['w-full px-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:border-transparent transition',
                                            errors.bride_nickname ? 'border-red-300 focus:ring-red-200' : 'border-stone-200 focus:ring-[#92A89C]/50']"
                                   @input="delete errors.bride_nickname"/>
                            <p v-if="errors.bride_nickname" class="text-xs text-red-500">{{ errors.bride_nickname }}</p>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-medium text-stone-600">Instagram <span class="font-normal text-stone-400">(opsional)</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-stone-400">@</span>
                                <input v-model="details.bride_instagram" type="text" placeholder="username"
                                       :class="['w-full pl-7 pr-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:border-transparent transition',
                                                errors.bride_instagram ? 'border-red-300 focus:ring-red-200' : 'border-stone-200 focus:ring-[#92A89C]/50']"
                                       @input="delete errors.bride_instagram"/>
                            </div>
                            <p v-if="errors.bride_instagram" class="text-xs text-red-500">{{ errors.bride_instagram }}</p>
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

    </div>
</template>
