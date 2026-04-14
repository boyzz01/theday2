<script setup>
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);

function update(field, value) {
    emit('update:data', { ...props.data, [field]: value });
}

function updateCoverImage(url) {
    emit('update:data', { ...props.data, cover_image: { url } });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Cover image -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Foto Cover</label>
            <div v-if="data.cover_image?.url"
                 class="relative rounded-xl overflow-hidden mb-2 aspect-video bg-stone-100">
                <img :src="data.cover_image.url" class="w-full h-full object-cover" alt="cover"/>
                <button
                    @click="updateCoverImage('')"
                    class="absolute top-2 right-2 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center text-xs hover:bg-black/70 transition-colors"
                >✕</button>
            </div>
            <input
                type="url"
                :value="data.cover_image?.url ?? ''"
                @input="updateCoverImage($event.target.value)"
                placeholder="URL foto cover..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
            />
        </div>

        <!-- Headline -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Headline</label>
            <input
                type="text"
                :value="data.headline ?? 'The Wedding Of'"
                @input="update('headline', $event.target.value)"
                placeholder="The Wedding Of"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
            />
        </div>

        <!-- Groom name -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">
                Nama Mempelai Pria <span class="text-red-400">*</span>
            </label>
            <input
                type="text"
                :value="data.groom_name ?? ''"
                @input="update('groom_name', $event.target.value)"
                placeholder="Nama mempelai pria..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
            />
        </div>

        <!-- Bride name -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">
                Nama Mempelai Wanita <span class="text-red-400">*</span>
            </label>
            <input
                type="text"
                :value="data.bride_name ?? ''"
                @input="update('bride_name', $event.target.value)"
                placeholder="Nama mempelai wanita..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
            />
        </div>

        <!-- Opening text -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Teks Pembuka Amplop</label>
            <textarea
                rows="2"
                :value="data.opening_text ?? ''"
                @input="update('opening_text', $event.target.value)"
                placeholder="Kepada Yth. Bapak/Ibu/Saudara/i..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors resize-none"
            />
        </div>
    </div>
</template>
