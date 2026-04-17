<script setup>
// Step 4 — Interaksi
// Sections: rsvp (opt), wishes (opt), gift (opt)

import { ref } from 'vue';
import SectionAccordionCard from '@/Components/Wizard/SectionAccordionCard.vue';

const props = defineProps({
    sections:        { type: Object,   required: true },
    onToggleSection: { type: Function, required: true },
});

const expanded = ref(null);

function toggle(key) {
    expanded.value = expanded.value === key ? null : key;
}

function addBankAccount() {
    if (!props.sections.gift.data_json.accounts) {
        props.sections.gift.data_json.accounts = [];
    }
    props.sections.gift.data_json.accounts.push({ bank: '', name: '', number: '' });
}

function removeBankAccount(index) {
    props.sections.gift.data_json.accounts?.splice(index, 1);
}
</script>

<template>
    <div class="p-4 sm:p-6 space-y-3">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">Interaksi</h2>
            <p class="text-sm text-stone-400 mt-0.5">RSVP, ucapan, dan kado digital (semua opsional)</p>
        </div>

        <!-- RSVP (optional, enabled by default) -->
        <SectionAccordionCard
            title="RSVP"
            description="Form konfirmasi kehadiran tamu"
            :is-required="sections.rsvp?.is_required ?? false"
            :is-enabled="sections.rsvp?.is_enabled ?? true"
            :status="sections.rsvp?.completion_status ?? 'complete'"
            :expanded="expanded === 'rsvp'"
            @toggle-expand="toggle('rsvp')"
            @toggle-enabled="onToggleSection('rsvp')"
        >
            <div class="space-y-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-stone-700">Batas RSVP</label>
                    <input
                        v-model="sections.rsvp.data_json.deadline"
                        type="date"
                        class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition"
                    />
                    <p class="text-xs text-stone-400">Opsional. Tamu masih bisa RSVP setelah tanggal ini jika tidak diisi.</p>
                </div>
                <div class="flex items-center gap-3 p-3 bg-[#92A89C]/10 border border-[#B8C7BF]/50 rounded-xl">
                    <svg class="w-4 h-4 text-[#73877C] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-[#73877C]">Form RSVP akan muncul di halaman undangan. Data konfirmasi masuk ke dashboard.</p>
                </div>
            </div>
        </SectionAccordionCard>

        <!-- Wishes (optional, enabled by default) -->
        <SectionAccordionCard
            title="Ucapan & Doa"
            description="Kolom ucapan selamat dari tamu"
            :is-required="sections.wishes?.is_required ?? false"
            :is-enabled="sections.wishes?.is_enabled ?? true"
            :status="sections.wishes?.completion_status ?? 'complete'"
            :expanded="expanded === 'wishes'"
            @toggle-expand="toggle('wishes')"
            @toggle-enabled="onToggleSection('wishes')"
        >
            <div class="p-3 bg-[#92A89C]/10 border border-[#B8C7BF]/50 rounded-xl">
                <p class="text-xs text-[#73877C]">
                    Tamu dapat mengirimkan ucapan langsung dari halaman undangan.
                    Semua ucapan tersimpan di dashboard Anda.
                </p>
            </div>
        </SectionAccordionCard>

        <!-- Gift (optional) -->
        <SectionAccordionCard
            title="Kado Digital"
            description="Rekening bank untuk kado pernikahan"
            :is-required="sections.gift?.is_required ?? false"
            :is-enabled="sections.gift?.is_enabled ?? false"
            :status="sections.gift?.completion_status ?? 'disabled'"
            :expanded="expanded === 'gift'"
            @toggle-expand="toggle('gift')"
            @toggle-enabled="onToggleSection('gift')"
        >
            <div class="space-y-4">
                <div
                    v-for="(acc, index) in sections.gift.data_json.accounts ?? []"
                    :key="index"
                    class="rounded-xl border border-stone-200 p-4 space-y-3 bg-stone-50/50"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-stone-600">Rekening {{ index + 1 }}</span>
                        <button @click="removeBankAccount(index)"
                                class="p-1 rounded-lg text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Bank</label>
                            <input v-model="acc.bank" type="text" placeholder="BCA / Mandiri / dll"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Nomor Rekening</label>
                            <input v-model="acc.number" type="text" placeholder="1234567890"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-stone-600">Atas Nama</label>
                            <input v-model="acc.name" type="text" placeholder="Nama Pemilik"
                                   class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#92A89C]/50 focus:border-transparent transition bg-white"/>
                        </div>
                    </div>
                </div>

                <button
                    @click="addBankAccount"
                    class="w-full py-3 rounded-xl border-2 border-dashed border-stone-200 text-sm font-medium text-stone-500 hover:text-[#73877C] hover:border-[#92A89C]/50 hover:bg-[#92A89C]/10 transition-all flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Rekening
                </button>
            </div>
        </SectionAccordionCard>

    </div>
</template>
