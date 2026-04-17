<script setup>
const props = defineProps({
    invitation:   { type: Object, required: true },
    primaryColor: { type: String, default: '#92A89C' },
    fontFamily:   { type: String, default: 'Playfair Display' },
});

const details = props.invitation.details ?? {};

const coverPhoto = details.cover_photo_url
    ?? details.groom_photo_url
    ?? null;

const firstEvent = props.invitation.events?.[0] ?? null;
</script>

<template>
    <section
        class="relative min-h-screen flex flex-col items-center justify-end pb-16 overflow-hidden"
        :style="{ backgroundColor: primaryColor + '18' }"
    >
        <!-- Cover photo / gradient bg -->
        <div class="absolute inset-0">
            <img
                v-if="coverPhoto"
                :src="coverPhoto"
                class="w-full h-full object-cover object-top"
                alt="Cover"
            />
            <div
                class="absolute inset-0"
                :style="{
                    background: coverPhoto
                        ? `linear-gradient(to bottom, transparent 30%, rgba(0,0,0,0.72) 100%)`
                        : `linear-gradient(160deg, ${primaryColor}22, ${primaryColor}08)`
                }"
            />
        </div>

        <!-- Floating decorative circles (no-photo fallback) -->
        <template v-if="!coverPhoto">
            <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full opacity-10"
                 :style="{ backgroundColor: primaryColor }"/>
            <div class="absolute top-1/3 -left-12 w-48 h-48 rounded-full opacity-8"
                 :style="{ backgroundColor: primaryColor }"/>
        </template>

        <!-- Content -->
        <div class="relative z-10 text-center px-8 space-y-5 w-full max-w-sm mx-auto">

            <!-- Couple photos -->
            <template v-if="details.groom_photo_url || details.bride_photo_url">
                <div class="flex items-end justify-center gap-3 mb-4">
                    <div v-if="details.groom_photo_url"
                         class="w-28 h-36 rounded-2xl overflow-hidden border-4 border-white/80 shadow-xl">
                        <img :src="details.groom_photo_url" class="w-full h-full object-cover" alt="Mempelai pria"/>
                    </div>
                    <div class="flex flex-col items-center gap-1 pb-2">
                        <div class="w-8 h-px" :style="{ backgroundColor: 'white' }"/>
                        <span class="text-white text-xl font-light">&amp;</span>
                        <div class="w-8 h-px" :style="{ backgroundColor: 'white' }"/>
                    </div>
                    <div v-if="details.bride_photo_url"
                         class="w-28 h-36 rounded-2xl overflow-hidden border-4 border-white/80 shadow-xl">
                        <img :src="details.bride_photo_url" class="w-full h-full object-cover" alt="Mempelai wanita"/>
                    </div>
                </div>
            </template>

            <!-- Names -->
            <div>
                <p class="text-xs tracking-[0.25em] uppercase mb-2"
                   :style="{ color: coverPhoto ? 'rgba(255,255,255,0.75)' : primaryColor }">
                    Pernikahan
                </p>
                <h1 class="text-5xl font-semibold leading-tight"
                    :style="{ fontFamily, color: coverPhoto ? 'white' : '#292524' }">
                    {{ details.groom_name ?? '—' }}
                </h1>
                <div class="flex items-center justify-center gap-3 my-2">
                    <div class="h-px w-12" :style="{ backgroundColor: coverPhoto ? 'rgba(255,255,255,0.5)' : primaryColor }"/>
                    <span class="text-2xl font-light"
                          :style="{ color: coverPhoto ? 'rgba(255,255,255,0.7)' : primaryColor }">&amp;</span>
                    <div class="h-px w-12" :style="{ backgroundColor: coverPhoto ? 'rgba(255,255,255,0.5)' : primaryColor }"/>
                </div>
                <h1 class="text-5xl font-semibold leading-tight"
                    :style="{ fontFamily, color: coverPhoto ? 'white' : '#292524' }">
                    {{ details.bride_name ?? '—' }}
                </h1>
            </div>

            <!-- Event date -->
            <div v-if="firstEvent"
                 class="inline-block px-5 py-2 rounded-full text-sm font-medium backdrop-blur-sm"
                 :style="{ backgroundColor: primaryColor + '30', color: coverPhoto ? 'white' : '#292524', border: `1px solid ${primaryColor}50` }">
                {{ firstEvent.event_date_formatted }}
            </div>

            <!-- Scroll hint -->
            <div class="flex flex-col items-center gap-1 pt-2 opacity-60">
                <span class="text-xs tracking-widest"
                      :style="{ color: coverPhoto ? 'white' : '#78716c' }">SCROLL</span>
                <svg class="w-4 h-4 animate-bounce"
                     :style="{ color: coverPhoto ? 'white' : primaryColor }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
    </section>
</template>
