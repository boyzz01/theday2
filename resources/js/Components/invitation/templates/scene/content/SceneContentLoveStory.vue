<!-- resources/js/Components/invitation/templates/scene/content/SceneContentLoveStory.vue -->
<script setup>
defineProps({
    stories: { type: Array, default: () => [] },
})
</script>

<template>
    <div>
        <p v-if="!stories.length" class="text-center text-gray-400 py-8">
            Belum ada kisah cinta.
        </p>
        <div v-else class="relative pl-6 space-y-6">
            <!-- Timeline line -->
            <div class="absolute left-2 top-2 bottom-2 w-0.5 bg-gray-200" />

            <div v-for="(story, i) in stories" :key="i" class="relative">
                <!-- Dot -->
                <div class="absolute -left-4 top-1 w-3 h-3 rounded-full bg-pink-400 border-2 border-white shadow" />

                <p class="text-xs text-gray-400 mb-0.5">{{ story.date }}</p>
                <h4 class="font-semibold text-gray-800 text-sm">{{ story.title }}</h4>
                <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{{ story.description }}</p>
            </div>
        </div>
    </div>
</template>
