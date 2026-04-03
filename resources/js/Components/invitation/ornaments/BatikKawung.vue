<script setup>
defineProps({
    color:   { type: String, default: 'currentColor' },
    opacity: { type: Number, default: 0.04 },
});
</script>

<template>
    <!--
        Batik Kawung Pattern — repeating 4-petal floral motif.
        Classic Javanese royal textile pattern.
        Used as a subtle background texture at low opacity.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        class="absolute inset-0 w-full h-full pointer-events-none select-none"
        aria-hidden="true"
        :style="{ color, opacity }"
    >
        <defs>
            <pattern id="kawung-tile" x="0" y="0" width="48" height="48" patternUnits="userSpaceOnUse">
                <!-- 4 petals radiating from center (classic kawung motif) -->
                <ellipse cx="24" cy="10" rx="7"  ry="10" stroke="currentColor" stroke-width="0.8" fill="none"/>
                <ellipse cx="24" cy="38" rx="7"  ry="10" stroke="currentColor" stroke-width="0.8" fill="none"/>
                <ellipse cx="10" cy="24" rx="10" ry="7"  stroke="currentColor" stroke-width="0.8" fill="none"/>
                <ellipse cx="38" cy="24" rx="10" ry="7"  stroke="currentColor" stroke-width="0.8" fill="none"/>
                <!-- Center dot -->
                <circle cx="24" cy="24" r="2.5" fill="currentColor"/>
                <!-- Corner connector dots (shared with adjacent tiles) -->
                <circle cx="0"  cy="0"  r="1.5" fill="currentColor"/>
                <circle cx="48" cy="0"  r="1.5" fill="currentColor"/>
                <circle cx="0"  cy="48" r="1.5" fill="currentColor"/>
                <circle cx="48" cy="48" r="1.5" fill="currentColor"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#kawung-tile)"/>
    </svg>
</template>
