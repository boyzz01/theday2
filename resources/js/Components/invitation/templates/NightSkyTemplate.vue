<!-- resources/js/Components/invitation/templates/NightSkyTemplate.vue -->
<script setup>
import SceneTemplate  from './scene/SceneTemplate.vue'
import NightSkyConfig from './scene/configs/NightSkyConfig.js'

defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
})
</script>

<template>
    <SceneTemplate v-bind="$props" :sceneConfig="NightSkyConfig" />
</template>
