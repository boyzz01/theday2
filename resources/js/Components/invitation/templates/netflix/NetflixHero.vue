<script setup>
import { computed } from 'vue'

const props = defineProps({
    coverUrl:    { type: String, default: null },
    groomName:   { type: String, default: '' },
    brideName:   { type: String, default: '' },
    subtitle:    { type: String, default: 'Sebuah Kisah Cinta' },
    eventDate:   { type: String, default: '' },
    heroQuote:   { type: String, default: '' },
    openingText: { type: String, default: '' },
    quoteText:   { type: String, default: '' },
})

const year = computed(() => {
    if (!props.eventDate) return new Date().getFullYear()
    return props.eventDate.slice(0, 4) || new Date().getFullYear()
})
</script>

<template>
    <section class="nfh-root">
        <!-- Cinematic photo top half -->
        <div class="nfh-photo-wrap">
            <div
                class="nfh-photo"
                :style="coverUrl ? { backgroundImage: `url(${coverUrl})` } : { background: '#222' }"
            />
            <div class="nfh-photo-overlay"/>
            <p v-if="heroQuote" class="nfh-subtitle-text">{{ heroQuote }}</p>
        </div>

        <!-- Detail below -->
        <div class="nfh-detail">
            <div class="nfh-label">
                <span class="nfh-n">N</span>
                <span class="nfh-genre">DOCUMENTER</span>
            </div>
            <h2 class="nfh-title">{{ groomName }} &amp; {{ brideName }}: {{ subtitle }}</h2>
            <div class="nfh-meta">
                <span class="nfh-match">100% match</span>
                <span class="nfh-badge-pill">SU</span>
                <span class="nfh-muted">{{ year }}</span>
                <span class="nfh-badge-pill">4K</span>
                <span class="nfh-badge-pill">HD</span>
            </div>
            <div v-if="eventDate" class="nfh-coming">Coming soon on {{ eventDate }}</div>
            <p v-if="openingText" class="nfh-synopsis">{{ openingText }}</p>
            <p v-if="quoteText" class="nfh-quote">{{ quoteText }}</p>
        </div>
    </section>
</template>

<style scoped>
.nfh-root { background: #141414; font-family: Arial, Helvetica, sans-serif; }
.nfh-photo-wrap { position: relative; height: 50vh; overflow: hidden; }
.nfh-photo {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    filter: brightness(0.6);
}
.nfh-photo-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, transparent 40%, #141414 100%);
}
.nfh-subtitle-text {
    position: absolute; bottom: 20px; left: 20px; right: 20px;
    color: rgba(255,255,255,0.85); font-size: 14px;
    background: rgba(0,0,0,0.5); padding: 6px 10px;
    border-left: 3px solid #fff; font-style: italic;
    margin: 0;
}
.nfh-detail { padding: 20px 20px 32px; }
.nfh-label { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.nfh-n {
    background: #E50914; color: #fff;
    font-weight: 900; font-size: 14px;
    padding: 2px 6px;
}
.nfh-genre {
    color: #fff; font-size: 12px;
    letter-spacing: 3px; font-weight: 500;
}
.nfh-title {
    color: #fff; font-size: 22px; font-weight: 700;
    margin: 0 0 12px; line-height: 1.3;
}
.nfh-meta {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; margin-bottom: 16px;
}
.nfh-match { color: #46D369; font-weight: 700; font-size: 14px; }
.nfh-muted { color: #bcbcbc; font-size: 13px; }
.nfh-badge-pill {
    border: 1px solid #bcbcbc; color: #bcbcbc;
    font-size: 11px; padding: 2px 6px; border-radius: 3px;
}
.nfh-coming {
    background: #E50914; color: #fff;
    padding: 12px 20px; border-radius: 4px;
    font-weight: 700; font-size: 14px; margin-bottom: 20px;
    text-align: center;
}
.nfh-synopsis {
    color: #fff; font-size: 16px; line-height: 1.6;
    margin: 0 0 16px;
}
.nfh-quote {
    color: #808080; font-size: 14px;
    font-style: italic; line-height: 1.6; margin: 0;
    border-left: 3px solid #333; padding-left: 12px;
}
</style>
