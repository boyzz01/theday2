<script setup>
defineProps({ guestName: { type: String, default: 'Tamu Undangan' } })
const emit = defineEmits(['proceed'])
</script>

<template>
    <div class="nfw-root" @click="emit('proceed')">
        <div class="nfw-logo">THEDAY</div>
        <p class="nfw-question">Who's watching?</p>
        <div class="nfw-avatar">
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="21" cy="26" r="4" fill="white"/>
                <circle cx="39" cy="26" r="4" fill="white"/>
                <path d="M20 38 Q30 46 40 38" stroke="white" stroke-width="3" stroke-linecap="round" fill="none"/>
            </svg>
        </div>
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
    font-family: Arial, Helvetica, sans-serif;
}
.nfw-logo {
    font-size: 48px; font-weight: 900;
    color: #E50914; letter-spacing: -2px;
    margin-bottom: 8px;
}
.nfw-question {
    font-size: 24px; color: #fff;
    font-weight: 400; margin: 0;
}
.nfw-avatar {
    width: 120px; height: 120px;
    background: linear-gradient(135deg, #E50914, #831010);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
}
.nfw-avatar svg { width: 70px; height: 70px; }
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
