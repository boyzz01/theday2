<!-- resources/js/Components/invitation/templates/BeachTemplate.vue -->
<script setup>
import SceneTemplate from './scene/SceneTemplate.vue'
import BeachConfig   from './scene/configs/BeachConfig.js'

defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
})
</script>

<template>
    <SceneTemplate v-bind="$props" :sceneConfig="BeachConfig" />
</template>
