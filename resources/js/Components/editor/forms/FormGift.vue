<script setup>
import { computed } from 'vue';
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);
const items = computed(() => props.data.items ?? []);

function addItem() {
    emit('update:data', { ...props.data, items: [...items.value, { type: 'bank_transfer', bank_name: '', account_name: '', account_number: '', note: '' }] });
}
function removeItem(idx) { emit('update:data', { ...props.data, items: items.value.filter((_, i) => i !== idx) }); }
function updateItem(idx, field, value) {
    emit('update:data', { ...props.data, items: items.value.map((it, i) => i === idx ? { ...it, [field]: value } : it) });
}
</script>
<template>
    <div class="space-y-4">
        <div v-for="(item, idx) in items" :key="idx" class="rounded-2xl border border-stone-100 p-4 space-y-3">
            <div class="flex justify-between items-center">
                <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wider">Metode {{ idx + 1 }}</h3>
                <button @click="removeItem(idx)" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
            </div>
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Tipe</label>
                <select :value="item.type" @change="updateItem(idx, 'type', $event.target.value)"
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 bg-white transition-colors">
                    <option value="bank_transfer">Transfer Bank</option>
                    <option value="ewallet">E-Wallet</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <template v-if="item.type === 'bank_transfer'">
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">Nama Bank</label>
                    <input type="text" :value="item.bank_name ?? ''" @input="updateItem(idx, 'bank_name', $event.target.value)"
                        placeholder="BCA / Mandiri / BNI..."
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
                </div>
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">Nama Rekening</label>
                    <input type="text" :value="item.account_name ?? ''" @input="updateItem(idx, 'account_name', $event.target.value)"
                        placeholder="Nama pemilik rekening..."
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
                </div>
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">Nomor Rekening</label>
                    <input type="text" :value="item.account_number ?? ''" @input="updateItem(idx, 'account_number', $event.target.value)"
                        placeholder="0123456789"
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
                </div>
            </template>
            <template v-else-if="item.type === 'ewallet'">
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">Provider</label>
                    <input type="text" :value="item.provider ?? ''" @input="updateItem(idx, 'provider', $event.target.value)"
                        placeholder="GoPay / OVO / DANA..."
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
                </div>
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">Nomor HP / ID</label>
                    <input type="text" :value="item.phone_number ?? ''" @input="updateItem(idx, 'phone_number', $event.target.value)"
                        placeholder="08xxxxxxxxxx"
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
                </div>
            </template>
            <div>
                <label class="block text-xs font-medium text-stone-600 mb-1.5">Catatan</label>
                <input type="text" :value="item.note ?? ''" @input="updateItem(idx, 'note', $event.target.value)"
                    placeholder="Opsional..."
                    class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 transition-colors"/>
            </div>
        </div>
        <button @click="addItem"
            class="w-full py-2.5 rounded-xl border border-dashed border-stone-200 text-sm text-stone-400 hover:border-amber-300 hover:text-amber-600 transition-colors flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Metode
        </button>
    </div>
</template>
