<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);

const navItems = [
    {
        label: 'Artikel',
        href: '/admin/articles',
        match: '/admin/articles',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>`,
    },
    {
        label: 'Tulis Artikel',
        href: '/admin/articles/create',
        match: '/admin/articles/create',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>`,
    },
];

const isActive = (href) => {
    const path = window.location.pathname;
    return path === href || (href !== '/admin/articles/create' && path.startsWith(href + '/') && !path.startsWith('/admin/articles/create'));
};

const logout = async () => {
    await axios.post(route('logout'));
    window.location.href = '/';
};

const initials = computed(() => {
    if (!user.value?.name) return 'A';
    return user.value.name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
});
</script>

<template>
    <div class="min-h-screen flex" style="background:#F8FAFC">

        <!-- Sidebar -->
        <aside class="w-56 flex-shrink-0 flex flex-col bg-gray-900 min-h-screen fixed top-0 left-0 h-full z-30">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background:#92A89C">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm leading-none" style="font-family:'Playfair Display',serif">TheDay</p>
                    <p class="text-white/40 text-xs mt-0.5">Admin Panel</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <a
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition cursor-pointer"
                    :class="isActive(item.href)
                        ? 'bg-white/10 text-white'
                        : 'text-white/50 hover:bg-white/5 hover:text-white/80'"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         v-html="item.icon" />
                    {{ item.label }}
                </a>

                <!-- Divider -->
                <div class="border-t border-white/10 my-3"></div>

                <!-- View Blog -->
                <a href="/blog" target="_blank"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-white/40 hover:text-white/70 transition cursor-pointer">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Lihat Blog
                </a>
            </nav>

            <!-- User + logout -->
            <div class="px-3 pb-5 border-t border-white/10 pt-4">
                <div class="flex items-center gap-3 px-3 py-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                         style="background:#92A89C">
                        {{ initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-xs font-medium truncate">{{ user?.name }}</p>
                        <p class="text-white/40 text-xs truncate">Administrator</p>
                    </div>
                </div>
                <button
                    @click="logout"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-white/40 hover:text-white/70 hover:bg-white/5 transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Keluar
                </button>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 ml-56 flex flex-col min-h-screen">

            <!-- Flash -->
            <Transition name="slide-down">
                <div v-if="flash?.success"
                     class="fixed top-4 right-4 z-50 px-5 py-3 rounded-xl text-sm font-medium text-white shadow-lg"
                     style="background:#059669">
                    {{ flash.success }}
                </div>
            </Transition>

            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
