<!-- resources/js/Components/invitation/templates/scene/content/SceneContentGallery.vue -->
<script setup>
defineProps({
    galleries: { type: Array, default: () => [] },
})
</script>

<template>
    <div>
        <p v-if="!galleries.length" class="text-center text-gray-400 py-8">
            Belum ada foto.
        </p>
        <div v-else class="grid grid-cols-2 gap-2">
            <div v-for="img in galleries" :key="img.id" class="rounded-lg overflow-hidden">
                <img
                    :src="img.image_url ?? img.file_url"
                    :alt="img.caption ?? ''"
                    loading="lazy"
                    class="w-full h-40 object-cover"
                />
                <p v-if="img.caption" class="text-xs text-gray-500 text-center py-1 px-1">
                    {{ img.caption }}
                </p>
            </div>
        </div>
    </div>
</template>
