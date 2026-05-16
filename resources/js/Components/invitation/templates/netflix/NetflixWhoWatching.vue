<script setup>
import TheDayLogo from './TheDayLogo.vue'
defineProps({ guestName: { type: String, default: 'Tamu Undangan' } })
const emit = defineEmits(['proceed'])
</script>

<template>
    <div class="nfw-root" @click="emit('proceed')">
        <TheDayLogo class="nfw-logo" :height="72"/>
        <p class="nfw-question">Who's watching?</p>
        <img class="nfw-avatar" src="/images/templates/netflix/guest.webp" alt="Guest avatar"/>
        <p class="nfw-name">{{ guestName }}</p>
        <button class="nfw-btn" @click.stop="emit('proceed')">OPEN INVITATION</button>
    </div>
</template>

<style scoped>
.nfw-root {
    position: fixed; inset: 0; z-index: 50;
    background: #141414;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 20px; cursor: pointer;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}
.nfw-logo {
    margin-bottom: 8px;
    display: block;
}
.nfw-question {
    font-size: 24px; color: #fff;
    font-weight: 400; margin: 0;
}
.nfw-avatar {
    width: 120px; height: 120px;
    border-radius: 16px;
    object-fit: cover;
    display: block;
}
.nfw-name {
    font-size: 18px; color: #E50914;
    font-weight: 600; margin: 0;
}
.nfw-btn {
    margin-top: 12px;
    padding: 14px 40px;
    background: transparent;
    border: 2px solid #fff;
    color: #fff;
    font-size: 14px; font-weight: 700;
    letter-spacing: 3px;
    cursor: pointer;
    transition: background 0.2s;
}
.nfw-btn:hover { background: rgba(255,255,255,0.1); }
</style>
