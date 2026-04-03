<script setup>
defineProps({
    color:   { type: String, default: 'currentColor' },
    opacity: { type: Number, default: 0.07 },
    size:    { type: String, default: '480px' },
});
</script>

<template>
    <!--
        Mandala / Bunga Teratai — Javanese lotus mandala ornament.
        Used as decorative background element in countdown & closing sections.
    -->
    <svg
        :style="{ color, opacity, width: size, height: size }"
        viewBox="0 0 480 480"
        class="pointer-events-none select-none"
        aria-hidden="true"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <!-- Outermost ring -->
        <circle cx="240" cy="240" r="234" stroke="currentColor" stroke-width="0.5"/>
        <circle cx="240" cy="240" r="220" stroke="currentColor" stroke-width="1.5"/>
        <circle cx="240" cy="240" r="205" stroke="currentColor" stroke-width="0.5"/>

        <!-- 16-fold radial lines (outermost band) -->
        <g stroke="currentColor" stroke-width="0.5">
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(0 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(22.5 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(45 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(67.5 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(90 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(112.5 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(135 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(157.5 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(180 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(202.5 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(225 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(247.5 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(270 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(292.5 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(315 240 240)"/>
            <line x1="240" y1="6"   x2="240" y2="36"  transform="rotate(337.5 240 240)"/>
        </g>

        <!-- 8 outer lotus petals -->
        <g fill="currentColor">
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(0 240 240)"/>
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(45 240 240)"/>
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(90 240 240)"/>
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(135 240 240)"/>
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(180 240 240)"/>
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(225 240 240)"/>
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(270 240 240)"/>
            <ellipse cx="240" cy="60" rx="10" ry="22" transform="rotate(315 240 240)"/>
        </g>

        <!-- Middle rings -->
        <circle cx="240" cy="240" r="175" stroke="currentColor" stroke-width="1"/>
        <circle cx="240" cy="240" r="155" stroke="currentColor" stroke-width="0.5"/>
        <circle cx="240" cy="240" r="135" stroke="currentColor" stroke-width="1.5"/>

        <!-- 8-fold radial lines (middle band) -->
        <g stroke="currentColor" stroke-width="0.8">
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(0 240 240)"/>
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(45 240 240)"/>
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(90 240 240)"/>
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(135 240 240)"/>
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(180 240 240)"/>
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(225 240 240)"/>
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(270 240 240)"/>
            <line x1="240" y1="65" x2="240" y2="105" transform="rotate(315 240 240)"/>
        </g>

        <!-- 8 inner lotus petals -->
        <g fill="currentColor">
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(0 240 240)"/>
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(45 240 240)"/>
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(90 240 240)"/>
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(135 240 240)"/>
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(180 240 240)"/>
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(225 240 240)"/>
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(270 240 240)"/>
            <ellipse cx="240" cy="115" rx="8" ry="18" transform="rotate(315 240 240)"/>
        </g>

        <!-- Inner rings -->
        <circle cx="240" cy="240" r="100" stroke="currentColor" stroke-width="1"/>
        <circle cx="240" cy="240" r="78"  stroke="currentColor" stroke-width="0.5"/>
        <circle cx="240" cy="240" r="58"  stroke="currentColor" stroke-width="1.5"/>

        <!-- 4-fold inner diamond petals -->
        <g fill="currentColor">
            <ellipse cx="240" cy="170" rx="7" ry="14" transform="rotate(0 240 240)"/>
            <ellipse cx="240" cy="170" rx="7" ry="14" transform="rotate(90 240 240)"/>
            <ellipse cx="240" cy="170" rx="7" ry="14" transform="rotate(180 240 240)"/>
            <ellipse cx="240" cy="170" rx="7" ry="14" transform="rotate(270 240 240)"/>
        </g>

        <!-- Innermost -->
        <circle cx="240" cy="240" r="36"  stroke="currentColor" stroke-width="1"/>
        <circle cx="240" cy="240" r="18"  fill="currentColor"/>
        <circle cx="240" cy="240" r="12"  fill="none" stroke="currentColor" stroke-width="0.5"/>
        <circle cx="240" cy="240" r="6"   fill="currentColor" opacity="0.6"/>
    </svg>
</template>
