<script setup>
import { useLocale } from '@/Composables/useLocale';

const { locale, toggleLocale } = useLocale();
</script>

<template>
    <button
        type="button"
        @click="toggleLocale"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition-all hover:bg-stone-50"
        style="border-color: rgba(146,168,156,0.4); color: #73877C"
        :aria-label="locale === 'id' ? 'Switch to English' : 'Ganti ke Indonesia'"
    >
        <span>{{ locale === 'id' ? '🇮🇩' : '🇬🇧' }}</span>
        <span>{{ locale === 'id' ? 'ID' : 'EN' }}</span>
    </button>
</template>
