<script setup>
const props = defineProps({ data: { type: Object, default: () => ({}) } });
const emit  = defineEmits(['update:data']);

function updatePerson(person, field, value) {
    emit('update:data', {
        ...props.data,
        [person]: { ...(props.data[person] ?? {}), [field]: value },
    });
}

function updatePhoto(person, url) {
    emit('update:data', {
        ...props.data,
        [person]: { ...(props.data[person] ?? {}), photo: url ? { url } : null },
    });
}

const fieldLabel = {
    full_name:   'Nama Lengkap *',
    nickname:    'Nama Panggilan',
    father_name: 'Nama Ayah',
    mother_name: 'Nama Ibu',
    instagram:   'Instagram',
};
</script>

<template>
    <div class="space-y-6">
        <template v-for="person in ['groom', 'bride']" :key="person">
            <div class="rounded-2xl border border-stone-100 p-4 space-y-3">
                <h3 class="text-xs font-semibold text-stone-500 uppercase tracking-wider">
                    {{ person === 'groom' ? '👤 Mempelai Pria' : '👤 Mempelai Wanita' }}
                </h3>

                <!-- Photo -->
                <div>
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">Foto</label>
                    <div v-if="data[person]?.photo?.url" class="mb-2 flex items-start gap-2">
                        <img :src="data[person].photo.url" class="w-14 h-14 rounded-full object-cover border-2 border-stone-100" alt="photo"/>
                        <button
                            @click="updatePhoto(person, '')"
                            class="text-xs text-red-400 hover:text-red-600 transition-colors mt-1"
                        >Hapus</button>
                    </div>
                    <input
                        type="url"
                        :value="data[person]?.photo?.url ?? ''"
                        @input="updatePhoto(person, $event.target.value)"
                        placeholder="URL foto..."
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                    />
                </div>

                <!-- Text fields -->
                <div v-for="(label, field) in fieldLabel" :key="field">
                    <label class="block text-xs font-medium text-stone-600 mb-1.5">{{ label }}</label>
                    <input
                        type="text"
                        :value="data[person]?.[field] ?? ''"
                        @input="updatePerson(person, field, $event.target.value)"
                        :placeholder="label.replace(' *', '') + '...'"
                        class="w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
