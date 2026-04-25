<!-- resources/js/Components/invitation/templates/scene/SceneGuestbook.vue -->
<script setup>
defineProps({
    open: { type: Boolean, default: true },
})
const emit = defineEmits(['open-form', 'open-list', 'toggle'])
</script>

<template>
    <div class="guestbook-bar" :class="{ 'bar-collapsed': !open }">
        <div class="bar-actions">
            <button class="action-btn" @click="emit('open-form')" aria-label="Tulis ucapan">
                +
            </button>
            <button class="action-btn" @click="emit('open-list')" aria-label="Lihat ucapan">
                ↑
            </button>
        </div>
        <button class="toggle-btn" @click="emit('toggle')">
            {{ open ? 'Sembunyikan Ucapan' : 'Tampilkan Ucapan' }}
        </button>
    </div>
</template>

<style scoped>
.guestbook-bar {
    position:        fixed;
    bottom:          0;
    left:            50%;
    transform:       translateX(-50%);
    width:           100%;
    max-width:       480px;
    background:      rgba(255, 235, 230, 0.95);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-top:      1px solid rgba(0, 0, 0, 0.08);
    display:         flex;
    align-items:     center;
    gap:             8px;
    padding:         10px 16px;
    z-index:         30;
    transition:      transform 0.3s ease;
}

.bar-collapsed {
    transform: translateX(-50%) translateY(100%);
}

.bar-actions {
    display: flex;
    gap:     6px;
}

.action-btn {
    width:         36px;
    height:        36px;
    border-radius: 50%;
    background:    #fff;
    border:        1px solid rgba(0,0,0,0.12);
    font-size:     18px;
    line-height:   1;
    cursor:        pointer;
    display:       flex;
    align-items:   center;
    justify-content: center;
    box-shadow:    0 1px 4px rgba(0,0,0,0.1);
}

.toggle-btn {
    background: none;
    border:     none;
    font-size:  13px;
    color:      #555;
    cursor:     pointer;
    flex:       1;
    text-align: left;
}
</style>
