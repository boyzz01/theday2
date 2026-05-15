<script setup>
defineProps({
    height: { type: [Number, String], default: 60 },
    muted:  { type: Boolean, default: false },
})
</script>

<template>
    <img
        src="/images/templates/netflix/logo.png"
        :style="{ height: typeof height === 'number' ? `${height}px` : height }"
        alt="THEDAY"
        class="theday-logo"
        :class="{ 'theday-logo--muted': muted }"
    />
</template>

<style scoped>
.theday-logo {
    display: block;
    width: auto;
    object-fit: contain;
}
.theday-logo--muted {
    opacity: 0.35;
}
</style>
