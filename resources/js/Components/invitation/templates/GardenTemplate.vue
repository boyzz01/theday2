<!-- resources/js/Components/invitation/templates/GardenTemplate.vue -->
<script setup>
import SceneTemplate from './scene/SceneTemplate.vue'
import GardenConfig  from './scene/configs/GardenConfig.js'

defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
})
</script>

<template>
    <SceneTemplate v-bind="$props" :sceneConfig="GardenConfig" />
</template>
