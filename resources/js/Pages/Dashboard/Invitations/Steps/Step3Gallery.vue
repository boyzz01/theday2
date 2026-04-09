<script setup>
import { ref } from 'vue';

const props = defineProps({
    galleries:         { type: Object,   required: true }, // ref([])
    invitationId:      { type: String,   default: null },
    uploadGalleryFile: { type: Function, required: true },
    removeGallery:     { type: Function, required: true },
    moveGallery:       { type: Function, required: true },
});

const isDragOver          = ref(false);
const isUploading         = ref(false);
const uploadError         = ref(null);
const editingCaptionIndex = ref(null);

async function handleFiles(files) {
    if (!props.invitationId) {
        uploadError.value = 'Simpan informasi dasar (Step 1) terlebih dahulu sebelum upload foto.';
        return;
    }
    isUploading.value = true;
    uploadError.value = null;
    try {
        for (const file of Array.from(files)) {
            if (!file.type.startsWith('image/')) continue;
            await props.uploadGalleryFile(file);
        }
    } catch {
        uploadError.value = 'Gagal mengunggah foto. Coba lagi.';
    } finally {
        isUploading.value = false;
    }
}

function onDropZone(e) {
    isDragOver.value = false;
    handleFiles(e.dataTransfer.files);
}

function onFileInput(e) {
    handleFiles(e.target.files);
    e.target.value = '';
}

// Card drag-to-reorder
const dragFrom = ref(null);
function onCardDragStart(i) { dragFrom.value = i; }
function onCardDrop(i) {
    if (dragFrom.value !== null && dragFrom.value !== i) {
        props.moveGallery(dragFrom.value, i);
    }
    dragFrom.value = null;
}
</script>

<template>
    <div class="p-6 space-y-6">

        <div>
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                Galeri Foto
            </h2>
            <p class="text-sm text-stone-400 mt-0.5">Upload dan susun foto untuk galeri undangan</p>
        </div>

        <!-- Drop zone -->
        <label
            :class="[
                'relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed py-10 px-6 cursor-pointer transition-all',
                isDragOver
                    ? 'border-amber-400 bg-amber-50'
                    : 'border-stone-200 bg-stone-50/50 hover:border-stone-300 hover:bg-stone-50',
            ]"
            @dragover.prevent="isDragOver = true"
            @dragleave="isDragOver = false"
            @drop.prevent="onDropZone"
        >
            <div v-if="isUploading" class="flex flex-col items-center gap-2">
                <svg class="w-8 h-8 animate-spin text-amber-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm text-amber-600 font-medium">Mengunggah foto…</span>
            </div>
            <template v-else>
                <div class="w-12 h-12 rounded-xl bg-stone-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <p class="text-sm font-medium text-stone-700">Drag & drop foto di sini</p>
                    <p class="text-xs text-stone-400 mt-0.5">atau klik untuk memilih file (JPG, PNG, WebP · maks 5 MB per foto)</p>
                </div>
                <span class="px-4 py-1.5 rounded-xl text-xs font-semibold text-white" style="background-color: #D4A373">
                    Pilih Foto
                </span>
            </template>
            <input type="file" accept="image/*" multiple class="sr-only"
                   :disabled="isUploading || !invitationId"
                   @change="onFileInput"/>
        </label>

        <!-- Warnings / errors -->
        <p v-if="uploadError" class="text-xs text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-2.5">
            {{ uploadError }}
        </p>
        <p v-else-if="!invitationId" class="text-xs text-amber-600 bg-amber-50 border border-amber-100 rounded-xl px-4 py-2.5">
            Simpan informasi dasar (Step 1) terlebih dahulu untuk dapat upload foto.
        </p>

        <!-- Photo grid -->
        <div v-if="galleries.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div
                v-for="(item, index) in galleries"
                :key="item._key"
                draggable="true"
                @dragstart="onCardDragStart(index)"
                @dragover.prevent
                @drop="onCardDrop(index)"
                class="relative group rounded-xl overflow-hidden border border-stone-200 bg-stone-100 aspect-square cursor-grab active:cursor-grabbing"
            >
                <img :src="item.image_url" class="w-full h-full object-cover" alt="Gallery photo"/>

                <!-- Overlay actions -->
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-between p-2">
                    <!-- Top: move + delete -->
                    <div class="flex justify-between">
                        <div class="flex gap-1">
                            <button v-if="index > 0"
                                    @click="moveGallery(index, index - 1)"
                                    class="w-7 h-7 rounded-lg bg-white/20 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/40 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <button v-if="index < galleries.length - 1"
                                    @click="moveGallery(index, index + 1)"
                                    class="w-7 h-7 rounded-lg bg-white/20 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/40 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                        <button @click="removeGallery(index)"
                                class="w-7 h-7 rounded-lg bg-red-500/80 text-white flex items-center justify-center hover:bg-red-600 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Bottom: caption -->
                    <div>
                        <input
                            v-if="editingCaptionIndex === index"
                            v-model="item.caption"
                            type="text"
                            placeholder="Tulis caption…"
                            @blur="editingCaptionIndex = null"
                            @keyup.enter="editingCaptionIndex = null"
                            class="w-full px-2 py-1 text-xs rounded-lg bg-white/90 text-stone-800 focus:outline-none"
                            autofocus
                        />
                        <button
                            v-else
                            @click="editingCaptionIndex = index"
                            class="w-full text-left px-2 py-1 text-xs rounded-lg bg-black/30 backdrop-blur-sm text-white hover:bg-black/50 transition-colors"
                        >
                            {{ item.caption || '+ Caption' }}
                        </button>
                    </div>
                </div>

                <!-- Sort badge -->
                <div class="absolute top-1.5 left-1.5 w-5 h-5 rounded-full bg-black/50 text-white text-xs flex items-center justify-center font-medium">
                    {{ index + 1 }}
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-6 text-stone-400 text-sm">
            Belum ada foto. Upload foto di atas untuk memulai.
        </div>

    </div>
</template>
