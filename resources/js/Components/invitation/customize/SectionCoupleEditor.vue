<!-- resources/js/Components/invitation/customize/SectionCoupleEditor.vue -->
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

const form    = ref({ ...props.modelValue })
const saving  = ref(false)
const error   = ref(null)
const groomPhotoFile  = ref(null)
const bridePhotoFile  = ref(null)

function setGroomPhoto(e) { groomPhotoFile.value = e.target.files[0] || null }
function setBridePhoto(e) { bridePhotoFile.value = e.target.files[0] || null }

async function save() {
    saving.value = true
    error.value  = null
    try {
        const fd = new FormData()
        const fields = [
            'groom_name','groom_nickname','groom_instagram','groom_parent_names',
            'bride_name','bride_nickname','bride_instagram','bride_parent_names',
        ]
        fields.forEach(f => { if (form.value[f] != null) fd.append(f, form.value[f]) })
        if (groomPhotoFile.value) fd.append('groom_photo', groomPhotoFile.value)
        if (bridePhotoFile.value) fd.append('bride_photo', bridePhotoFile.value)

        const res = await axios.post(
            `/api/invitations/${props.invitationId}/details`,
            fd,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        form.value = { ...form.value, ...res.data.data }
        groomPhotoFile.value = null
        bridePhotoFile.value = null
        emit('update:modelValue', { ...form.value })
    } catch {
        error.value = 'Gagal menyimpan. Coba lagi.'
    } finally {
        saving.value = false
    }
}

defineExpose({ save })
</script>

<template>
    <div class="space-y-5">
        <!-- Groom -->
        <div class="space-y-2">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Pengantin Pria</p>
            <input v-model="form.groom_name" type="text" placeholder="Nama lengkap"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="form.groom_nickname" type="text" placeholder="Nama panggilan"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-stone-400">@</span>
                <input v-model="form.groom_instagram" type="text" placeholder="username instagram"
                    class="w-full pl-7 pr-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            </div>
            <input v-model="form.groom_parent_names" type="text" placeholder="Nama orang tua"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <label class="flex items-center gap-2 cursor-pointer">
                <img v-if="form.groom_photo_url" :src="form.groom_photo_url" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                <div v-else class="w-10 h-10 rounded-full bg-stone-100 flex-shrink-0" />
                <span class="text-xs text-stone-500 underline">{{ groomPhotoFile ? groomPhotoFile.name : 'Ganti foto' }}</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="setGroomPhoto" />
            </label>
        </div>

        <!-- Bride -->
        <div class="space-y-2">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wider">Pengantin Wanita</p>
            <input v-model="form.bride_name" type="text" placeholder="Nama lengkap"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="form.bride_nickname" type="text" placeholder="Nama panggilan"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-stone-400">@</span>
                <input v-model="form.bride_instagram" type="text" placeholder="username instagram"
                    class="w-full pl-7 pr-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            </div>
            <input v-model="form.bride_parent_names" type="text" placeholder="Nama orang tua"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <label class="flex items-center gap-2 cursor-pointer">
                <img v-if="form.bride_photo_url" :src="form.bride_photo_url" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                <div v-else class="w-10 h-10 rounded-full bg-stone-100 flex-shrink-0" />
                <span class="text-xs text-stone-500 underline">{{ bridePhotoFile ? bridePhotoFile.name : 'Ganti foto' }}</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="setBridePhoto" />
            </label>
        </div>

        <p v-if="error" class="text-xs text-red-400">{{ error }}</p>
    </div>
</template>
