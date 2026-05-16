<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Object, default: () => ({ stories: [] }) },
})
const emit = defineEmits(['update:modelValue'])

const local   = ref(JSON.parse(JSON.stringify(props.modelValue?.stories ?? [])))
const saving  = ref(false)
const editing = ref(null)  // index of story being edited, or null
const photoUploading = ref(false)
const photoError = ref(null)
const saveError  = ref(null)

const editForm = ref({ date: '', title: '', description: '', photo_url: '' })

function startAdd() {
    editForm.value = { date: '', title: '', description: '', photo_url: '' }
    editing.value  = -1  // -1 = new item
}

function startEdit(index) {
    editForm.value = { ...local.value[index] }
    editing.value  = index
}

function cancelEdit() {
    editing.value = null
}

function removeStory(index) {
    local.value.splice(index, 1)
    saveAll()
}

async function uploadPhoto(event) {
    const file = event.target.files[0]
    if (!file) return
    photoError.value = null
    photoUploading.value = true
    try {
        const fd = new FormData()
        fd.append('image', file)
        const res = await axios.post(
            `/api/invitations/${props.invitationId}/galleries`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        editForm.value.photo_url = res.data.data.image_url
    } catch {
        photoError.value = 'Upload foto gagal. Coba lagi.'
    } finally {
        photoUploading.value   = false
        event.target.value     = ''
    }
}

function confirmEdit() {
    if (editing.value === -1) {
        local.value.push({ ...editForm.value })
    } else {
        local.value[editing.value] = { ...editForm.value }
    }
    editing.value = null
    saveAll()
}

async function saveAll() {
    saving.value = true
    try {
        await axios.patch(`/api/invitations/${props.invitationId}/sections/love_story`, {
            data:   { stories: local.value },
            status: local.value.length ? 'complete' : 'empty',
        })
        emit('update:modelValue', { stories: [...local.value] })
    } catch {
        saveError.value = 'Gagal menyimpan. Coba lagi.'
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div class="space-y-3">
        <!-- Story list -->
        <div v-if="local.length" class="space-y-2">
            <div
                v-for="(story, i) in local"
                :key="i"
                class="flex items-center gap-3 p-3 rounded-xl border border-stone-100 bg-white"
            >
                <div
                    v-if="story.photo_url"
                    class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0"
                >
                    <img :src="story.photo_url" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-10 h-10 rounded-lg bg-stone-100 flex-shrink-0" />

                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-stone-700 truncate">{{ story.title || '(tanpa judul)' }}</p>
                    <p class="text-[10px] text-stone-400">{{ story.date }}</p>
                </div>

                <div class="flex gap-1">
                    <button type="button" @click="startEdit(i)" class="text-xs px-2 py-1 rounded-lg bg-stone-100 text-stone-600 hover:bg-stone-200">Edit</button>
                    <button type="button" @click="removeStory(i)" class="text-xs px-2 py-1 rounded-lg bg-red-50 text-red-400 hover:bg-red-100">×</button>
                </div>
            </div>
        </div>

        <p v-else-if="editing === null" class="text-xs text-stone-400 text-center py-3">Belum ada chapter.</p>

        <!-- Edit / Add form -->
        <div v-if="editing !== null" class="space-y-2 p-3 rounded-xl border border-[#92A89C]/30 bg-stone-50">
            <input v-model="editForm.date" type="text" placeholder="Tanggal (mis. Maret 2020)"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="editForm.title" type="text" placeholder="Judul chapter"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <textarea v-model="editForm.description" rows="3" placeholder="Cerita singkat..."
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C] resize-none" />

            <!-- Photo -->
            <div class="flex items-center gap-2">
                <img v-if="editForm.photo_url" :src="editForm.photo_url" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                <label class="flex-1 flex items-center justify-center px-3 py-2 rounded-lg border border-dashed border-stone-200 text-xs text-stone-500 cursor-pointer hover:bg-stone-100">
                    {{ photoUploading ? 'Mengupload...' : (editForm.photo_url ? 'Ganti foto' : '+ Foto') }}
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" :disabled="photoUploading" @change="uploadPhoto" />
                </label>
            </div>

            <p v-if="photoError" class="text-xs text-red-400">{{ photoError }}</p>

            <div class="flex gap-2">
                <button type="button" @click="cancelEdit" class="flex-1 py-2 rounded-xl text-xs font-medium border border-stone-200 text-stone-600 hover:bg-stone-50">Batal</button>
                <button type="button" @click="confirmEdit" :disabled="saving" class="flex-1 py-2 rounded-xl text-xs font-bold text-white bg-[#92A89C] hover:opacity-90 disabled:opacity-60">
                    {{ saving ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>

            <p v-if="saveError" class="text-xs text-red-400">{{ saveError }}</p>
        </div>

        <!-- Add button -->
        <button
            v-if="editing === null"
            type="button"
            @click="startAdd"
            class="w-full py-2.5 rounded-xl border-2 border-dashed border-stone-200 text-sm text-stone-500 hover:border-stone-300 hover:bg-stone-50 transition-colors"
        >
            + Tambah Chapter
        </button>
    </div>
</template>
