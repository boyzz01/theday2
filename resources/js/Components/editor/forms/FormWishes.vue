<script setup>
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);
function update(field, value) { emit('update:data', { ...props.data, [field]: value }); }
</script>
<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between p-3 rounded-xl bg-stone-50">
            <div>
                <p class="text-sm font-medium text-stone-700">Aktifkan Ucapan</p>
                <p class="text-xs text-stone-400 mt-0.5">Tamu dapat mengirim ucapan dan doa</p>
            </div>
            <button @click="update('is_enabled', !(data.is_enabled ?? true))"
                :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                         (data.is_enabled ?? true) ? 'bg-amber-400' : 'bg-stone-200']">
                <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform',
                              (data.is_enabled ?? true) ? 'translate-x-6' : 'translate-x-1']"/>
            </button>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Moderasi</label>
            <select :value="data.moderation_mode ?? 'post_moderated'"
                @change="update('moderation_mode', $event.target.value)"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 bg-white transition-colors">
                <option value="none">Tanpa moderasi (langsung tampil)</option>
                <option value="post_moderated">Moderasi (review dulu)</option>
            </select>
        </div>
        <div class="flex items-center justify-between py-2">
            <span class="text-sm text-stone-600">Izinkan ucapan anonim</span>
            <button @click="update('allow_anonymous', !data.allow_anonymous)"
                :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                         data.allow_anonymous ? 'bg-amber-400' : 'bg-stone-200']">
                <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform',
                              data.allow_anonymous ? 'translate-x-4' : 'translate-x-1']"/>
            </button>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Teks Empty State</label>
            <input type="text" :value="data.empty_state_text ?? ''" @input="update('empty_state_text', $event.target.value)"
                placeholder="Jadilah yang pertama mengirim doa terbaik"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
        </div>
    </div>
</template>
