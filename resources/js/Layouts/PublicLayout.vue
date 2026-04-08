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
            © {{ new Date().getFullYear() }} TheDay. {{ t('Dibuat dengan ❤️ di Indonesia.', 'Made with ❤️ in Indonesia.') }}
        </footer>
    </div>
</template>
