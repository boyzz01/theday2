<script setup>
// PremiumUpsellCard.vue
// Pattern B locked teaser card — shown in place of premium sections for free users.
// Pattern A sections (hidden entirely) use v-if="canUsePremium" instead.

defineProps({
    title:       { type: String, required: true },
    description: { type: String, required: true },
});
</script>

<template>
    <div class="relative rounded-2xl border border-[#B8C7BF]/60 bg-gradient-to-br from-stone-50 to-[#92A89C]/5 overflow-hidden">
        <!-- Premium badge -->
        <div class="absolute top-3 right-3">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-stone-800 text-[#C4D0C9] text-xs font-semibold tracking-wide">
                <!-- Crown icon -->
                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M5 16L3 5l5.5 5L12 2l3.5 8L21 5l-2 11H5zm2 4h10v-2H7v2z"/>
                </svg>
                Premium
            </span>
        </div>

        <div class="p-5">
            <!-- Icon + text -->
            <div class="flex items-start gap-3 pr-20">
                <div class="w-9 h-9 rounded-xl bg-[#92A89C]/15 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-[#73877C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-stone-700">{{ title }}</p>
                    <p class="text-xs text-stone-500 mt-0.5 leading-relaxed">{{ description }}</p>
                </div>
            </div>

            <!-- CTA -->
            <a
                href="/upgrade"
                class="mt-4 w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-stone-800 text-[#C4D0C9] text-sm font-semibold hover:bg-stone-700 transition-colors"
            >
                Upgrade ke Premium
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</template>
