<script setup>
import { computed } from 'vue';
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);
const items = computed(() => props.data.items ?? []);

function addItem() { emit('update:data', { ...props.data, items: [...items.value, { title: '', date: '', description: '' }] }); }
function removeItem(idx) { emit('update:data', { ...props.data, items: items.value.filter((_, i) => i !== idx) }); }
function updateItem(idx, field, value) {
    emit('update:data', { ...props.data, items: items.value.map((it, i) => i === idx ? { ...it, [field]: value } : it) });
}
</script>
<template>
    <div class="space-y-4">
        <div v-for="(item, idx) in items" :key="idx" class="rounded-2xl border border-stone-100 p-4 space-y-3">
            <div class="flex justify-between items-center">
                <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wider">Momen {{ idx + 1 }}</h3>
                <button @click="removeItem(idx)" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
            </div>
            <input type="text" :value="item.title" @input="updateItem(idx, 'title', $event.target.value)"
                placeholder="Pertama Bertemu / Lamaran..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
            <input type="date" :value="item.date" @input="updateItem(idx, 'date', $event.target.value)"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 transition-colors"/>
            <textarea rows="3" :value="item.description" @input="updateItem(idx, 'description', $event.target.value)"
                placeholder="Ceritakan momen ini..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors resize-none"/>
        </div>
        <button @click="addItem"
            class="w-full py-2.5 rounded-xl border border-dashed border-stone-200 text-sm text-stone-400 hover:border-amber-300 hover:text-amber-600 transition-colors flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Momen
        </button>
    </div>
</template>
