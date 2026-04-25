<!-- resources/js/Components/invitation/templates/scene/content/SceneContentCouple.vue -->
<script setup>
defineProps({
    groomName: { type: String, default: '' },
    brideName: { type: String, default: '' },
    details:   { type: Object, default: () => ({}) },
})
</script>

<template>
    <div class="text-center space-y-6 pb-4">
        <p v-if="details.opening_text" class="text-sm text-gray-500 italic px-2">
            {{ details.opening_text }}
        </p>

        <div class="space-y-4">
            <!-- Groom -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h3 class="font-semibold text-lg text-gray-800">{{ groomName }}</h3>
                <p v-if="details.groom_nickname" class="text-sm text-gray-500">
                    "{{ details.groom_nickname }}"
                </p>
                <p v-if="details.groom_parents" class="text-sm text-gray-600 mt-1">
                    Putra dari {{ details.groom_parents }}
                </p>
            </div>

            <div class="text-2xl text-gray-300">&</div>

            <!-- Bride -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h3 class="font-semibold text-lg text-gray-800">{{ brideName }}</h3>
                <p v-if="details.bride_nickname" class="text-sm text-gray-500">
                    "{{ details.bride_nickname }}"
                </p>
                <p v-if="details.bride_parents" class="text-sm text-gray-600 mt-1">
                    Putri dari {{ details.bride_parents }}
                </p>
            </div>
        </div>
    </div>
</template>
