<!-- resources/js/Components/invitation/templates/scene/content/SceneContentLoveStory.vue -->
<script setup>
defineProps({
    stories: { type: Array, default: () => [] },
})
</script>

<template>
    <div>
        <p v-if="!stories.length" class="text-center text-gray-400 py-8 italic text-sm">
            Belum ada kisah cinta.
        </p>
        <div v-else class="relative pl-6 space-y-6 pb-4">
            <!-- Timeline line -->
            <div class="timeline-line" />

            <div v-for="(story, i) in stories" :key="i" class="relative story-item">
                <!-- Dot -->
                <div class="timeline-dot" />

                <!-- Photo (optional) -->
                <div v-if="story.photo_url" class="story-photo">
                    <img :src="story.photo_url" :alt="story.title" loading="lazy" />
                </div>

                <!-- Text -->
                <div :class="['story-text', story.photo_url ? 'has-photo' : '']">
                    <p class="story-date">{{ story.date }}</p>
                    <h4 class="story-title">{{ story.title }}</h4>
                    <p class="story-desc">{{ story.description }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.timeline-line {
    position:   absolute;
    left:       8px;
    top:        8px;
    bottom:     8px;
    width:      1.5px;
    background: linear-gradient(180deg, #d4a8c0, #c8a87a, #d4a8c0);
    opacity:    0.5;
}

.story-item { position: relative; }

.timeline-dot {
    position:      absolute;
    left:          -21px;
    top:           6px;
    width:         10px;
    height:        10px;
    border-radius: 50%;
    background:    linear-gradient(135deg, #e8b4c8, #c8956a);
    border:        2px solid #fff;
    box-shadow:    0 0 6px rgba(200, 140, 100, 0.4);
}

.story-photo {
    width:         100%;
    aspect-ratio:  16/9;
    border-radius: 12px 12px 0 0;
    overflow:      hidden;
    box-shadow:    0 4px 16px rgba(0,0,0,0.12);
}

.story-photo img {
    width:      100%;
    height:     100%;
    object-fit: cover;
    display:    block;
}

.story-text {
    background:    rgba(255, 255, 255, 0.5);
    border-radius: 12px;
    padding:       12px 14px;
}

.story-text.has-photo {
    border-radius: 0 0 12px 12px;
    padding-top:   14px;
}

.story-date {
    font-size:      10px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color:          #c8956a;
    margin-bottom:  3px;
}

.story-title {
    font-size:   14px;
    font-weight: 700;
    color:       #3d2010;
    margin:      0 0 5px;
}

.story-desc {
    font-size:   12px;
    color:       #7a5a40;
    line-height: 1.6;
    margin:      0;
}
</style>
