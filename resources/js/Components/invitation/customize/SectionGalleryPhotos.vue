<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Array,  default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const uploading = ref(false)
const error     = ref(null)

async function upload(event) {
    const file = event.target.files[0]
    if (!file) return
    uploading.value = true
    error.value     = null
    try {
        const fd = new FormData()
        fd.append('image', file)
        const res = await axios.post(
            `/api/invitations/${props.invitationId}/galleries`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        emit('update:modelValue', [...props.modelValue, res.data.data])
    } catch {
        error.value = 'Upload gagal. Coba lagi.'
    } finally {
        uploading.value        = false
        event.target.value     = ''
    }
}

async function remove(gallery) {
    try {
        await axios.delete(`/api/invitations/${props.invitationId}/galleries/${gallery.id}`)
        emit('update:modelValue', props.modelValue.filter(g => g.id !== gallery.id))
    } catch {
        error.value = 'Gagal menghapus foto. Coba lagi.'
    }
}

async function move(index, direction) {
    const list = [...props.modelValue]
    const target = index + direction
    if (target < 0 || target >= list.length) return
    ;[list[index], list[target]] = [list[target], list[index]]
    emit('update:modelValue', list)
    try {
        await axios.put(`/api/invitations/${props.invitationId}/galleries/reorder`, {
            ids: list.map(g => g.id),
        })
    } catch {
        error.value = 'Gagal menata ulang foto.'
        emit('update:modelValue', props.modelValue)
    }
}
</script>

<template>
    <div class="space-y-3">
        <!-- Photo grid -->
        <div v-if="modelValue.length" class="grid grid-cols-3 gap-2">
            <div
                v-for="(photo, i) in modelValue"
                :key="photo.id"
                class="relative aspect-square rounded-lg overflow-hidden group"
            >
                <img :src="photo.image_url" class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100">
                    <button
                        type="button"
                        @click="move(i, -1)"
                        :disabled="i === 0"
                        class="w-6 h-6 bg-white/90 rounded text-stone-700 text-xs disabled:opacity-30"
                    >↑</button>
                    <button
                        type="button"
                        @click="move(i, 1)"
                        :disabled="i === modelValue.length - 1"
                        class="w-6 h-6 bg-white/90 rounded text-stone-700 text-xs disabled:opacity-30"
                    >↓</button>
                    <button
                        type="button"
                        @click="remove(photo)"
                        class="w-6 h-6 bg-red-500/90 rounded text-white text-xs"
                    >×</button>
                </div>
            </div>
        </div>

        <p v-else class="text-xs text-stone-400 text-center py-4">Belum ada foto.</p>

        <!-- Upload button -->
        <label class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-dashed border-stone-200 text-sm text-stone-500 hover:border-stone-300 hover:bg-stone-50 cursor-pointer transition-colors">
            <span>{{ uploading ? 'Mengupload...' : '+ Tambah Foto' }}</span>
            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" :disabled="uploading" @change="upload" />
        </label>

        <p v-if="error" class="text-xs text-red-400">{{ error }}</p>
    </div>
</template>
