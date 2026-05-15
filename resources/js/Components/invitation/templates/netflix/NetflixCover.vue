<script setup>
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
                : { background: '#1a1a1a' }"
        />
        <div class="nfc-overlay"/>

        <!-- Top bar -->
        <div class="nfc-top">
            <span class="nfc-brand">THEDAY</span>
            <div class="nfc-top-actions">
                <button class="nfc-fab" @click.stop="emit('toggleMusic')" aria-label="Toggle musik">
                    {{ musicPlaying ? '🔊' : '🔇' }}
                </button>
            </div>
        </div>

        <!-- Bottom content -->
        <div class="nfc-bottom">
            <div class="nfc-title">{{ groomNick }} &amp; {{ brideNick }}:<br>{{ subtitle }}</div>
            <div class="nfc-meta">
                <span class="nfc-badge">Coming Soon</span>
                <span class="nfc-date">{{ eventDate }}</span>
            </div>
            <div class="nfc-tags">
                <span v-for="tag in tags.slice(0,4)" :key="tag" class="nfc-tag">{{ tag }}</span>
            </div>
            <button class="nfc-play" @click="emit('open')">
                ▶ &nbsp;Buka Undangan
            </button>
        </div>
    </div>
</template>

<style scoped>
.nfc-root {
    position: fixed; inset: 0; z-index: 50;
    font-family: Arial, Helvetica, sans-serif;
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
.nfc-brand {
    font-size: 28px; font-weight: 900;
    color: #E50914; letter-spacing: -1px;
}
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
    background: transparent; border: none;
    color: #fff; font-size: 22px; font-weight: 700;
    cursor: pointer; text-align: left; padding: 0;
    letter-spacing: 1px;
}
.nfc-play:active { opacity: 0.7; }
</style>
