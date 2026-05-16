<!-- resources/js/Components/invitation/templates/scene/content/SceneContentGallery.vue -->
<script setup>
defineProps({
    galleries: { type: Array,  default: () => [] },
    layout:    { type: String, default: 'polaroid' },
})

const rotations = [-6, 4, -3, 7, -5, 3, -7, 5, -2, 6]
const getRotation = (i) => rotations[i % rotations.length]
</script>

<template>
    <div class="gallery-wrap">
        <p v-if="!galleries.length" class="empty-msg">Belum ada foto.</p>

        <!-- ── Polaroid scattered ── -->
        <div v-else-if="layout === 'polaroid'" class="polaroid-grid">
            <div
                v-for="(img, i) in galleries"
                :key="img.id"
                class="polaroid"
                :style="{ '--rot': getRotation(i) + 'deg', animationDelay: (i * 0.08) + 's' }"
            >
                <div class="polaroid-photo">
                    <img :src="img.image_url ?? img.file_url" :alt="img.caption ?? ''" loading="lazy" />
                </div>
                <p class="polaroid-caption">{{ img.caption ?? '✦' }}</p>
            </div>
        </div>

        <!-- ── Masonry ── -->
        <div v-else-if="layout === 'masonry'" class="masonry-grid">
            <div
                v-for="(img, i) in galleries"
                :key="img.id"
                class="masonry-item"
                :class="i % 3 === 0 ? 'tall' : ''"
                :style="{ animationDelay: (i * 0.07) + 's' }"
            >
                <img :src="img.image_url ?? img.file_url" :alt="img.caption ?? ''" loading="lazy" />
                <div v-if="img.caption" class="masonry-caption">{{ img.caption }}</div>
            </div>
        </div>

        <!-- ── Grid seragam ── -->
        <div v-else-if="layout === 'grid'" class="uniform-grid">
            <div
                v-for="(img, i) in galleries"
                :key="img.id"
                class="grid-item"
                :style="{ animationDelay: (i * 0.06) + 's' }"
            >
                <img :src="img.image_url ?? img.file_url" :alt="img.caption ?? ''" loading="lazy" />
                <div v-if="img.caption" class="grid-caption">{{ img.caption }}</div>
            </div>
        </div>

        <!-- ── Horizontal scroll ── -->
        <div v-else-if="layout === 'scroll'" class="scroll-track">
            <div
                v-for="(img, i) in galleries"
                :key="img.id"
                class="scroll-card"
                :style="{ animationDelay: (i * 0.08) + 's' }"
            >
                <img :src="img.image_url ?? img.file_url" :alt="img.caption ?? ''" loading="lazy" />
                <p v-if="img.caption" class="scroll-caption">{{ img.caption }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gallery-wrap { padding: 8px 4px 16px; }

.empty-msg {
    text-align: center;
    color:      rgba(150, 120, 80, 0.6);
    padding:    32px 0;
    font-size:  13px;
    font-style: italic;
}

/* ── Polaroid ── */
.polaroid-grid {
    display:               grid;
    grid-template-columns: repeat(2, 1fr);
    gap:                   20px 12px;
    padding:               12px 8px;
}
.polaroid {
    background:    #fffef8;
    padding:       8px 8px 28px;
    box-shadow:    0 2px 6px rgba(0,0,0,0.15), 0 6px 20px rgba(0,0,0,0.1);
    transform:     rotate(var(--rot));
    transition:    transform 0.25s ease, box-shadow 0.25s ease;
    animation:     polaroid-appear 0.4s ease both;
    border-radius: 2px;
}
.polaroid:hover {
    transform:  rotate(0deg) scale(1.05);
    box-shadow: 0 8px 24px rgba(0,0,0,0.22);
    position:   relative;
    z-index:    2;
}
.polaroid-photo { width: 100%; aspect-ratio: 1; overflow: hidden; background: #e8e0d0; }
.polaroid-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.polaroid-caption {
    text-align:     center;
    font-size:      10px;
    color:          #8a7060;
    margin-top:     6px;
    font-style:     italic;
    letter-spacing: 0.04em;
    white-space:    nowrap;
    overflow:       hidden;
    text-overflow:  ellipsis;
}
@keyframes polaroid-appear {
    from { opacity: 0; transform: rotate(var(--rot)) translateY(16px) scale(0.9); }
    to   { opacity: 1; transform: rotate(var(--rot)) translateY(0) scale(1); }
}

/* ── Masonry ── */
.masonry-grid {
    columns:    2;
    gap:        8px;
    padding:    4px;
}
.masonry-item {
    break-inside:  avoid;
    margin-bottom: 8px;
    border-radius: 10px;
    overflow:      hidden;
    position:      relative;
    animation:     fade-up 0.4s ease both;
}
.masonry-item img { width: 100%; display: block; object-fit: cover; }
.masonry-item.tall img { aspect-ratio: 3/4; }
.masonry-item:not(.tall) img { aspect-ratio: 1; }
.masonry-caption {
    position:   absolute;
    bottom:     0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.5));
    color:      #fff;
    font-size:  10px;
    padding:    12px 8px 6px;
    text-align: center;
}

/* ── Grid seragam ── */
.uniform-grid {
    display:               grid;
    grid-template-columns: repeat(2, 1fr);
    gap:                   6px;
    padding:               4px;
}
.grid-item {
    border-radius: 10px;
    overflow:      hidden;
    position:      relative;
    aspect-ratio:  1;
    animation:     fade-up 0.35s ease both;
}
.grid-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.grid-caption {
    position:   absolute;
    bottom:     0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.5));
    color:      #fff;
    font-size:  10px;
    padding:    12px 8px 6px;
    text-align: center;
}

/* ── Horizontal scroll ── */
.scroll-track {
    display:    flex;
    gap:        12px;
    overflow-x: auto;
    padding:    8px 4px 16px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
}
.scroll-track::-webkit-scrollbar { height: 3px; }
.scroll-track::-webkit-scrollbar-thumb { background: rgba(150,120,80,0.3); border-radius: 99px; }
.scroll-card {
    flex:          0 0 72%;
    scroll-snap-align: center;
    border-radius: 14px;
    overflow:      hidden;
    position:      relative;
    animation:     fade-up 0.4s ease both;
}
.scroll-card img { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; }
.scroll-caption {
    position:   absolute;
    bottom:     0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.55));
    color:      #fff;
    font-size:  11px;
    padding:    16px 10px 8px;
    text-align: center;
}

@keyframes fade-up {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
