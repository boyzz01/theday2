<!-- resources/js/Components/invitation/templates/scene/content/SceneContentGift.vue -->
<script setup>
defineProps({
    accounts:      { type: Array,  default: () => [] },
    copiedAccount: { type: String, default: null },
})
const emit = defineEmits(['copy'])
</script>

<template>
    <div class="space-y-4">
        <p v-if="!accounts.length" class="text-center text-gray-400 py-8">
            Informasi rekening belum diisi.
        </p>
        <div
            v-for="acc in accounts"
            :key="acc.account_number"
            class="border border-gray-100 rounded-xl p-4 bg-gray-50"
        >
            <p class="font-semibold text-gray-800 text-sm">{{ acc.bank }}</p>
            <p class="text-lg font-mono text-gray-700 mt-1 tracking-wider">{{ acc.account_number }}</p>
            <p class="text-xs text-gray-500 mt-0.5">a/n {{ acc.account_name }}</p>
            <button
                @click="emit('copy', acc.account_number)"
                class="mt-3 w-full border border-gray-300 rounded-lg py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
            >
                {{ copiedAccount === acc.account_number ? '✓ Tersalin' : 'Salin Nomor' }}
            </button>
        </div>
    </div>
</template>
