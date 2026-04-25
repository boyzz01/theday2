<!-- resources/js/Components/invitation/templates/scene/SceneHotspot.vue -->
<script setup>
const props = defineProps({
    hotspot: { type: Object, required: true },
    index:   { type: Number, default: 0 },
})
const emit = defineEmits(['click'])
</script>

<template>
    <button
        class="hotspot-btn"
        :style="{
            position:       'absolute',
            left:           props.hotspot.x + '%',
            top:            props.hotspot.y + '%',
            transform:      'translate(-50%, -50%)',
            animationDelay: (props.index * 0.12) + 's',
        }"
        @click="emit('click', props.hotspot.section)"
    >
        {{ props.hotspot.label }}
    </button>
</template>

<style scoped>
.hotspot-btn {
    background:       rgba(255, 255, 255, 0.15);
    border:           1.5px solid rgba(255, 255, 255, 0.65);
    border-radius:    999px;
    padding:          4px 12px;
    font-size:        11px;
    font-weight:      600;
    color:            #fff;
    letter-spacing:   0.04em;
    backdrop-filter:  blur(4px);
    -webkit-backdrop-filter: blur(4px);
    cursor:           pointer;
    white-space:      nowrap;
    box-shadow:
        0 0 8px  rgba(255, 255, 255, 0.55),
        0 0 20px rgba(100, 220, 255, 0.4);
    animation: pulse-glow 2.2s ease-in-out infinite;
}

.hotspot-btn:active {
    transform: translate(-50%, -50%) scale(0.95);
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow:
            0 0 8px  rgba(255, 255, 255, 0.55),
            0 0 20px rgba(100, 220, 255, 0.40);
    }
    50% {
        box-shadow:
            0 0 14px rgba(255, 255, 255, 0.90),
            0 0 32px rgba(100, 220, 255, 0.75);
    }
}
</style>
