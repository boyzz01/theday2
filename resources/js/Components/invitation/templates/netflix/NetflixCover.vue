<script setup>
defineProps({
    coverUrl:    { type: String,  default: null },
    groomNick:   { type: String,  default: '' },
    brideNick:   { type: String,  default: '' },
    subtitle:    { type: String,  default: 'Sebuah Kisah Cinta' },
    eventDate:   { type: String,  default: '' },
    tags:        { type: Array,   default: () => [] },
    musicPlaying:{ type: Boolean, default: false },
})
defineEmits(['open', 'toggleMusic'])
</script>
<template><div>NetflixCover</div></template>
