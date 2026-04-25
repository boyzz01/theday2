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
        class="hotspot-wrap"
        :class="hotspot.animation ? `anim-${hotspot.animation}` : 'anim-float'"
        :style="{
            position:       'absolute',
            left:           hotspot.x + '%',
            top:            hotspot.y + '%',
            transform:      'translate(-50%, -50%)',
            animationDelay: (index * 0.2) + 's',
            width:          (hotspot.size ?? 72) + 'px',
        }"
        @click="emit('click', hotspot.section)"
    >
        <!-- Mode ilustrasi -->
        <template v-if="hotspot.illustration">
            <img
                :src="hotspot.illustration"
                :alt="hotspot.label"
                class="hotspot-img"
                draggable="false"
            />
            <span class="hotspot-label">{{ hotspot.label }}</span>
        </template>

        <!-- Fallback: text pill (kalau belum ada ilustrasi) -->
        <template v-else>
            <span class="hotspot-pill">{{ hotspot.label }}</span>
        </template>
    </button>
</template>

<style scoped>
.hotspot-wrap {
    background:  none;
    border:      none;
    cursor:      pointer;
    display:     flex;
    flex-direction: column;
    align-items: center;
    gap:         4px;
    padding:     0;
}

/* ── Ilustrasi ── */
.hotspot-img {
    width:      100%;
    height:     auto;
    object-fit: contain;
    filter:     drop-shadow(0 0 6px rgba(100, 220, 255, 0.5));
    transition: filter 0.2s ease, transform 0.2s ease;
    user-select: none;
    -webkit-user-drag: none;
}

.hotspot-wrap:active .hotspot-img {
    filter:    drop-shadow(0 0 12px rgba(100, 220, 255, 0.9));
    transform: scale(0.94);
}

/* ── Label badge di bawah ilustrasi ── */
.hotspot-label {
    background:      rgba(0, 0, 0, 0.45);
    border:          1px solid rgba(255, 255, 255, 0.4);
    border-radius:   999px;
    padding:         2px 9px;
    font-size:       10px;
    font-weight:     600;
    color:           #fff;
    letter-spacing:  0.04em;
    white-space:     nowrap;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* ── Fallback pill (tanpa ilustrasi) ── */
.hotspot-pill {
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
    white-space:      nowrap;
    box-shadow:
        0 0 8px  rgba(255, 255, 255, 0.55),
        0 0 20px rgba(100, 220, 255, 0.4);
}

/* ═══════════════════════════════
   CSS Animations
   ═══════════════════════════════ */

/* Float: naik-turun pelan (default karakter, objek melayang) */
.anim-float {
    animation: anim-float 3.5s ease-in-out infinite;
}
@keyframes anim-float {
    0%, 100% { transform: translate(-50%, -50%) translateY(0px); }
    50%       { transform: translate(-50%, -50%) translateY(-7px); }
}

/* Sway: goyang kiri-kanan (objek menggantung, baju, papan) */
.anim-sway {
    animation: anim-sway 3s ease-in-out infinite;
    transform-origin: center top;
}
@keyframes anim-sway {
    0%, 100% { transform: translate(-50%, -50%) rotate(-4deg); }
    50%       { transform: translate(-50%, -50%) rotate(4deg); }
}

/* Bounce: loncat kecil (mailbox, peti hadiah) */
.anim-bounce {
    animation: anim-bounce 1.8s ease-in-out infinite;
}
@keyframes anim-bounce {
    0%, 100% { transform: translate(-50%, -50%) translateY(0px) scaleY(1); }
    40%       { transform: translate(-50%, -50%) translateY(-9px) scaleY(1.05); }
    60%       { transform: translate(-50%, -50%) translateY(-9px) scaleY(1.05); }
    80%       { transform: translate(-50%, -50%) translateY(0px) scaleY(0.96); }
}

/* Pulse: kembang-kempis (botol, objek penting) */
.anim-pulse {
    animation: anim-pulse 2.5s ease-in-out infinite;
}
@keyframes anim-pulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1); }
    50%       { transform: translate(-50%, -50%) scale(1.08); }
}

/* Spin: rotasi pelan (dress code hanger, ornamen bulat) */
.anim-spin {
    animation: anim-spin 6s linear infinite;
}
@keyframes anim-spin {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to   { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Idle: diam, tanpa animasi */
.anim-idle {
    animation: none;
}
</style>
