<script setup>
defineProps({
    music:        { type: Object, default: null },
    playing:      { type: Boolean, default: false },
    primaryColor: { type: String, default: '#D4A373' },
});

defineEmits(['toggle']);
</script>

<template>
    <!-- This section is purely visual; actual audio is controlled in Show.vue -->
    <section v-if="music" class="py-12 px-6 text-center" :style="{ backgroundColor: primaryColor + '10' }">
        <div class="flex items-center justify-center gap-4 max-w-xs mx-auto">
            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                 :style="{ backgroundColor: primaryColor + '20' }">
                <svg class="w-5 h-5" :style="{ color: primaryColor }" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 19V6l12-3v13"/>
                    <circle cx="6" cy="18" r="3"/>
                    <circle cx="18" cy="15" r="3"/>
                </svg>
            </div>
            <div class="flex-1 text-left">
                <p class="text-xs text-stone-400">Musik Latar</p>
                <p class="text-sm font-medium text-stone-700 truncate">{{ music.title }}</p>
            </div>
            <button
                @click="$emit('toggle')"
                class="w-10 h-10 rounded-full flex items-center justify-center transition-all active:scale-90"
                :style="{ backgroundColor: primaryColor }"
            >
                <svg v-if="playing" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <rect x="6" y="4" width="4" height="16" rx="1"/>
                    <rect x="14" y="4" width="4" height="16" rx="1"/>
                </svg>
                <svg v-else class="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </button>
        </div>
    </section>
</template>
