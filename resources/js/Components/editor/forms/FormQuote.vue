<script setup>
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);
function update(field, value) { emit('update:data', { ...props.data, [field]: value }); }
</script>
<template>
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Kutipan / Ayat</label>
            <textarea rows="4" :value="data.text ?? ''" @input="update('text', $event.target.value)"
                placeholder="Dan di antara tanda-tanda kekuasaan-Nya..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors resize-none"/>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Sumber</label>
            <input type="text" :value="data.source ?? ''" @input="update('source', $event.target.value)"
                placeholder="QS. Ar-Rum: 21"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>
    </div>
</template>
