<script setup>
import ImageUploader from '@/Components/editor/ImageUploader.vue';

const props = defineProps({
    data:         { type: Object, default: () => ({}) },
    invitationId: { type: String, required: true },
});
const emit = defineEmits(['update:data']);

function update(field, value) { emit('update:data', { ...props.data, [field]: value }); }
</script>

<template>
    <div class="space-y-4">
        <p class="text-xs text-stone-400 bg-stone-50 rounded-xl px-3 py-2.5">
            Layar ini tampil pertama kali sebelum tamu membuka undangan.
        </p>

        <!-- Background image upload -->
        <ImageUploader
            :model-value="data.bg_image_url ?? ''"
            @update:model-value="update('bg_image_url', $event)"
            :invitation-id="invitationId"
            upload-type="cover"
            label="Foto Background Amplop"
            aspect-class="aspect-video"
            hint="JPG / PNG / WebP · maks. 5 MB"
        />

        <!-- Groom name -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">
                Nama Mempelai Pria <span class="text-red-400">*</span>
            </label>
            <input type="text"
                :value="data.groom_name ?? ''"
                @input="update('groom_name', $event.target.value)"
                placeholder="Nama singkat / panggilan..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>

        <!-- Bride name -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">
                Nama Mempelai Wanita <span class="text-red-400">*</span>
            </label>
            <input type="text"
                :value="data.bride_name ?? ''"
                @input="update('bride_name', $event.target.value)"
                placeholder="Nama singkat / panggilan..."
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>

        <!-- Recipient text -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Teks Tujuan Tamu</label>
            <input type="text"
                :value="data.recipient_text ?? ''"
                @input="update('recipient_text', $event.target.value)"
                placeholder="Kepada Yth. Bapak/Ibu/Saudara/i Tamu Undangan"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>

        <!-- Button text -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Teks Tombol</label>
            <input type="text"
                :value="data.button_text ?? ''"
                @input="update('button_text', $event.target.value)"
                placeholder="Buka Undangan"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>
    </div>
</template>
