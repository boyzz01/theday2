<!-- resources/js/Components/invitation/templates/scene/content/SceneContentCouple.vue -->
<script setup>
const DEFAULT_QUOTE = 'Dua insan yang dipertemukan, kini melangkah bersama menuju kebahagiaan.'

const props = defineProps({
    groomName:   { type: String, default: '' },
    brideName:   { type: String, default: '' },
    details:     { type: Object, default: () => ({}) },
    quote:       { type: Object, default: () => ({}) },
    coupleOrder: { type: String, default: 'groom_first' },
})
</script>

<template>
    <div class="couple-wrap">
        <div class="opening-quote">
            <p class="opening-text">{{ quote?.text?.trim() || DEFAULT_QUOTE }}</p>
            <p v-if="quote?.source?.trim()" class="opening-source">{{ quote.source }}</p>
        </div>

        <!-- Person A (groom_first → groom, bride_first → bride) -->
        <div class="person-card">
            <div class="person-photo">
                <img
                    v-if="coupleOrder === 'bride_first' ? details.bride_photo_url : details.groom_photo_url"
                    :src="coupleOrder === 'bride_first' ? details.bride_photo_url : details.groom_photo_url"
                    :alt="coupleOrder === 'bride_first' ? brideName : groomName"
                />
                <div v-else class="photo-placeholder">👤</div>
            </div>
            <div class="person-info">
                <span class="person-role">{{ coupleOrder === 'bride_first' ? 'The Bride' : 'The Groom' }}</span>
                <h3 class="person-name">{{ coupleOrder === 'bride_first' ? brideName : groomName }}</h3>
                <p v-if="coupleOrder === 'bride_first' ? details.bride_parent_names : details.groom_parent_names" class="person-parents">
                    {{ coupleOrder === 'bride_first' ? 'Putri' : 'Putra' }} dari
                    {{ coupleOrder === 'bride_first' ? details.bride_parent_names : details.groom_parent_names }}
                </p>
                <a
                    v-if="coupleOrder === 'bride_first' ? details.bride_instagram : details.groom_instagram"
                    :href="`https://instagram.com/${coupleOrder === 'bride_first' ? details.bride_instagram : details.groom_instagram}`"
                    target="_blank" rel="noopener" class="person-ig"
                >@{{ coupleOrder === 'bride_first' ? details.bride_instagram : details.groom_instagram }}</a>
            </div>
        </div>

        <!-- Divider -->
        <div class="couple-divider">&</div>

        <!-- Person B -->
        <div class="person-card">
            <div class="person-photo">
                <img
                    v-if="coupleOrder === 'bride_first' ? details.groom_photo_url : details.bride_photo_url"
                    :src="coupleOrder === 'bride_first' ? details.groom_photo_url : details.bride_photo_url"
                    :alt="coupleOrder === 'bride_first' ? groomName : brideName"
                />
                <div v-else class="photo-placeholder">👤</div>
            </div>
            <div class="person-info">
                <span class="person-role">{{ coupleOrder === 'bride_first' ? 'The Groom' : 'The Bride' }}</span>
                <h3 class="person-name">{{ coupleOrder === 'bride_first' ? groomName : brideName }}</h3>
                <p v-if="coupleOrder === 'bride_first' ? details.groom_parent_names : details.bride_parent_names" class="person-parents">
                    {{ coupleOrder === 'bride_first' ? 'Putra' : 'Putri' }} dari
                    {{ coupleOrder === 'bride_first' ? details.groom_parent_names : details.bride_parent_names }}
                </p>
                <a
                    v-if="coupleOrder === 'bride_first' ? details.groom_instagram : details.bride_instagram"
                    :href="`https://instagram.com/${coupleOrder === 'bride_first' ? details.groom_instagram : details.bride_instagram}`"
                    target="_blank" rel="noopener" class="person-ig"
                >@{{ coupleOrder === 'bride_first' ? details.groom_instagram : details.bride_instagram }}</a>
            </div>
        </div>

        <p v-if="details.closing_text" class="closing-text">
            {{ details.closing_text }}
        </p>
    </div>
</template>

<style scoped>
.couple-wrap {
    padding-bottom: 16px;
}

.opening-quote {
    padding:     4px 8px 16px;
    text-align:  center;
}

.opening-text {
    font-size:   12px;
    color:       #888;
    font-style:  italic;
    line-height: 1.6;
    white-space: pre-line;
    margin:      0;
}

.opening-source {
    font-size:   11px;
    color:       #aaa;
    margin-top:  6px;
}

/* ── Person card ── */
.person-card {
    border-radius: 16px;
    overflow:      hidden;
    margin-bottom: 8px;
    background:    rgba(255,255,255,0.5);
}

.person-photo {
    width:    100%;
    aspect-ratio: 4/3;
    overflow: hidden;
    background: #e8e0d0;
}

.person-photo img {
    width:      100%;
    height:     100%;
    object-fit: cover;
    object-position: top center;
    display:    block;
}

.photo-placeholder {
    width:           100%;
    height:          100%;
    display:         flex;
    align-items:     center;
    justify-content: center;
    font-size:       48px;
    background:      #f0ebe3;
}

.person-info {
    padding:    14px 16px;
    text-align: center;
}

.person-role {
    font-size:      10px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color:          #aaa;
    display:        block;
    margin-bottom:  4px;
}

.person-name {
    font-size:   20px;
    font-weight: 700;
    color:       #2d1a0e;
    margin:      0 0 6px;
    line-height: 1.2;
}

.person-parents {
    font-size:   12px;
    color:       #888;
    line-height: 1.5;
    margin:      0 0 10px;
}

.person-ig {
    display:         inline-block;
    padding:         5px 14px;
    border-radius:   999px;
    background:      rgba(100, 80, 200, 0.08);
    border:          1px solid rgba(100, 80, 200, 0.2);
    font-size:       12px;
    color:           #7c60d0;
    text-decoration: none;
    transition:      background 0.2s ease;
}
.person-ig:hover { background: rgba(100, 80, 200, 0.15); }

/* ── Divider ── */
.couple-divider {
    text-align:  center;
    font-size:   28px;
    color:       #ccc;
    padding:     4px 0 12px;
    line-height: 1;
}

.closing-text {
    font-size:   11px;
    color:       #999;
    font-style:  italic;
    text-align:  center;
    line-height: 1.6;
    padding:     16px 8px 0;
}
</style>
