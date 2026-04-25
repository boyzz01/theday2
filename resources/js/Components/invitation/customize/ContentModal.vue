<!-- resources/js/Components/invitation/customize/ContentModal.vue -->
<script setup>
defineProps({
    open:  { type: Boolean, required: true },
    title: { type: String,  required: true },
})
defineEmits(['close'])
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end lg:items-center justify-center p-0 lg:p-4"
            >
                <div class="absolute inset-0 bg-black/50" @click="$emit('close')" />

                <div class="relative w-full lg:max-w-lg max-h-[92vh] flex flex-col bg-white rounded-t-2xl lg:rounded-2xl shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100 flex-shrink-0">
                        <h2 class="text-sm font-bold text-stone-800">{{ title }}</h2>
                        <button
                            type="button"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors"
                            @click="$emit('close')"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-5 space-y-4">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="px-5 py-4 border-t border-stone-100 flex-shrink-0">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: translateY(20px);
}
</style>
