<!-- resources/js/Components/invitation/templates/scene/content/SceneContentCouple.vue -->
<script setup>
defineProps({
    groomName: { type: String, default: '' },
    brideName: { type: String, default: '' },
    details:   { type: Object, default: () => ({}) },
})
</script>

<template>
    <div class="couple-wrap">
        <p v-if="details.quote || details.opening_text" class="opening-text">
            {{ details.quote || details.opening_text }}
        </p>

        <!-- Groom -->
        <div class="person-card">
            <div class="person-photo">
                <img
                    v-if="details.groom_photo_url"
                    :src="details.groom_photo_url"
                    :alt="groomName"
                />
                <div v-else class="photo-placeholder">👤</div>
            </div>
            <div class="person-info">
                <span class="person-role">The Groom</span>
                <h3 class="person-name">{{ groomName }}</h3>
                <p v-if="details.groom_parent_names" class="person-parents">
                    Putra dari {{ details.groom_parent_names }}
                </p>
                <a
                    v-if="details.groom_instagram"
                    :href="`https://instagram.com/${details.groom_instagram}`"
                    target="_blank"
                    rel="noopener"
                    class="person-ig"
                >@{{ details.groom_instagram }}</a>
            </div>
        </div>

        <!-- Divider -->
        <div class="couple-divider">&</div>

        <!-- Bride -->
        <div class="person-card">
            <div class="person-photo">
                <img
                    v-if="details.bride_photo_url"
                    :src="details.bride_photo_url"
                    :alt="brideName"
                />
                <div v-else class="photo-placeholder">👤</div>
            </div>
            <div class="person-info">
                <span class="person-role">The Bride</span>
                <h3 class="person-name">{{ brideName }}</h3>
                <p v-if="details.bride_parent_names" class="person-parents">
                    Putri dari {{ details.bride_parent_names }}
                </p>
                <a
                    v-if="details.bride_instagram"
                    :href="`https://instagram.com/${details.bride_instagram}`"
                    target="_blank"
                    rel="noopener"
                    class="person-ig"
                >@{{ details.bride_instagram }}</a>
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

.opening-text {
    font-size:   12px;
    color:       #888;
    font-style:  italic;
    text-align:  center;
    line-height: 1.6;
    padding:     4px 8px 16px;
    white-space: pre-line;
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
