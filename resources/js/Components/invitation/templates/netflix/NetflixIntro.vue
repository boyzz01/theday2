<script setup>
import { onMounted } from 'vue'

const emit = defineEmits(['done'])

function playTaDum() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()

        // Note 1: low "ta" (D2 ~73Hz) with quick decay
        const osc1  = ctx.createOscillator()
        const gain1 = ctx.createGain()
        osc1.connect(gain1); gain1.connect(ctx.destination)
        osc1.type = 'sine'
        osc1.frequency.value = 73.42
        gain1.gain.setValueAtTime(0.9, ctx.currentTime)
        gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5)
        osc1.start(ctx.currentTime)
        osc1.stop(ctx.currentTime + 0.5)

        // Note 2: resonant "dum" (B1 ~61Hz), delayed 0.12s
        const osc2  = ctx.createOscillator()
        const gain2 = ctx.createGain()
        osc2.connect(gain2); gain2.connect(ctx.destination)
        osc2.type = 'sine'
        osc2.frequency.value = 61.74
        gain2.gain.setValueAtTime(0, ctx.currentTime + 0.12)
        gain2.gain.linearRampToValueAtTime(1.0, ctx.currentTime + 0.18)
        gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.2)
        osc2.start(ctx.currentTime + 0.12)
        osc2.stop(ctx.currentTime + 1.2)
    } catch {
        // AudioContext blocked — continue silently
    }
}

onMounted(() => {
    playTaDum()
    // Animation is 2s, auto-emit done after 2.3s
    setTimeout(() => emit('done'), 2300)
})
</script>

<template>
    <div class="nfi-root">
        <div class="nfi-n">
            <div class="nfi-bar nfi-bar--left"/>
            <div class="nfi-bar nfi-bar--diag"/>
            <div class="nfi-bar nfi-bar--right"/>
        </div>
    </div>
</template>

<style scoped>
.nfi-root {
    position: fixed; inset: 0; z-index: 50;
    background: #000;
    display: flex; align-items: center; justify-content: center;
}
.nfi-n {
    position: relative; width: 80px; height: 120px;
    animation: nfi-appear 0.3s ease forwards, nfi-fade 0.4s ease 1.9s forwards;
}
.nfi-bar {
    position: absolute;
    background: #E50914;
    border-radius: 3px;
    animation: nfi-grow 0.5s cubic-bezier(0.25,0.46,0.45,0.94) forwards;
}
.nfi-bar--left {
    left: 0; top: 0; width: 14px; height: 0;
    animation-delay: 0.2s;
    animation: nfi-grow-v 0.4s ease 0.2s forwards;
}
.nfi-bar--right {
    right: 0; top: 0; width: 14px; height: 0;
    animation: nfi-grow-v 0.4s ease 0.4s forwards;
}
.nfi-bar--diag {
    left: 14px; top: 0;
    width: 0; height: 14px;
    transform: skewX(-20deg) scaleX(0);
    transform-origin: left center;
    animation: nfi-grow-d 0.3s ease 0.6s forwards;
}
@keyframes nfi-appear {
    from { opacity: 0; transform: scale(0.8); }
    to   { opacity: 1; transform: scale(1); }
}
@keyframes nfi-grow-v {
    from { height: 0; }
    to   { height: 120px; }
}
@keyframes nfi-grow-d {
    from { width: 0; transform: skewX(-20deg) scaleX(0); }
    to   { width: 52px; transform: skewX(-20deg) scaleX(1); }
}
@keyframes nfi-fade {
    from { opacity: 1; }
    to   { opacity: 0; }
}
</style>
