<script setup>
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);
function update(field, value) { emit('update:data', { ...props.data, [field]: value }); }
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Judul Penutup</label>
            <input type="text" :value="data.title ?? ''" @input="update('title', $event.target.value)"
                placeholder="Terima Kasih"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Pesan Penutup</label>
            <textarea rows="5" :value="data.body ?? ''" @input="update('body', $event.target.value)"
                placeholder="Merupakan suatu kehormatan dan kebahagiaan bagi kami..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors resize-none"/>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Tanda Tangan / Nama Keluarga</label>
            <input type="text" :value="data.signature ?? ''" @input="update('signature', $event.target.value)"
                placeholder="Kami yang berbahagia"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>
    </div>
</template>
