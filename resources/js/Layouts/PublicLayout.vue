<script setup>
import AppNavbar from '@/Components/AppNavbar.vue';
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
            © {{ new Date().getFullYear() }} TheDay. Dibuat dengan ❤️ di Indonesia.
        </footer>
    </div>
</template>
