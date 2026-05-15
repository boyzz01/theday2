<script setup>
import { ref, computed } from 'vue'
import { useInvitationTemplate } from '@/Composables/useInvitationTemplate'
import NetflixWhoWatching from './netflix/NetflixWhoWatching.vue'
import NetflixIntro       from './netflix/NetflixIntro.vue'
import NetflixCover       from './netflix/NetflixCover.vue'
import NetflixHero        from './netflix/NetflixHero.vue'

const props = defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
})

const {
    groomName, brideName, groomNick, brideNick,
    coverPhotoUrl,
    details, events, galleries,
    sectionEnabled, sectionData,
    openingText, closingText,
    firstEventDate, countdown, targetDate, pad,
    audioEl, musicPlaying, toggleMusic,
    toastMsg, toastVisible,
    copiedAccount, copyToClipboard,
    localMessages, msgForm, msgSubmitting, msgSuccess, msgError, submitMessage,
    rsvpForm, rsvpSubmitting, rsvpSuccess, rsvpError, submitRsvp,
} = useInvitationTemplate(props, {
    galleryLayout: 'grid',
    openingStyle:  'fade',
    revealClass:   'nf-visible',
})

// ── Netflix-specific config ───────────────────────────────────────────────────
const cfg             = computed(() => props.invitation.config ?? {})
const netflixSubtitle = computed(() => cfg.value.netflix_subtitle ?? 'Sebuah Kisah Cinta')
const netflixTags     = computed(() => cfg.value.netflix_tags ?? ['#lovestory', '#romantic'])
const heroQuote       = computed(() => cfg.value.netflix_hero_quote ?? sectionData('quote').text ?? '')

// ── Guest name for WhoWatching ────────────────────────────────────────────────
const guestName = computed(() => {
    if (props.isDemo) return 'Tamu Undangan'
    if (props.guest?.name) return props.guest.name
    const params = new URLSearchParams(window.location.search)
    const raw = params.get('to') ?? ''
    return decodeURIComponent(raw).replace(/\+/g, ' ').trim() || 'Tamu Undangan'
})

// ── Phase management ──────────────────────────────────────────────────────────
const phase = ref(props.autoOpen ? 'content' : 'who-watching')

function onWhoWatchingProceed() { phase.value = 'intro' }
function onIntroDone()          { phase.value = 'cover' }
function onCoverOpen() {
    phase.value = 'content'
    if (props.invitation.music?.file_url && audioEl.value) {
        audioEl.value.play().catch(() => {})
        musicPlaying.value = true
    }
}

// ── Gallery lightbox ──────────────────────────────────────────────────────────
const lightboxUrl = ref(null)

// ── Couple data ───────────────────────────────────────────────────────────────
const groomPhoto   = computed(() => details.value.groom_photo_url    ?? null)
const bridePhoto   = computed(() => details.value.bride_photo_url    ?? null)
const groomParents = computed(() => details.value.groom_parents_text ?? '')
const brideParents = computed(() => details.value.bride_parents_text ?? '')

// ── Love story ────────────────────────────────────────────────────────────────
const loveStories = computed(() => sectionData('love_story').stories ?? [])

// ── RSVP scroll target ────────────────────────────────────────────────────────
const rsvpRef = ref(null)
function scrollToRsvp() {
    rsvpRef.value?.scrollIntoView({ behavior: 'smooth' })
}

// ── First event for hero ──────────────────────────────────────────────────────
const firstEvent = computed(() => events.value[0] ?? null)
const eventDateForHero = computed(() => {
    if (!firstEvent.value) return ''
    const d   = firstEvent.value.event_date_formatted ?? firstEvent.value.event_date ?? ''
    const day = firstEvent.value.event_day_name ?? ''
    return day ? `${day}, ${d}` : d
})
</script>

<template>
    <div style="font-family: Arial, Helvetica, sans-serif; background: #141414; min-height: 100vh;">

        <!-- Audio -->
        <audio
            v-if="invitation.music?.file_url && sectionEnabled('music')"
            ref="audioEl"
            :src="invitation.music.file_url"
            loop preload="none" class="sr-only"
        />

        <!-- Phase 0: Who's Watching -->
        <NetflixWhoWatching
            v-if="phase === 'who-watching'"
            :guest-name="guestName"
            @proceed="onWhoWatchingProceed"
        />

        <!-- Phase 1: Intro animation -->
        <NetflixIntro
            v-else-if="phase === 'intro'"
            @done="onIntroDone"
        />

        <!-- Phase 2: Cover -->
        <NetflixCover
            v-else-if="phase === 'cover'"
            :cover-url="coverPhotoUrl"
            :groom-nick="groomNick"
            :bride-nick="brideNick"
            :subtitle="netflixSubtitle"
            :event-date="firstEventDate"
            :tags="netflixTags"
            :music-playing="musicPlaying"
            @open="onCoverOpen"
            @toggle-music="toggleMusic"
        />

        <!-- Phase 3+: Full content -->
        <template v-else>

            <!-- Hero / Detail page -->
            <NetflixHero
                v-if="sectionEnabled('opening')"
                :cover-url="coverPhotoUrl"
                :groom-name="groomName"
                :bride-name="brideName"
                :subtitle="netflixSubtitle"
                :event-date="eventDateForHero"
                :hero-quote="heroQuote"
                :opening-text="openingText"
                :quote-text="sectionData('quote').text ?? ''"
            />

            <!-- Breaking News (Opening) -->
            <section v-if="sectionEnabled('opening')" class="nf-section">
                <h2 class="nf-section-title">BREAKING NEWS</h2>
                <img v-if="coverPhotoUrl" :src="coverPhotoUrl" alt="" class="nf-full-img"/>
                <p class="nf-body-text">{{ openingText }}</p>
            </section>

            <!-- Bride & Groom -->
            <section v-if="sectionEnabled('couple')" class="nf-section">
                <h2 class="nf-section-title">BRIDE &amp; GROOM</h2>
                <div class="nf-couple-grid">
                    <div class="nf-person">
                        <img v-if="groomPhoto" :src="groomPhoto" class="nf-portrait" alt=""/>
                        <div v-else class="nf-portrait nf-portrait--placeholder"/>
                        <p class="nf-person-name">{{ groomName }}</p>
                        <p class="nf-person-parents">{{ groomParents }}</p>
                    </div>
                    <div class="nf-person">
                        <img v-if="bridePhoto" :src="bridePhoto" class="nf-portrait" alt=""/>
                        <div v-else class="nf-portrait nf-portrait--placeholder"/>
                        <p class="nf-person-name">{{ brideName }}</p>
                        <p class="nf-person-parents">{{ brideParents }}</p>
                    </div>
                </div>
                <img v-if="coverPhotoUrl" :src="coverPhotoUrl" alt="" class="nf-full-img"/>
            </section>

            <!-- Timeline & Location -->
            <section v-if="sectionEnabled('events') && events.length" class="nf-section">
                <h2 class="nf-section-title">TIMELINE &amp; LOCATION</h2>
                <div v-for="event in events" :key="event.id" class="nf-event-card">
                    <img v-if="coverPhotoUrl" :src="coverPhotoUrl" class="nf-event-thumb" alt=""/>
                    <div class="nf-event-body">
                        <span class="nf-event-badge">{{ event.event_name }}</span>
                        <p class="nf-event-date">{{ event.event_date_formatted }}</p>
                        <div class="nf-event-chips">
                            <span v-if="event.start_time" class="nf-chip">
                                {{ event.start_time }}<span v-if="event.end_time"> s.d {{ event.end_time }}</span>
                            </span>
                            <span v-if="event.timezone" class="nf-chip">#{{ event.timezone }}</span>
                        </div>
                        <p v-if="event.location" class="nf-event-address">{{ event.location }}</p>
                        <a v-if="event.maps_url" :href="event.maps_url" target="_blank" rel="noopener" class="nf-maps-link">
                            Buka Google Maps &raquo;
                        </a>
                    </div>
                </div>
                <button class="nf-cta-btn" @click="scrollToRsvp">KONFIRMASI KEHADIRAN</button>
            </section>

            <!-- Countdown -->
            <section v-if="sectionEnabled('countdown') && targetDate && countdown.days >= 0" class="nf-section">
                <div class="nf-countdown">
                    <div v-for="(val, label) in { Hari: countdown.days, Jam: countdown.hours, Menit: countdown.minutes, Detik: countdown.seconds }" :key="label" class="nf-cd-unit">
                        <span class="nf-cd-num">{{ pad(val) }}</span>
                        <span class="nf-cd-label">{{ label }}</span>
                    </div>
                </div>
            </section>

            <!-- Our Love Story -->
            <section v-if="sectionEnabled('love_story') && loveStories.length" class="nf-section">
                <h2 class="nf-section-title">OUR LOVE STORY</h2>
                <div v-for="(story, idx) in loveStories" :key="story.date ?? idx" class="nf-episode">
                    <div class="nf-episode-top">
                        <img v-if="story.photo_url" :src="story.photo_url" class="nf-episode-thumb" alt=""/>
                        <div v-else class="nf-episode-thumb nf-episode-thumb--ph"/>
                        <div class="nf-episode-meta">
                            <p class="nf-episode-label">Episode {{ idx + 1 }}: {{ story.title }}</p>
                            <p class="nf-episode-year">{{ story.date }}</p>
                        </div>
                    </div>
                    <p class="nf-episode-desc">{{ story.description }}</p>
                </div>
            </section>

            <!-- Gallery -->
            <section v-if="sectionEnabled('gallery') && galleries.length" class="nf-section">
                <h2 class="nf-section-title">GALLERY</h2>
                <div class="nf-gallery-grid">
                    <img
                        v-for="img in galleries" :key="img.id"
                        :src="img.file_url" :alt="img.caption ?? ''"
                        class="nf-gallery-img" loading="lazy"
                        @click="lightboxUrl = img.file_url"
                    />
                </div>
            </section>

            <!-- RSVP -->
            <section v-if="sectionEnabled('rsvp')" ref="rsvpRef" class="nf-section">
                <h2 class="nf-section-title">KONFIRMASI KEHADIRAN</h2>
                <form class="nf-form" @submit.prevent="submitRsvp">
                    <input v-model="rsvpForm.guest_name" class="nf-input" placeholder="Nama lengkap" required/>
                    <select v-model="rsvpForm.attendance" class="nf-input" required>
                        <option value="">Konfirmasi kehadiran</option>
                        <option value="hadir">Hadir</option>
                        <option value="tidak_hadir">Tidak Hadir</option>
                    </select>
                    <input v-model.number="rsvpForm.guest_count" type="number" min="1" max="10" class="nf-input" placeholder="Jumlah tamu"/>
                    <textarea v-model="rsvpForm.notes" class="nf-input nf-textarea" placeholder="Catatan (opsional)"/>
                    <p v-if="rsvpError" class="nf-error">{{ rsvpError }}</p>
                    <p v-if="rsvpSuccess" class="nf-success">Terima kasih atas konfirmasinya!</p>
                    <button type="submit" class="nf-cta-btn" :disabled="rsvpSubmitting">
                        {{ rsvpSubmitting ? 'Mengirim...' : 'KIRIM KONFIRMASI' }}
                    </button>
                </form>
            </section>

            <!-- Gift / Rekening -->
            <section v-if="sectionEnabled('gift') && sectionData('gift').accounts?.length" class="nf-section">
                <h2 class="nf-section-title">AMPLOP DIGITAL</h2>
                <div v-for="acc in sectionData('gift').accounts" :key="acc.account_number" class="nf-account-card">
                    <p class="nf-account-bank">{{ acc.bank }}</p>
                    <p class="nf-account-name">{{ acc.account_name }}</p>
                    <p class="nf-account-num">{{ acc.account_number }}</p>
                    <button class="nf-copy-btn" @click="copyToClipboard(acc.account_number)">
                        {{ copiedAccount === acc.account_number ? 'TERSALIN ✓' : 'SALIN NOMOR' }}
                    </button>
                </div>
            </section>

            <!-- Wishes / Buku Tamu -->
            <section v-if="sectionEnabled('wishes')" class="nf-section">
                <h2 class="nf-section-title">UCAPAN &amp; DOA</h2>
                <form class="nf-form" @submit.prevent="submitMessage">
                    <input v-model="msgForm.name" class="nf-input" placeholder="Nama" required/>
                    <textarea v-model="msgForm.message" class="nf-input nf-textarea" placeholder="Tulis ucapan & doa..." required/>
                    <p v-if="msgError" class="nf-error">{{ msgError }}</p>
                    <p v-if="msgSuccess" class="nf-success">Ucapan terkirim!</p>
                    <button type="submit" class="nf-cta-btn" :disabled="msgSubmitting">
                        {{ msgSubmitting ? 'Mengirim...' : 'KIRIM UCAPAN' }}
                    </button>
                </form>
                <div v-for="msg in localMessages" :key="msg.id ?? msg.name" class="nf-wish-item">
                    <p class="nf-wish-name">{{ msg.name }}</p>
                    <p class="nf-wish-msg">{{ msg.message }}</p>
                </div>
            </section>

            <!-- Closing -->
            <section v-if="sectionEnabled('closing')" class="nf-section nf-closing">
                <h2 class="nf-closing-names">{{ groomName }} &amp; {{ brideName }}</h2>
                <p class="nf-body-text nf-closing-text">{{ closingText }}</p>
                <p class="nf-closing-brand">THEDAY</p>
            </section>

        </template>

        <!-- Floating music button -->
        <button
            v-if="phase === 'content' && sectionEnabled('music') && invitation.music?.file_url"
            class="nf-float-music"
            @click="toggleMusic"
            aria-label="Toggle musik"
        >{{ musicPlaying ? '♪' : '♩' }}</button>

        <!-- Lightbox -->
        <div v-if="lightboxUrl" class="nf-lightbox" @click="lightboxUrl = null">
            <img :src="lightboxUrl" alt="" class="nf-lightbox-img"/>
        </div>

        <!-- Toast -->
        <Transition name="nf-toast">
            <div v-if="toastVisible" class="nf-toast">{{ toastMsg }}</div>
        </Transition>

    </div>
</template>

<style scoped>
.nf-section { padding: 32px 20px; border-bottom: 1px solid #1F1F1F; }
.nf-section-title { font-size: 28px; font-weight: 900; color: #fff; margin: 0 0 20px; letter-spacing: -0.5px; }
.nf-body-text { color: #fff; font-size: 16px; line-height: 1.7; margin: 12px 0 0; }
.nf-full-img { width: 100%; border-radius: 6px; margin: 16px 0 0; display: block; object-fit: cover; max-height: 300px; }

.nf-couple-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.nf-person { display: flex; flex-direction: column; gap: 8px; }
.nf-portrait { width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 6px; display: block; }
.nf-portrait--placeholder { background: #2D2D2D; }
.nf-person-name { color: #fff; font-weight: 700; font-size: 15px; margin: 0; }
.nf-person-parents { color: #bcbcbc; font-size: 13px; line-height: 1.4; margin: 0; }

.nf-event-card { display: flex; gap: 14px; margin-bottom: 24px; }
.nf-event-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.nf-event-body { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.nf-event-badge { background: #E50914; color: #fff; font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 999px; align-self: flex-start; }
.nf-event-date { color: #fff; font-weight: 700; font-size: 16px; margin: 0; }
.nf-event-chips { display: flex; gap: 6px; flex-wrap: wrap; }
.nf-chip { background: #333; color: #bcbcbc; font-size: 12px; padding: 3px 10px; border-radius: 999px; }
.nf-event-address { color: #bcbcbc; font-size: 13px; margin: 0; line-height: 1.4; }
.nf-maps-link { color: #E50914; font-size: 13px; font-weight: 600; text-decoration: none; }

.nf-cta-btn { width: 100%; padding: 16px; background: #E50914; border: none; color: #fff; font-size: 14px; font-weight: 700; letter-spacing: 2px; border-radius: 4px; cursor: pointer; margin-top: 16px; transition: background 0.2s; }
.nf-cta-btn:hover { background: #831010; }
.nf-cta-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.nf-countdown { display: flex; justify-content: center; gap: 24px; padding: 20px 0; }
.nf-cd-unit { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.nf-cd-num { font-size: 40px; font-weight: 900; color: #fff; font-variant-numeric: tabular-nums; }
.nf-cd-label { font-size: 12px; color: #bcbcbc; letter-spacing: 1px; text-transform: uppercase; }

.nf-episode { margin-bottom: 28px; }
.nf-episode-top { display: flex; gap: 14px; margin-bottom: 10px; }
.nf-episode-thumb { width: 90px; height: 90px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.nf-episode-thumb--ph { background: #2D2D2D; }
.nf-episode-meta { flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 4px; }
.nf-episode-label { color: #fff; font-weight: 700; font-size: 15px; margin: 0; line-height: 1.3; }
.nf-episode-year { color: #bcbcbc; font-size: 13px; margin: 0; }
.nf-episode-desc { color: #fff; font-size: 15px; line-height: 1.6; margin: 0; }

.nf-gallery-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.nf-gallery-img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; cursor: pointer; display: block; }

.nf-form { display: flex; flex-direction: column; gap: 12px; }
.nf-input { background: #333; border: 1px solid #8C8C8C; color: #fff; padding: 14px 16px; font-size: 16px; border-radius: 4px; font-family: inherit; outline: none; width: 100%; box-sizing: border-box; }
.nf-input:focus { border-color: #fff; }
.nf-textarea { min-height: 100px; resize: vertical; }
.nf-error { color: #E50914; font-size: 14px; margin: 0; }
.nf-success { color: #46D369; font-size: 14px; margin: 0; }

.nf-account-card { background: #1F1F1F; border-radius: 6px; padding: 16px 20px; margin-bottom: 12px; display: flex; flex-direction: column; gap: 4px; }
.nf-account-bank { color: #bcbcbc; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; margin: 0; }
.nf-account-name { color: #fff; font-weight: 700; font-size: 16px; margin: 0; }
.nf-account-num { color: #fff; font-size: 20px; font-weight: 900; letter-spacing: 2px; margin: 0; }
.nf-copy-btn { align-self: flex-start; margin-top: 8px; background: transparent; border: 2px solid #E50914; color: #E50914; padding: 8px 20px; font-size: 12px; font-weight: 700; letter-spacing: 2px; border-radius: 4px; cursor: pointer; transition: all 0.2s; }
.nf-copy-btn:hover { background: #E50914; color: #fff; }

.nf-wish-item { padding: 16px 0; border-bottom: 1px solid #222; }
.nf-wish-name { color: #fff; font-weight: 700; font-size: 14px; margin: 0 0 4px; }
.nf-wish-msg { color: #bcbcbc; font-size: 14px; line-height: 1.5; margin: 0; }

.nf-closing { text-align: center; padding-bottom: 64px; }
.nf-closing-names { color: #fff; font-size: 26px; font-weight: 700; margin: 0 0 16px; }
.nf-closing-text { text-align: center; color: #808080; }
.nf-closing-brand { margin-top: 32px; font-size: 24px; font-weight: 900; color: #333; letter-spacing: -1px; }

.nf-float-music { position: fixed; bottom: 16px; right: 16px; z-index: 40; width: 48px; height: 48px; background: #E50914; border: none; border-radius: 50%; color: #fff; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.4); }

.nf-lightbox { position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; cursor: zoom-out; }
.nf-lightbox-img { max-width: 95vw; max-height: 90vh; object-fit: contain; border-radius: 4px; }

.nf-toast { position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%); background: #2D2D2D; color: #fff; padding: 10px 20px; border-radius: 8px; font-size: 14px; z-index: 50; white-space: nowrap; box-shadow: 0 4px 12px rgba(0,0,0,0.4); }
.nf-toast-enter-active, .nf-toast-leave-active { transition: opacity 0.3s; }
.nf-toast-enter-from, .nf-toast-leave-to { opacity: 0; }
</style>
