<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PasswordGate   from './PasswordGate.vue';
import SectionCover   from './Sections/SectionCover.vue';
import SectionOpening from './Sections/SectionOpening.vue';
import SectionEvents  from './Sections/SectionEvents.vue';
import SectionGallery from './Sections/SectionGallery.vue';
import SectionMusic   from './Sections/SectionMusic.vue';
import SectionRsvp    from './Sections/SectionRsvp.vue';
import SectionMessages from './Sections/SectionMessages.vue';
import SectionClosing from './Sections/SectionClosing.vue';

const props = defineProps({
    invitation:   { type: Object,  required: true },
    messages:     { type: Array,   default: () => [] },
    needPassword: { type: Boolean, default: false },
});

// ── Config / theme ────────────────────────────────────────────────────────
const cfg = computed(() => props.invitation.config ?? {});
const primaryColor = computed(() => cfg.value.primary_color ?? '#D4A373');
const fontFamily   = computed(() => cfg.value.font ?? 'Playfair Display');

// ── Envelope / open animation ─────────────────────────────────────────────
const opened    = ref(false);
const unlocked  = ref(!props.needPassword);

function openInvitation() {
    opened.value = true;
}

function onUnlocked() {
    unlocked.value = true;
}

// ── Dynamic Google Font injection ─────────────────────────────────────────
onMounted(() => {
    const font = fontFamily.value.replace(/ /g, '+');
    const link = document.createElement('link');
    link.rel   = 'stylesheet';
    link.href  = `https://fonts.googleapis.com/css2?family=${font}:ital,wght@0,400;0,600;0,700;1,400&display=swap`;
    document.head.appendChild(link);

    // Inject CSS vars on :root
    document.documentElement.style.setProperty('--inv-primary', primaryColor.value);
    document.documentElement.style.setProperty('--inv-font',    `'${fontFamily.value}', serif`);
});

// ── Background music ──────────────────────────────────────────────────────
const musicPlaying = ref(false);
const audioEl      = ref(null);

function toggleMusic() {
    if (!audioEl.value) return;
    if (musicPlaying.value) {
        audioEl.value.pause();
        musicPlaying.value = false;
    } else {
        audioEl.value.play().then(() => { musicPlaying.value = true; }).catch(() => {});
    }
}

// Auto-play after first user interaction on the envelope open
function handleOpenAndPlay() {
    openInvitation();
    if (props.invitation.music?.file_url && audioEl.value) {
        audioEl.value.play().then(() => { musicPlaying.value = true; }).catch(() => {});
    }
}

// ── Messages reactive list ────────────────────────────────────────────────
const localMessages = ref([...props.messages]);

function onMessageSent(msg) {
    localMessages.value.unshift(msg);
}

// ── Nav sections ──────────────────────────────────────────────────────────
const activeSection = ref('cover');
const sections = ['cover', 'opening', 'events', 'gallery', 'rsvp', 'messages', 'closing'];

const sectionRefs = {};
function setSectionRef(name) {
    return (el) => { if (el) sectionRefs[name] = el; };
}

let observer;
onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.dataset.section;
                }
            }
        },
        { threshold: 0.3 }
    );
    Object.entries(sectionRefs).forEach(([, el]) => observer.observe(el));
});
onUnmounted(() => observer?.disconnect());
</script>

<template>
    <Head>
        <title>{{ invitation.title }}</title>
        <meta name="description" :content="`Undangan ${invitation.title}`"/>
        <meta property="og:title" :content="invitation.title"/>
        <meta property="og:type" content="website"/>
        <meta v-if="invitation.details?.cover_photo_url"
              property="og:image" :content="invitation.details.cover_photo_url"/>
    </Head>

    <!-- Background audio -->
    <audio
        v-if="invitation.music?.file_url"
        ref="audioEl"
        :src="invitation.music.file_url"
        loop
        preload="none"
        class="sr-only"
    />

    <!-- ── Password gate ──────────────────────────────────────── -->
    <PasswordGate
        v-if="!unlocked"
        :slug="invitation.slug"
        :primary-color="primaryColor"
        :font-family="fontFamily"
        :cover-url="invitation.details?.cover_photo_url"
        @unlocked="onUnlocked"
    />

    <!-- ── Envelope / open screen ────────────────────────────── -->
    <Transition name="envelope">
        <div
            v-if="unlocked && !opened"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center text-center px-8"
            :style="{ backgroundColor: primaryColor + '15', fontFamily: fontFamily }"
        >
            <!-- Decorative top ornament -->
            <div class="absolute top-0 left-0 right-0 h-2" :style="{ backgroundColor: primaryColor }"/>

            <div class="max-w-sm w-full space-y-8">
                <div>
                    <p class="text-xs tracking-[0.3em] uppercase mb-3" :style="{ color: primaryColor }">
                        Undangan {{ invitation.event_type === 'pernikahan' ? 'Pernikahan' : 'Ulang Tahun' }}
                    </p>

                    <!-- Names -->
                    <template v-if="invitation.event_type === 'pernikahan'">
                        <h1 class="text-4xl font-semibold text-stone-800 leading-tight"
                            :style="{ fontFamily }">
                            {{ invitation.details?.groom_name ?? '—' }}
                        </h1>
                        <div class="flex items-center justify-center gap-3 my-3">
                            <div class="h-px flex-1" :style="{ backgroundColor: primaryColor }"/>
                            <span class="text-lg" :style="{ color: primaryColor }">&amp;</span>
                            <div class="h-px flex-1" :style="{ backgroundColor: primaryColor }"/>
                        </div>
                        <h1 class="text-4xl font-semibold text-stone-800 leading-tight"
                            :style="{ fontFamily }">
                            {{ invitation.details?.bride_name ?? '—' }}
                        </h1>
                    </template>
                    <template v-else>
                        <h1 class="text-4xl font-semibold text-stone-800 leading-tight"
                            :style="{ fontFamily }">
                            {{ invitation.details?.birthday_person_name ?? '—' }}
                        </h1>
                        <p v-if="invitation.details?.birthday_age" class="mt-2 text-xl text-stone-500">
                            {{ invitation.details.birthday_age }} tahun
                        </p>
                    </template>
                </div>

                <!-- First event date hint -->
                <div v-if="invitation.events?.length" class="text-sm text-stone-500">
                    {{ invitation.events[0].event_date_formatted }}
                </div>

                <!-- CTA button -->
                <button
                    @click="handleOpenAndPlay"
                    class="w-full py-4 rounded-2xl text-white text-sm font-semibold tracking-wide transition-all active:scale-95 shadow-lg"
                    :style="{ backgroundColor: primaryColor }"
                >
                    Buka Undangan
                    <svg class="inline-block w-4 h-4 ml-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <p class="text-xs text-stone-400">Kepada Yth. Bapak/Ibu/Sdr/i Tamu Undangan</p>
            </div>

            <!-- Bottom ornament -->
            <div class="absolute bottom-0 left-0 right-0 h-2" :style="{ backgroundColor: primaryColor }"/>
        </div>
    </Transition>

    <!-- ── Main scrolling content ─────────────────────────────── -->
    <div v-if="unlocked && opened" class="relative">

        <!-- Floating music button -->
        <button
            v-if="invitation.music?.file_url"
            @click="toggleMusic"
            :class="['fixed bottom-6 right-4 z-40 w-12 h-12 rounded-full shadow-lg flex items-center justify-center transition-all active:scale-90',
                     musicPlaying ? 'animate-spin-slow' : '']"
            :style="{ backgroundColor: primaryColor }"
            aria-label="Toggle musik"
        >
            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 19V6l12-3v13"/>
                <circle cx="6" cy="18" r="3"/>
                <circle cx="18" cy="15" r="3"/>
            </svg>
        </button>

        <!-- ── Sections ────────────────────────────────────────── -->
        <div data-section="cover" :ref="setSectionRef('cover')">
            <SectionCover
                :invitation="invitation"
                :primary-color="primaryColor"
                :font-family="fontFamily"
            />
        </div>

        <div data-section="opening" :ref="setSectionRef('opening')">
            <SectionOpening
                :invitation="invitation"
                :primary-color="primaryColor"
                :font-family="fontFamily"
            />
        </div>

        <div data-section="events" :ref="setSectionRef('events')">
            <SectionEvents
                :events="invitation.events"
                :primary-color="primaryColor"
                :font-family="fontFamily"
            />
        </div>

        <div v-if="invitation.galleries?.length" data-section="gallery" :ref="setSectionRef('gallery')">
            <SectionGallery
                :galleries="invitation.galleries"
                :primary-color="primaryColor"
            />
        </div>

        <div data-section="rsvp" :ref="setSectionRef('rsvp')">
            <SectionRsvp
                :slug="invitation.slug"
                :primary-color="primaryColor"
                :font-family="fontFamily"
            />
        </div>

        <div data-section="messages" :ref="setSectionRef('messages')">
            <SectionMessages
                :slug="invitation.slug"
                :messages="localMessages"
                :primary-color="primaryColor"
                :font-family="fontFamily"
                @message-sent="onMessageSent"
            />
        </div>

        <div data-section="closing" :ref="setSectionRef('closing')">
            <SectionClosing
                :invitation="invitation"
                :primary-color="primaryColor"
                :font-family="fontFamily"
            />
        </div>
    </div>
</template>

<style>
/* Global vars set from JS */
:root {
    --inv-primary: #D4A373;
    --inv-font: 'Playfair Display', serif;
}

/* Scroll-reveal base — sections animate in via IntersectionObserver */
.reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.7s ease, transform 0.7s ease;
}
.reveal.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Envelope transition */
.envelope-leave-active { transition: opacity 0.6s ease, transform 0.6s ease; }
.envelope-leave-to     { opacity: 0; transform: scale(1.04); }

/* Slow spin for music button */
@keyframes spin-slow { to { transform: rotate(360deg); } }
.animate-spin-slow { animation: spin-slow 4s linear infinite; }
</style>
