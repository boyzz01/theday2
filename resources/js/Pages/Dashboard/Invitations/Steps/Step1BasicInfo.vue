<script setup>
const props = defineProps({
    basic:             { type: Object,   required: true },
    details:           { type: Object,   required: true },
    template:          { type: Object,   required: true },
    uploadPhotoField:  { type: Function, required: true },
});

function handlePhotoUpload(event, field) {
    const file = event.target.files?.[0];
    if (!file) return;
    props.uploadPhotoField(file, field.replace('_url', ''));
    event.target.value = '';
}


</script>

<template>
    <div class="p-6 space-y-8">

        <!-- Section title -->
        <div>
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                Informasi Dasar
            </h2>
            <p class="text-sm text-stone-400 mt-0.5">Isi detail utama undangan Anda</p>
        </div>

        <!-- Judul undangan -->
        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-stone-700">
                Judul Undangan <span class="text-red-400">*</span>
            </label>
            <input
                v-model="basic.title"
                type="text"
                placeholder="Contoh: Pernikahan Budi & Ani"
                class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
            />
        </div>

        <!-- ── Detail Mempelai ───────────────────────────────── -->
        <template>

            <!-- Mempelai Pria -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-stone-600 uppercase tracking-wide">Mempelai Pria</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-stone-700">Nama Mempelai Pria</label>
                        <input v-model="details.groom_name" type="text" placeholder="Ahmad Budi"
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-stone-700">Nama Orang Tua Pria</label>
                        <input v-model="details.groom_parent_names" type="text" placeholder="Bapak & Ibu Hasan"
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                    </div>
                </div>

                <!-- Photo pria -->
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Foto Mempelai Pria</label>
                    <div class="flex items-center gap-4">
                        <div v-if="details.groom_photo_url"
                             class="w-20 h-20 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                            <img :src="details.groom_photo_url" class="w-full h-full object-cover" alt="Foto pria"/>
                        </div>
                        <label class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium cursor-pointer transition-all border-stone-200 text-stone-600 hover:border-stone-300 hover:bg-stone-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ details.groom_photo_url ? 'Ganti Foto' : 'Upload Foto' }}
                            <input type="file" accept="image/*" class="sr-only"
                                   @change="handlePhotoUpload($event, 'groom_photo_url')"/>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Mempelai Wanita -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-stone-600 uppercase tracking-wide">Mempelai Wanita</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-stone-700">Nama Mempelai Wanita</label>
                        <input v-model="details.bride_name" type="text" placeholder="Siti Ani"
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-stone-700">Nama Orang Tua Wanita</label>
                        <input v-model="details.bride_parent_names" type="text" placeholder="Bapak & Ibu Rasyid"
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"/>
                    </div>
                </div>

                <!-- Photo wanita -->
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Foto Mempelai Wanita</label>
                    <div class="flex items-center gap-4">
                        <div v-if="details.bride_photo_url"
                             class="w-20 h-20 rounded-xl overflow-hidden border border-stone-200 flex-shrink-0">
                            <img :src="details.bride_photo_url" class="w-full h-full object-cover" alt="Foto wanita"/>
                        </div>
                        <label class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium cursor-pointer transition-all border-stone-200 text-stone-600 hover:border-stone-300 hover:bg-stone-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ details.bride_photo_url ? 'Ganti Foto' : 'Upload Foto' }}
                            <input type="file" accept="image/*" class="sr-only"
                                   @change="handlePhotoUpload($event, 'bride_photo_url')"/>
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <!-- ── Opening & Closing text ─────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-stone-700">Kata Pembuka</label>
                <textarea
                    v-model="details.opening_text"
                    rows="4"
                    placeholder="Dengan memohon rahmat dan ridho Allah SWT…"
                    class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition resize-none"
                />
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-stone-700">Kata Penutup</label>
                <textarea
                    v-model="details.closing_text"
                    rows="4"
                    placeholder="Merupakan suatu kehormatan dan kebahagiaan…"
                    class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition resize-none"
                />
            </div>
        </div>

    </div>
</template>
