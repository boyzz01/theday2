<script setup>
import { ref } from 'vue';
import { useReveal } from '@/Composables/useReveal.js';

defineProps({
    color: { type: String, default: 'currentColor' },
    light: { type: Boolean, default: false },  // true = lighter variant for dark bg
});

const el = ref(null);
const { visible } = useReveal(el, { threshold: 0.2 });
</script>

<template>
    <!--
        Sulur Lung-lungan — Javanese flowing vine/tendril ornament.
        Grows from the center outward via stroke-dasharray animation.
        Used as a section divider.
    -->
    <div ref="el" class="sulur-root" :class="{ visible }">
        <svg
            viewBox="0 0 480 64"
            class="sulur-svg"
            :style="{ color }"
            aria-hidden="true"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <!-- Center diamond jewel -->
            <rect x="230" y="24" width="20" height="20"
                  transform="rotate(45 240 34)"
                  :fill="color" class="sulur-jewel"/>
            <rect x="234" y="28" width="12" height="12"
                  transform="rotate(45 240 34)"
                  fill="currentColor" opacity="0.3" class="sulur-jewel"/>

            <!-- ── Left stem ──────────────────────────────────────── -->
            <path
                d="M229,34 C210,34 195,18 172,28 C150,38 130,20 108,30 C88,40 68,25 45,32"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                class="sulur-path sulur-left"
            />
            <!-- Left tendrils upper -->
            <path d="M185,26 Q178,12 170,22"
                  stroke="currentColor" stroke-width="1" stroke-linecap="round" class="sulur-curl"/>
            <path d="M135,24 Q128,10 122,20"
                  stroke="currentColor" stroke-width="1" stroke-linecap="round" class="sulur-curl"/>
            <path d="M85,28 Q80,14 74,24"
                  stroke="currentColor" stroke-width="1" stroke-linecap="round" class="sulur-curl"/>
            <!-- Left leaves -->
            <ellipse cx="172" cy="22" rx="10" ry="5"  transform="rotate(-25 172 22)" stroke="currentColor" stroke-width="0.9" class="sulur-leaf"/>
            <ellipse cx="125" cy="38" rx="9"  ry="4.5" transform="rotate(22 125 38)"  stroke="currentColor" stroke-width="0.9" class="sulur-leaf"/>
            <ellipse cx="78"  cy="26" rx="8"  ry="4"  transform="rotate(-18 78 26)"  stroke="currentColor" stroke-width="0.9" class="sulur-leaf"/>

            <!-- ── Right stem (mirror) ─────────────────────────────── -->
            <path
                d="M251,34 C270,34 285,18 308,28 C330,38 350,20 372,30 C392,40 412,25 435,32"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                class="sulur-path sulur-right"
            />
            <!-- Right tendrils upper -->
            <path d="M295,26 Q302,12 310,22"
                  stroke="currentColor" stroke-width="1" stroke-linecap="round" class="sulur-curl"/>
            <path d="M345,24 Q352,10 358,20"
                  stroke="currentColor" stroke-width="1" stroke-linecap="round" class="sulur-curl"/>
            <path d="M395,28 Q400,14 406,24"
                  stroke="currentColor" stroke-width="1" stroke-linecap="round" class="sulur-curl"/>
            <!-- Right leaves (mirror) -->
            <ellipse cx="308" cy="22" rx="10" ry="5"  transform="rotate(25 308 22)"   stroke="currentColor" stroke-width="0.9" class="sulur-leaf"/>
            <ellipse cx="355" cy="38" rx="9"  ry="4.5" transform="rotate(-22 355 38)" stroke="currentColor" stroke-width="0.9" class="sulur-leaf"/>
            <ellipse cx="402" cy="26" rx="8"  ry="4"  transform="rotate(18 402 26)"   stroke="currentColor" stroke-width="0.9" class="sulur-leaf"/>
        </svg>
    </div>
</template>

<style scoped>
.sulur-root {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.sulur-svg {
    width: 100%;
    max-width: 360px;
    height: auto;
}

/* Stem grow animation */
.sulur-path {
    stroke-dasharray: 500;
    stroke-dashoffset: 500;
    transition: stroke-dashoffset 1.8s cubic-bezier(0.22, 1, 0.36, 1);
}
.sulur-left  { transition-delay: 0s;   }
.sulur-right { transition-delay: 0.1s; }

/* Leaf/curl/jewel fade in */
.sulur-leaf,
.sulur-curl,
.sulur-jewel {
    opacity: 0;
    transition: opacity 0.6s ease;
    transition-delay: 1s;
}

.visible .sulur-path  { stroke-dashoffset: 0; }
.visible .sulur-leaf,
.visible .sulur-curl,
.visible .sulur-jewel { opacity: 1; }
</style>
