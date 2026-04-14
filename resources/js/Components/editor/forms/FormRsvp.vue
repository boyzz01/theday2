<script setup>
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);
function update(field, value) { emit('update:data', { ...props.data, [field]: value }); }
</script>
<template>
    <div class="space-y-4">
        <!-- Open toggle -->
        <div class="flex items-center justify-between p-3 rounded-xl bg-stone-50">
            <div>
                <p class="text-sm font-medium text-stone-700">Buka RSVP</p>
                <p class="text-xs text-stone-400 mt-0.5">Tamu dapat mengirim konfirmasi kehadiran</p>
            </div>
            <button
                @click="update('is_open', !(data.is_open ?? true))"
                :class="[
                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                    (data.is_open ?? true) ? 'bg-amber-400' : 'bg-stone-200',
                ]"
            >
                <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform',
                              (data.is_open ?? true) ? 'translate-x-6' : 'translate-x-1']"/>
            </button>
        </div>

        <!-- Deadline -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Batas RSVP</label>
            <input type="datetime-local" :value="(data.deadline ?? '').replace('Z','').slice(0,16)"
                @input="update('deadline', $event.target.value)"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>

        <!-- Max guests -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Maks. Tamu per RSVP</label>
            <input type="number" min="1" max="10" :value="data.max_guests_per_response ?? 2"
                @input="update('max_guests_per_response', parseInt($event.target.value))"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"/>
        </div>

        <!-- Toggles -->
        <div class="space-y-2">
            <template v-for="opt in [
                { key: 'collect_attendance_count', label: 'Tanya jumlah kehadiran' },
                { key: 'collect_message', label: 'Tanya ucapan/pesan' },
                { key: 'collect_meal_preference', label: 'Tanya pilihan makanan' },
            ]" :key="opt.key">
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-stone-600">{{ opt.label }}</span>
                    <button
                        @click="update(opt.key, !data[opt.key])"
                        :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                                 data[opt.key] ? 'bg-amber-400' : 'bg-stone-200']"
                    >
                        <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform',
                                      data[opt.key] ? 'translate-x-4' : 'translate-x-1']"/>
                    </button>
                </div>
            </template>
        </div>

        <!-- Success message -->
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Pesan Sukses</label>
            <textarea rows="2" :value="data.success_message ?? ''" @input="update('success_message', $event.target.value)"
                placeholder="Terima kasih atas konfirmasi kehadiran Anda"
                class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors resize-none"/>
        </div>
    </div>
</template>
