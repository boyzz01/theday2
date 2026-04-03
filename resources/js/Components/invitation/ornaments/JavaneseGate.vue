<script setup>
defineProps({
    primaryColor: { type: String, default: '#8B6914' },
    lightColor:   { type: String, default: '#C9A84C' },
});
</script>

<template>
    <!--
        Gapura Jawa — Decorative Javanese arch frame.
        Inspired by candi/gapura architecture: two pillars + pointed arch.
        Used to frame the names in the cover section.
        Content is rendered in the slot (overlaid on the frame).
    -->
    <div class="gate-wrapper">
        <!-- The decorative SVG frame (absolutely positioned) -->
        <svg
            viewBox="0 0 380 440"
            class="gate-svg"
            aria-hidden="true"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            preserveAspectRatio="xMidYMid meet"
        >
            <defs>
                <linearGradient id="jg-gold-v" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%"   :stop-color="lightColor"/>
                    <stop offset="40%"  :stop-color="primaryColor"/>
                    <stop offset="100%" :stop-color="lightColor"/>
                </linearGradient>
                <linearGradient id="jg-gold-h" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%"   :stop-color="primaryColor"/>
                    <stop offset="50%"  :stop-color="lightColor"/>
                    <stop offset="100%" :stop-color="primaryColor"/>
                </linearGradient>
            </defs>

            <!-- ── Left pillar ──────────────────────────────────── -->
            <rect x="8"   y="120" width="36" height="312" fill="url(#jg-gold-v)" rx="2"/>
            <!-- Pillar inner shadow line -->
            <rect x="16"  y="124" width="4"  height="304" fill="#00000020" rx="1"/>
            <!-- Horizontal molding bands -->
            <rect x="8"   y="160" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="8"   y="210" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="8"   y="270" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="8"   y="330" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="8"   y="390" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>

            <!-- ── Right pillar (mirror) ─────────────────────────── -->
            <rect x="336" y="120" width="36" height="312" fill="url(#jg-gold-v)" rx="2"/>
            <rect x="360" y="124" width="4"  height="304" fill="#00000020" rx="1"/>
            <rect x="336" y="160" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="336" y="210" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="336" y="270" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="336" y="330" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>
            <rect x="336" y="390" width="36" height="5"   fill="url(#jg-gold-h)" opacity="0.9"/>

            <!-- ── Pointed arch (kuncungan style) ──────────────── -->
            <!-- Outer arch stroke -->
            <path
                d="M8,128 Q8,16 190,4 Q372,16 372,128"
                :stroke="lightColor" stroke-width="8" fill="none" stroke-linecap="round"
            />
            <!-- Inner arch stroke -->
            <path
                d="M20,128 Q20,32 190,18 Q360,32 360,128"
                :stroke="primaryColor" stroke-width="3" fill="none" opacity="0.7"
            />

            <!-- ── Top finial (mustaka) ──────────────────────────── -->
            <polygon points="190,0 178,22 202,22" :fill="lightColor"/>
            <rect x="180" y="18" width="20" height="10" :fill="primaryColor" rx="2"/>
            <circle cx="190" cy="38"  r="9" :fill="lightColor"/>
            <circle cx="190" cy="38"  r="5" :fill="primaryColor"/>

            <!-- ── Pillar capitals (ornamental tops) ─────────────── -->
            <!-- Left capital -->
            <rect x="4"   y="110" width="44" height="16" :fill="lightColor"   rx="2"/>
            <rect x="0"   y="120" width="52" height="6"  :fill="primaryColor" rx="1"/>
            <polygon points="4,110 26,88 48,110" :fill="lightColor"/>
            <!-- Left capital lotus -->
            <ellipse cx="26" cy="90" rx="8" ry="5" :fill="primaryColor" opacity="0.8"/>
            <!-- Right capital -->
            <rect x="332" y="110" width="44" height="16" :fill="lightColor"   rx="2"/>
            <rect x="328" y="120" width="52" height="6"  :fill="primaryColor" rx="1"/>
            <polygon points="332,110 354,88 376,110" :fill="lightColor"/>
            <ellipse cx="354" cy="90" rx="8" ry="5" :fill="primaryColor" opacity="0.8"/>

            <!-- ── Base platform ────────────────────────────────── -->
            <rect x="0"   y="428" width="380" height="12" :fill="lightColor"   rx="2"/>
            <rect x="4"   y="420" width="372" height="10" :fill="primaryColor" rx="1"/>
            <!-- Base lotus corner ornaments -->
            <circle cx="26"  cy="422" r="9"  :stroke="lightColor" stroke-width="1.5" fill="none"/>
            <circle cx="26"  cy="422" r="5"  :fill="primaryColor"/>
            <circle cx="354" cy="422" r="9"  :stroke="lightColor" stroke-width="1.5" fill="none"/>
            <circle cx="354" cy="422" r="5"  :fill="primaryColor"/>
            <!-- Center base ornament -->
            <rect x="183" y="416" width="14" height="14"
                  transform="rotate(45 190 423)" :fill="lightColor"/>
        </svg>

        <!-- Content slot (names, date, etc.) -->
        <div class="gate-content">
            <slot/>
        </div>
    </div>
</template>

<style scoped>
.gate-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 380px;
    margin: 0 auto;
}

.gate-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    user-select: none;
}

.gate-content {
    position: relative;
    z-index: 1;
    width: 100%;
    padding: 60px 52px 56px;
    text-align: center;
}
</style>
