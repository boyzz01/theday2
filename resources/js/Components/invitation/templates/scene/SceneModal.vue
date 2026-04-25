<!-- resources/js/Components/invitation/templates/scene/SceneModal.vue -->
<script setup>
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title:      { type: String,  required: true },
})
const emit = defineEmits(['update:modelValue'])

function close() {
    emit('update:modelValue', false)
}

let touchStartY = 0

function onTouchStart(e) {
    touchStartY = e.touches[0].clientY
}

function onTouchEnd(e) {
    if (e.changedTouches[0].clientY - touchStartY > 80) close()
}
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition name="backdrop-fade">
            <div
                v-if="modelValue"
                class="modal-backdrop"
                @click="close"
            />
        </Transition>

        <!-- Sheet -->
        <Transition name="slide-up">
            <div
                v-if="modelValue"
                class="modal-sheet"
                @touchstart="onTouchStart"
                @touchend="onTouchEnd"
            >
                <!-- Browser chrome (dekoratif) -->
                <div class="browser-chrome">
                    <span class="dot dot-red" />
                    <span class="dot dot-yellow" />
                    <span class="dot dot-green" />
                    <span class="url-bar">theday.id</span>
                </div>

                <!-- Nav bar -->
                <div class="nav-bar">
                    <span class="modal-title">{{ title }}</span>
                    <button class="close-btn" @click="close" aria-label="Tutup">✕</button>
                </div>

                <!-- Scrollable content -->
                <div class="modal-content">
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-backdrop {
    position:   fixed;
    inset:      0;
    background: rgba(0, 0, 0, 0.55);
    z-index:    40;
}

.modal-sheet {
    position:         fixed;
    bottom:           0;
    left:             50%;
    transform:        translateX(-50%);
    width:            100%;
    max-width:        480px;
    max-height:       82vh;
    background:       #fff;
    border-radius:    16px 16px 0 0;
    z-index:          50;
    display:          flex;
    flex-direction:   column;
    overflow:         hidden;
}

.browser-chrome {
    display:         flex;
    align-items:     center;
    gap:             6px;
    padding:         8px 14px 6px;
    background:      #f0f0f0;
    border-bottom:   1px solid #ddd;
}

.dot {
    width:         10px;
    height:        10px;
    border-radius: 50%;
    display:       inline-block;
}
.dot-red    { background: #FF5F57; }
.dot-yellow { background: #FEBC2E; }
.dot-green  { background: #28C840; }

.url-bar {
    margin-left:   8px;
    font-size:     11px;
    color:         #888;
    background:    #e4e4e4;
    border-radius: 4px;
    padding:       2px 10px;
    flex:          1;
    text-align:    center;
}

.nav-bar {
    display:         flex;
    align-items:     center;
    justify-content: space-between;
    padding:         10px 16px;
    border-bottom:   1px solid #eee;
    flex-shrink:     0;
}

.modal-title {
    font-size:   15px;
    font-weight: 600;
    color:       #333;
}

.close-btn {
    background: none;
    border:     none;
    font-size:  16px;
    color:      #666;
    cursor:     pointer;
    padding:    4px 8px;
}

.modal-content {
    overflow-y:  auto;
    flex:        1;
    padding:     16px;
    -webkit-overflow-scrolling: touch;
}

/* Transitions */
.backdrop-fade-enter-active,
.backdrop-fade-leave-active { transition: opacity 0.25s ease; }
.backdrop-fade-enter-from,
.backdrop-fade-leave-to     { opacity: 0; }

.slide-up-enter-active,
.slide-up-leave-active { transition: transform 0.3s ease; }
.slide-up-enter-from,
.slide-up-leave-to     { transform: translateX(-50%) translateY(100%); }
</style>
