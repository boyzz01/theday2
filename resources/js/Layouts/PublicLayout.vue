<script setup>
import AppNavbar from '@/Components/AppNavbar.vue';
import { useLocale } from '@/Composables/useLocale';

const { t } = useLocale();
</script>

<template>
    <div class="min-h-screen flex flex-col" style="background-color: #FFFCF7">

        <AppNavbar />

        <!-- Page content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-stone-100 py-6 text-center text-xs text-stone-400">
            <p class="mb-2">© {{ new Date().getFullYear() }} TheDay. {{ t('Dibuat dengan ❤️ di Indonesia.', 'Made with ❤️ in Indonesia.') }}</p>
            <div class="flex items-center justify-center gap-4">
                <a href="/kontak" class="hover:text-stone-600 transition-colors">{{ t('Kontak', 'Contact') }}</a>
                <span class="text-stone-200">·</span>
                <a href="/kebijakan-privasi" class="hover:text-stone-600 transition-colors">{{ t('Kebijakan Privasi', 'Privacy Policy') }}</a>
                <span class="text-stone-200">·</span>
                <a href="/syarat-ketentuan" class="hover:text-stone-600 transition-colors">{{ t('Syarat & Ketentuan', 'Terms & Conditions') }}</a>
                <span class="text-stone-200">·</span>
                <a href="/kebijakan-cookie" class="hover:text-stone-600 transition-colors">{{ t('Kebijakan Cookie', 'Cookie Policy') }}</a>
            </div>
        </footer>
    </div>
</template>
