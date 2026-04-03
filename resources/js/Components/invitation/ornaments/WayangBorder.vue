<script setup>
defineProps({
    primaryColor: { type: String, default: '#8B6914' },
    lightColor:   { type: String, default: '#C9A84C' },
});
</script>

<template>
    <!--
        Wayang Border — Ornamental rectangular frame.
        Inspired by the decorative borders on wayang kulit and traditional Javanese manuscripts.
        Stretches to fit its container via preserveAspectRatio="none".
    -->
    <svg
        viewBox="0 0 500 360"
        class="absolute inset-0 w-full h-full pointer-events-none select-none"
        aria-hidden="true"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        preserveAspectRatio="none"
    >
        <defs>
            <linearGradient id="wb-top" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%"   :stop-color="primaryColor"/>
                <stop offset="50%"  :stop-color="lightColor"/>
                <stop offset="100%" :stop-color="primaryColor"/>
            </linearGradient>
            <linearGradient id="wb-left" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%"   :stop-color="primaryColor"/>
                <stop offset="50%"  :stop-color="lightColor"/>
                <stop offset="100%" :stop-color="primaryColor"/>
            </linearGradient>
        </defs>

        <!-- Outer frame -->
        <rect x="4"   y="4"   width="492" height="352" stroke="url(#wb-top)" stroke-width="1.5" fill="none"/>
        <!-- Inner frame -->
        <rect x="14"  y="14"  width="472" height="332" stroke="url(#wb-top)" stroke-width="0.5" fill="none" stroke-dasharray="6 4"/>

        <!-- ── Corner ornaments ─────────────────────────────── -->
        <!-- Each corner: L-shaped thick line + concentric circle with dot -->

        <!-- Top-left -->
        <path d="M4,50 Q4,4 50,4"   :stroke="lightColor" stroke-width="4" stroke-linecap="round"/>
        <circle cx="28" cy="28" r="12" :stroke="lightColor" stroke-width="1.5" fill="none"/>
        <circle cx="28" cy="28" r="7"  :stroke="primaryColor" stroke-width="1" fill="none"/>
        <circle cx="28" cy="28" r="3"  :fill="lightColor"/>

        <!-- Top-right -->
        <path d="M450,4 Q496,4 496,50"  :stroke="lightColor" stroke-width="4" stroke-linecap="round"/>
        <circle cx="472" cy="28" r="12" :stroke="lightColor" stroke-width="1.5" fill="none"/>
        <circle cx="472" cy="28" r="7"  :stroke="primaryColor" stroke-width="1" fill="none"/>
        <circle cx="472" cy="28" r="3"  :fill="lightColor"/>

        <!-- Bottom-left -->
        <path d="M4,310 Q4,356 50,356"  :stroke="lightColor" stroke-width="4" stroke-linecap="round"/>
        <circle cx="28" cy="332" r="12" :stroke="lightColor" stroke-width="1.5" fill="none"/>
        <circle cx="28" cy="332" r="7"  :stroke="primaryColor" stroke-width="1" fill="none"/>
        <circle cx="28" cy="332" r="3"  :fill="lightColor"/>

        <!-- Bottom-right -->
        <path d="M450,356 Q496,356 496,310" :stroke="lightColor" stroke-width="4" stroke-linecap="round"/>
        <circle cx="472" cy="332" r="12" :stroke="lightColor" stroke-width="1.5" fill="none"/>
        <circle cx="472" cy="332" r="7"  :stroke="primaryColor" stroke-width="1" fill="none"/>
        <circle cx="472" cy="332" r="3"  :fill="lightColor"/>

        <!-- ── Mid-edge ornaments (diamond at center of each edge) ── -->

        <!-- Top center -->
        <rect x="241" y="-4" width="18" height="18" transform="rotate(45 250 5)" :fill="lightColor"/>
        <rect x="244" y="-1" width="12" height="12" transform="rotate(45 250 5)" :fill="primaryColor" opacity="0.6"/>

        <!-- Bottom center -->
        <rect x="241" y="342" width="18" height="18" transform="rotate(45 250 351)" :fill="lightColor"/>
        <rect x="244" y="345" width="12" height="12" transform="rotate(45 250 351)" :fill="primaryColor" opacity="0.6"/>

        <!-- Left center -->
        <rect x="-4" y="171" width="18" height="18" transform="rotate(45 5 180)" :fill="lightColor"/>
        <rect x="-1" y="174" width="12" height="12" transform="rotate(45 5 180)" :fill="primaryColor" opacity="0.6"/>

        <!-- Right center -->
        <rect x="487" y="171" width="18" height="18" transform="rotate(45 496 180)" :fill="lightColor"/>
        <rect x="490" y="174" width="12" height="12" transform="rotate(45 496 180)" :fill="primaryColor" opacity="0.6"/>
    </svg>
</template>
