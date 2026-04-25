<!-- resources/js/Components/invitation/templates/scene/content/SceneContentEvents.vue -->
<script setup>
defineProps({
    events: { type: Array, default: () => [] },
})
</script>

<template>
    <div class="space-y-4">
        <p v-if="!events.length" class="text-center text-gray-400 py-8">
            Belum ada jadwal acara.
        </p>
        <div
            v-for="event in events"
            :key="event.id"
            class="border border-gray-100 rounded-xl p-4 bg-gray-50"
        >
            <h3 class="font-semibold text-gray-800 text-base mb-2">{{ event.event_name }}</h3>
            <p class="text-sm text-gray-600">📅 {{ event.event_date_formatted }}</p>
            <p v-if="event.start_time" class="text-sm text-gray-600">🕐 {{ event.start_time }}</p>
            <p class="text-sm text-gray-600 mt-1">📍 {{ event.location }}</p>
            <a
                v-if="event.maps_url"
                :href="event.maps_url"
                target="_blank"
                rel="noopener"
                class="inline-block mt-3 text-sm text-blue-500 underline"
            >
                Buka di Maps →
            </a>
        </div>
    </div>
</template>
