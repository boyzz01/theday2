<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    selectedMusic: { type: Object,   required: true }, // ref(null)
    defaultMusic:  { type: Array,    default: () => [] },
    invitationId:  { type: Object,   required: true }, // ref
    uploadAudio:   { type: Function, required: true },
});

const emit = defineEmits(['select']);

const playingId   = ref(null);
const audioEl     = ref(null);
const isUploading = ref(false);
const uploadError = ref(null);

// Default music has no real file_url; we use placeholder IDs
// In production these would be served from storage
const MUSIC_BASE_URL = '/music/'; // adjust to match your storage path

function isSelected(music) {
    return props.selectedMusic.value?.title === music.title;
}

function selectDefault(music) {
    emit('select', { title: music.title, file_url: music.file_url ?? `${MUSIC_BASE_URL}${music.id}.mp3` });
    stopPlay();
}

function togglePlay(music) {
    const src = music.file_url ?? `${MUSIC_BASE_URL}${music.id}.mp3`;
    if (playingId.value === music.id || playingId.value === music.title) {
        stopPlay();
        return;
    }
    stopPlay();
    playingId.value = music.id ?? music.title;
    if (audioEl.value) {
        audioEl.value.src = src;
        audioEl.value.play().catch(() => { playingId.value = null; });
    }
}

function stopPlay() {
    playingId.value = null;
    if (audioEl.value) {
        audioEl.value.pause();
        audioEl.value.currentTime = 0;
    }
}

async function handleAudioUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!props.invitationId.value) {
        uploadError.value = 'Simpan informasi dasar terlebih dahulu.';
        return;
    }
    isUploading.value = true;
    uploadError.value = null;
    try {
        const result = await props.uploadAudio(file);
        emit('select', { title: result.name ?? file.name, file_url: result.url });
    } catch {
        uploadError.value = 'Gagal mengupload file audio.';
    } finally {
        isUploading.value = false;
        e.target.value = '';
    }
}
</script>

<template>
    <div class="p-6 space-y-6">
        <audio ref="audioEl" @ended="playingId = null" class="sr-only"/>

        <div>
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                Musik Latar
            </h2>
            <p class="text-sm text-stone-400 mt-0.5">Pilih musik yang akan diputar saat undangan dibuka</p>
        </div>

        <!-- Selected indicator -->
        <div v-if="selectedMusic.value"
             class="flex items-center gap-3 px-4 py-3 rounded-xl bg-green-50 border border-green-100 text-sm">
            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13"/>
                <circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="2"/>
                <circle cx="18" cy="15" r="3" stroke="currentColor" stroke-width="2"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-green-800 truncate">{{ selectedMusic.value.title }}</p>
                <p class="text-xs text-green-600">Musik terpilih</p>
            </div>
            <button @click="emit('select', null)" class="text-green-400 hover:text-green-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Default music list -->
        <div>
            <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-3">Koleksi Musik</h3>
            <div class="space-y-2">
                <div
                    v-for="music in defaultMusic"
                    :key="music.id"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-xl border transition-all cursor-pointer',
                        isSelected(music)
                            ? 'border-amber-300 bg-amber-50'
                            : 'border-stone-200 hover:border-stone-300 hover:bg-stone-50',
                    ]"
                    @click="selectDefault(music)"
                >
                    <!-- Play button -->
                    <button
                        @click.stop="togglePlay(music)"
                        :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 transition-all',
                            playingId === music.id
                                ? 'text-white'
                                : 'text-stone-400 bg-stone-100 hover:bg-stone-200',
                        ]"
                        :style="playingId === music.id ? 'background-color: #D4A373' : ''"
                    >
                        <!-- Playing animation -->
                        <svg v-if="playingId === music.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="6" y="7" width="4" height="10" rx="1"/>
                            <rect x="14" y="7" width="4" height="10" rx="1"/>
                        </svg>
                        <svg v-else class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>

                    <div class="flex-1 min-w-0">
                        <p :class="['text-sm font-medium truncate', isSelected(music) ? 'text-amber-800' : 'text-stone-700']">
                            {{ music.title }}
                        </p>
                    </div>

                    <!-- Selected checkmark -->
                    <div v-if="isSelected(music)"
                         class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background-color: #D4A373">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload custom music -->
        <div>
            <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-3">Upload Musik Sendiri</h3>
            <label :class="[
                'flex items-center gap-3 px-4 py-3 rounded-xl border border-dashed cursor-pointer transition-all',
                isUploading
                    ? 'border-amber-200 bg-amber-50'
                    : 'border-stone-200 hover:border-stone-300 hover:bg-stone-50',
            ]">
                <div :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
                    isUploading ? 'bg-amber-100' : 'bg-stone-100',
                ]">
                    <svg v-if="isUploading" class="w-4 h-4 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-stone-700">
                        {{ isUploading ? 'Mengunggah audio…' : 'Upload file musik' }}
                    </p>
                    <p class="text-xs text-stone-400">MP3, WAV, OGG · maks 10 MB</p>
                </div>
                <input type="file" accept="audio/*" class="sr-only"
                       :disabled="isUploading || !invitationId.value"
                       @change="handleAudioUpload"/>
            </label>

            <p v-if="uploadError" class="mt-2 text-xs text-red-600">{{ uploadError }}</p>
            <p v-if="!invitationId.value" class="mt-2 text-xs text-amber-600">
                Simpan informasi dasar terlebih dahulu untuk upload musik.
            </p>
        </div>

    </div>
</template>
