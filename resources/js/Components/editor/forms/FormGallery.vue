<script setup>
import { computed } from 'vue';

const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);

const items = computed(() => props.data.items ?? []);

function addPhoto() {
    emit('update:data', {
        ...props.data,
        items: [...items.value, { url: '', caption: '', sort_order: items.value.length }],
    });
}

function removePhoto(idx) {
    emit('update:data', { ...props.data, items: items.value.filter((_, i) => i !== idx) });
}

function updatePhoto(idx, field, value) {
    const newItems = items.value.map((item, i) =>
        i === idx ? { ...item, [field]: value } : item
    );
    emit('update:data', { ...props.data, items: newItems });
}

function updateLayout(layout) {
    emit('update:data', { ...props.data, layout });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Layout toggle -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Layout</label>
            <div class="flex gap-2">
                <button
                    v-for="opt in [{ value: 'grid', label: 'Grid' }, { value: 'carousel', label: 'Carousel' }]"
                    :key="opt.value"
                    @click="updateLayout(opt.value)"
                    :class="[
                        'flex-1 py-2 rounded-xl text-xs font-medium border transition-all',
                        (data.layout ?? 'grid') === opt.value
                            ? 'border-amber-400 bg-amber-50 text-amber-700'
                            : 'border-stone-200 text-stone-500 hover:border-stone-300',
                    ]"
                >{{ opt.label }}</button>
            </div>
        </div>

        <!-- Photo list -->
        <div class="space-y-3">
            <div
                v-for="(photo, idx) in items"
                :key="idx"
                class="rounded-2xl border border-stone-100 p-3 space-y-2"
            >
                <div class="flex items-start gap-2">
                    <!-- Preview thumbnail -->
                    <div class="w-12 h-12 rounded-lg bg-stone-100 overflow-hidden flex-shrink-0">
                        <img v-if="photo.url" :src="photo.url" class="w-full h-full object-cover" alt=""/>
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Fields -->
                    <div class="flex-1 space-y-1.5">
                        <input
                            type="url"
                            :value="photo.url"
                            @input="updatePhoto(idx, 'url', $event.target.value)"
                            placeholder="URL foto..."
                            class="w-full px-2.5 py-1.5 rounded-lg border border-stone-200 text-xs text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"
                        />
                        <input
                            type="text"
                            :value="photo.caption"
                            @input="updatePhoto(idx, 'caption', $event.target.value)"
                            placeholder="Caption (opsional)..."
                            class="w-full px-2.5 py-1.5 rounded-lg border border-stone-200 text-xs text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"
                        />
                    </div>

                    <button
                        @click="removePhoto(idx)"
                        class="p-1 rounded-lg text-stone-300 hover:text-red-400 hover:bg-red-50 transition-colors flex-shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <button
            @click="addPhoto"
            class="w-full py-2.5 rounded-xl border border-dashed border-stone-200 text-sm text-stone-400 hover:border-amber-300 hover:text-amber-600 transition-colors flex items-center justify-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Foto
        </button>
    </div>
</template>
