<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import RegisterForm from './RegisterForm.vue';
import LoginForm    from './LoginForm.vue';

const props = defineProps({
    isOpen:     { type: Boolean, required: true },
    initialTab: { type: String,  default: 'register' },
});

const emit = defineEmits(['close', 'authenticated']);

const activeTab = ref(props.initialTab);

watch(() => props.initialTab, (v) => { activeTab.value = v; });
watch(() => props.isOpen,     (v) => { if (v) activeTab.value = props.initialTab; });

// ── Keyboard ─────────────────────────────────────────────────────────────────
function onKeydown(e) {
    if (e.key === 'Escape' && props.isOpen) emit('close');
}
onMounted(()  => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

function onAuthenticated(redirectUrl = null) {
    emit('authenticated', redirectUrl);
}
</script>

<template>
    <Teleport to="body">
        <Transition name="auth-modal">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(15,12,9,0.65); backdrop-filter: blur(6px)"
                @click.self="emit('close')"
            >
                <div
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="px-6 pt-6 pb-4 border-b border-stone-100 flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-6 h-6 rounded-lg flex items-center justify-center" style="background-color: #C8A26B">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </div>
                                <span class="font-semibold text-stone-800" style="font-family: 'Cormorant Garamond', serif">TheDay</span>
                            </div>
                            <p class="text-xs text-stone-400">
                                {{ activeTab === 'register' ? 'Simpan undanganmu sekarang.' : 'Lanjutkan mengedit undanganmu.' }}
                            </p>
                        </div>

                        <!-- Close button -->
                        <button
                            @click="emit('close')"
                            class="p-1.5 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors flex-shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Tab switcher -->
                    <div class="flex border-b border-stone-100">
                        <button
                            v-for="tab in [{ key: 'register', label: 'Daftar' }, { key: 'login', label: 'Masuk' }]"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            class="flex-1 py-3 text-sm font-medium transition-colors relative"
                            :class="activeTab === tab.key ? 'text-stone-800' : 'text-stone-400 hover:text-stone-600'"
                        >
                            {{ tab.label }}
                            <div
                                v-if="activeTab === tab.key"
                                class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full"
                                style="background-color: #C8A26B"
                            />
                        </button>
                    </div>

                    <!-- Form body (scrollable) -->
                    <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
                        <Transition name="tab-fade" mode="out-in">
                            <RegisterForm
                                v-if="activeTab === 'register'"
                                key="register"
                                :is-modal="true"
                                @authenticated="onAuthenticated"
                                @switch-tab="activeTab = $event"
                            />
                            <LoginForm
                                v-else
                                key="login"
                                :is-modal="true"
                                @authenticated="onAuthenticated"
                                @switch-tab="activeTab = $event"
                            />
                        </Transition>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.auth-modal-enter-active,
.auth-modal-leave-active {
    transition: opacity 0.2s ease;
}
.auth-modal-enter-active .bg-white,
.auth-modal-leave-active .bg-white {
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
}
.auth-modal-enter-from {
    opacity: 0;
}
.auth-modal-enter-from .bg-white {
    transform: scale(0.94);
    opacity: 0;
}
.auth-modal-leave-to {
    opacity: 0;
}

.tab-fade-enter-active, .tab-fade-leave-active { transition: opacity 0.15s, transform 0.15s; }
.tab-fade-enter-from  { opacity: 0; transform: translateX(8px); }
.tab-fade-leave-to    { opacity: 0; transform: translateX(-8px); }
</style>
