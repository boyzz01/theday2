<script setup>
import FormEnvelope       from './forms/FormEnvelope.vue';
import FormCover          from './forms/FormCover.vue';
import FormCouple         from './forms/FormCouple.vue';
import FormOpening        from './forms/FormOpening.vue';
import FormQuote          from './forms/FormQuote.vue';
import FormEvents         from './forms/FormEvents.vue';
import FormGallery        from './forms/FormGallery.vue';
import FormRsvp           from './forms/FormRsvp.vue';
import FormWishes         from './forms/FormWishes.vue';
import FormGift           from './forms/FormGift.vue';
import FormClosing        from './forms/FormClosing.vue';
import FormLoveStory      from './forms/FormLoveStory.vue';
import FormAdditionalInfo from './forms/FormAdditionalInfo.vue';

const FORM_MAP = {
    envelope:        FormEnvelope,
    cover:           FormCover,
    couple:          FormCouple,
    opening:         FormOpening,
    quote:           FormQuote,
    events:          FormEvents,
    gallery:         FormGallery,
    rsvp:            FormRsvp,
    wishes:          FormWishes,
    gift:            FormGift,
    closing:         FormClosing,
    love_story:      FormLoveStory,
    additional_info: FormAdditionalInfo,
};

const props = defineProps({
    section:      { type: Object, required: true },
    invitationId: { type: String, required: true },
});

const emit = defineEmits(['update:data']);

const formComponent = FORM_MAP[props.section.section_type] ?? null;
</script>

<template>
    <div>
        <!-- Section header -->
        <div class="flex items-center gap-3 mb-5">
            <div class="flex-1 min-w-0">
                <h2 class="text-base font-semibold text-stone-800" style="font-family: 'Playfair Display', serif">
                    {{ section.label }}
                </h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span v-if="section.is_required"
                          class="text-[10px] font-medium text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full">
                        Wajib
                    </span>
                    <span v-if="!section.is_enabled"
                          class="text-[10px] font-medium text-stone-400 bg-stone-100 px-1.5 py-0.5 rounded-full">
                        Nonaktif
                    </span>
                    <span :class="[
                        'text-[10px] font-medium px-1.5 py-0.5 rounded-full',
                        section.completion_status === 'complete' ? 'text-emerald-600 bg-emerald-50' :
                        section.completion_status === 'incomplete' ? 'text-amber-600 bg-amber-50' :
                        section.completion_status === 'error' ? 'text-red-600 bg-red-50' :
                        'text-stone-400 bg-stone-100',
                    ]">
                        {{ { complete: 'Lengkap', incomplete: 'Belum lengkap', empty: 'Kosong', warning: 'Perlu perhatian', error: 'Ada error' }[section.completion_status] ?? '' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Disabled state banner -->
        <div v-if="!section.is_enabled"
             class="mb-4 px-3 py-2.5 rounded-xl bg-stone-50 border border-stone-100 text-xs text-stone-400 text-center">
            Section ini sedang dinonaktifkan dan tidak akan ditampilkan di undangan.
        </div>

        <!-- Form -->
        <component
            v-if="formComponent"
            :is="formComponent"
            :data="section.data ?? {}"
            :invitation-id="invitationId"
            @update:data="emit('update:data', $event)"
        />

        <!-- No form available -->
        <div v-else class="py-12 text-center">
            <p class="text-sm text-stone-400">Form untuk section ini belum tersedia.</p>
        </div>
    </div>
</template>
