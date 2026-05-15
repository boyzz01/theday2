<script setup>
import TheDayLogo from './TheDayLogo.vue'
defineProps({
    coverUrl:     { type: String,  default: null },
    groomNick:    { type: String,  default: '' },
    brideNick:    { type: String,  default: '' },
    subtitle:     { type: String,  default: 'Sebuah Kisah Cinta' },
    eventDate:    { type: String,  default: '' },
    tags:         { type: Array,   default: () => ['#lovestory', '#romantic'] },
    musicPlaying: { type: Boolean, default: false },
})
const emit = defineEmits(['open', 'toggleMusic'])
</script>

<template>
    <div class="nfc-root">
        <!-- Background -->
        <div
            class="nfc-bg"
            :style="coverUrl
                ? { backgroundImage: `url(${coverUrl})` }
                : { background: '#141414' }"
        />
        <div class="nfc-overlay"/>

        <!-- Top bar -->
        <div class="nfc-top">
            <TheDayLogo class="nfc-brand nfc-stagger" style="--d: 0s" :height="32"/>
            <div class="nfc-top-actions nfc-stagger" style="--d: 0s">
                <button class="nfc-fab" @click.stop="emit('toggleMusic')" aria-label="Toggle musik">
                    {{ musicPlaying ? '🔊' : '🔇' }}
                </button>
            </div>
        </div>

        <!-- Bottom content -->
        <div class="nfc-bottom">
            <div class="nfc-title nfc-stagger" style="--d: 0.1s">{{ groomNick }} &amp; {{ brideNick }}:<br>{{ subtitle }}</div>
            <div class="nfc-meta nfc-stagger" style="--d: 0.25s">
                <span class="nfc-badge">Coming Soon</span>
                <span class="nfc-date">{{ eventDate }}</span>
            </div>
            <div class="nfc-tags nfc-stagger" style="--d: 0.4s">
                <span v-for="tag in tags.slice(0,4)" :key="tag" class="nfc-tag">{{ tag }}</span>
            </div>
            <button class="nfc-play nfc-stagger" style="--d: 0.55s" @click="emit('open')">
                <svg class="nfc-play-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <span>Lihat Cerita</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.nfc-root {
    position: fixed; inset: 0; z-index: 50;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    display: flex; flex-direction: column;
    justify-content: space-between;
}
.nfc-bg {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
}
.nfc-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, #141414 30%, transparent 70%);
}
.nfc-top {
    position: relative; z-index: 1;
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 20px 0;
}
.nfc-brand { display: block; }
.nfc-top-actions { display: flex; gap: 10px; }
.nfc-fab {
    width: 44px; height: 44px;
    background: #E50914; border: none;
    border-radius: 50%; color: #fff;
    font-size: 18px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.nfc-bottom {
    position: relative; z-index: 1;
    padding: 0 20px 48px;
    display: flex; flex-direction: column; gap: 12px;
}
.nfc-title {
    font-size: 28px; font-weight: 700;
    color: #fff; line-height: 1.2;
}
.nfc-meta { display: flex; align-items: center; gap: 12px; }
.nfc-badge {
    background: #E50914; color: #fff;
    padding: 4px 12px; border-radius: 999px;
    font-size: 13px; font-weight: 600;
}
.nfc-date { color: #fff; font-size: 15px; }
.nfc-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.nfc-tag {
    background: rgba(255,255,255,0.12);
    color: #bcbcbc; padding: 4px 12px;
    border-radius: 999px; font-size: 12px;
}
.nfc-play {
    margin-top: 8px;
    align-self: flex-start;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(6px);
    color: #141414;
    border: none; border-radius: 4px;
    padding: 6px 16px 6px 12px;
    display: inline-flex; align-items: center; gap: 6px;
    font-family: inherit;
    font-size: 14px; font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
}
.nfc-play:hover { background: rgba(255,255,255,0.9); }
.nfc-play:active { background: rgba(255,255,255,0.6); }
.nfc-play-icon { width: 18px; height: 18px; }

/* Background ken-burns infinite loop (zoom + pan) */
.nfc-bg {
    animation: nfc-kenburns 10s ease-in-out infinite alternate;
    transform-origin: center center;
}
@keyframes nfc-kenburns {
    0%   { transform: scale(1.05) translate(0, 0); }
    100% { transform: scale(1.25) translate(-4%, -3%); }
}

/* Stagger entrance */
.nfc-stagger {
    opacity: 0;
    transform: translateY(20px);
    animation: nfc-rise 0.7s cubic-bezier(0.16, 1, 0.3, 1) var(--d, 0s) forwards;
}
@keyframes nfc-rise {
    to { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    .nfc-stagger { opacity: 1; transform: none; animation: none; }
    .nfc-bg { animation: none; }
}
</style>
