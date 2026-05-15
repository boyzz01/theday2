<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavbarUserMenu from '@/Components/NavbarUserMenu.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { useLocale } from '@/Composables/useLocale';

const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user ?? null);
const isGuest = computed(() => auth.value?.isGuest ?? true);

const { t } = useLocale();
</script>

<template>
    <nav class="sticky top-0 z-40 border-b border-stone-100 bg-white/80 backdrop-blur-sm">
        <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">

            <!-- Logo — plain <a> because home is a Blade page, not Inertia -->
            <a href="/" class="flex items-center gap-2 flex-shrink-0">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-brand-primary">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <span class="font-semibold text-stone-800" style="font-family: 'Cormorant Garamond', serif; font-size: 1.15rem">TheDay</span>
            </a>

            <!-- Nav links -->
            <div class="hidden md:flex items-center gap-6 text-sm text-stone-500">
                <Link href="/templates" class="hover:text-stone-800 transition-colors">Template</Link>
                <Link href="/#harga" class="hover:text-stone-800 transition-colors">{{ t('nav.pricing') }}</Link>
            </div>

            <!-- Auth actions -->
            <div class="flex items-center gap-3">
                <!-- Language Toggle -->
                <LanguageSwitcher />

                <!-- Authenticated -->
                <NavbarUserMenu v-if="!isGuest && user" :user="user" />

                <!-- Guest -->
                <template v-else>
                    <Link
                        href="/login"
                        class="text-sm font-medium text-stone-500 hover:text-stone-800 transition-colors px-3 py-2"
                    >
                        {{ t('nav.login') }}
                    </Link>
                    <Link
                        href="/register"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all bg-brand-primary hover:bg-brand-primary-hover"
                    >
                        {{ t('nav.register') }}
                    </Link>
                </template>
            </div>
        </div>
    </nav>
</template>
