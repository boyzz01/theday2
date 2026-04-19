<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    slug:    { type: String, default: '' },
    message: { type: String, default: 'Undangan ini sudah tidak aktif.' },
});
</script>

<template>
    <Head title="Undangan Tidak Aktif" />

    <div class="min-h-screen flex flex-col items-center justify-center px-4"
         style="background: linear-gradient(135deg, #F4F7F5 0%, #EBF0EC 100%)">

        <div class="w-full max-w-md text-center">
            <!-- Icon -->
            <div class="w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-6"
                 style="background-color: rgba(146,168,156,0.15)">
                <svg class="w-10 h-10" style="color: #92A89C" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>

            <!-- TheDay logo -->
            <a href="/" class="inline-block mb-6">
                <img src="/image/logo.svg" alt="TheDay" class="h-7 mx-auto opacity-60" />
            </a>

            <h1 class="text-2xl font-bold mb-3" style="color: #2C2417">
                Undangan Tidak Aktif
            </h1>
            <p class="text-base mb-8" style="color: #73877C">
                {{ message }}
            </p>

            <!-- Divider -->
            <div class="w-12 h-px mx-auto mb-8" style="background-color: #92A89C"></div>

            <p class="text-sm mb-6" style="color: #92A89C">
                Ingin membuat undangan pernikahan digitalmu sendiri?
            </p>

            <a href="/"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90"
               style="background-color: #92A89C">
                Buat Undangan Gratis
            </a>
        </div>
    </div>
</template>
