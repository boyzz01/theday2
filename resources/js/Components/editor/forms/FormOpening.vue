<script setup>
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);
function update(field, value) { emit('update:data', { ...props.data, [field]: value }); }
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Salam Pembuka</label>
            <input
                type="text"
                :value="data.title ?? ''"
                @input="update('title', $event.target.value)"
                placeholder="Assalamu'alaikum Wr. Wb."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
            />
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Teks Pembuka</label>
            <textarea
                rows="5"
                :value="data.body ?? ''"
                @input="update('body', $event.target.value)"
                placeholder="Dengan memohon rahmat dan ridho Allah SWT..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors resize-none"
            />
        </div>
    </div>
</template>
