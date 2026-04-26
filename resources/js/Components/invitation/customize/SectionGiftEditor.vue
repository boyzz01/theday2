<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    invitationId: { type: String, required: true },
    modelValue:   { type: Object, default: () => ({ accounts: [] }) },
})
const emit   = defineEmits(['update:modelValue'])
const saving = ref(false)
const local  = ref(JSON.parse(JSON.stringify(props.modelValue?.accounts ?? [])))

function addAccount() {
    local.value.push({ bank_name: '', account_number: '', account_name: '' })
}

function removeAccount(index) {
    local.value.splice(index, 1)
    saveAll()
}

async function saveAll() {
    saving.value = true
    try {
        await axios.patch(`/api/invitations/${props.invitationId}/sections/gift`, {
            data:   { accounts: local.value },
            status: local.value.length ? 'complete' : 'empty',
        })
        emit('update:modelValue', { accounts: [...local.value] })
    } catch {
        alert('Gagal menyimpan.')
    } finally {
        saving.value = false
    }
}

defineExpose({ saveAll })
</script>

<template>
    <div class="space-y-3">
        <div v-for="(account, i) in local" :key="i" class="space-y-2 p-3 rounded-xl border border-stone-100 bg-white">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-stone-500">Rekening {{ i + 1 }}</span>
                <button type="button" @click="removeAccount(i)" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
            </div>
            <input v-model="account.bank_name" type="text" placeholder="Nama bank (mis. BCA)"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="account.account_number" type="text" placeholder="Nomor rekening"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
            <input v-model="account.account_name" type="text" placeholder="Nama pemilik rekening"
                class="w-full px-3 py-2 text-sm border border-stone-200 rounded-lg focus:outline-none focus:border-[#92A89C]" />
        </div>

        <p v-if="!local.length" class="text-xs text-stone-400 text-center py-3">Belum ada rekening.</p>

        <button type="button" @click="addAccount"
            class="w-full py-2.5 rounded-xl border-2 border-dashed border-stone-200 text-sm text-stone-500 hover:border-stone-300 hover:bg-stone-50 transition-colors">
            + Tambah Rekening
        </button>
    </div>
</template>
