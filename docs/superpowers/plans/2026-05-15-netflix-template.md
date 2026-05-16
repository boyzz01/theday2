# Netflix Template Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a full Netflix-themed wedding invitation template (`key: 'netflix'`) with a 4-phase interaction flow: Who's Watching → Netflix Intro (N animation + ta-dum) → Cover → Scrollable content.

**Architecture:** Approach B — `NetflixTemplate.vue` orchestrates a `phase` ref (`who-watching` → `intro` → `cover` → `content`). Four sub-components handle phases 0–3. All content sections are inline in the main template using `useInvitationTemplate` composable data.

**Tech Stack:** Vue 3 (Composition API), Inertia.js, Tailwind CSS, Web Audio API (ta-dum synthesis), CSS animations (N letterform).

**Spec:** `docs/superpowers/specs/2026-05-15-netflix-template-design.md`

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `resources/js/Components/invitation/templates/netflix/NetflixWhoWatching.vue` | Phase 0: profile picker screen |
| Create | `resources/js/Components/invitation/templates/netflix/NetflixIntro.vue` | Phase 1: N animation + ta-dum sound |
| Create | `resources/js/Components/invitation/templates/netflix/NetflixCover.vue` | Phase 2: hero/thumbnail cover |
| Create | `resources/js/Components/invitation/templates/netflix/NetflixHero.vue` | Phase 3: series detail / synopsis |
| Create | `resources/js/Components/invitation/templates/NetflixTemplate.vue` | Main orchestrator + all content sections |
| Modify | `resources/js/Components/invitation/templates/registry.js` | Register `'netflix'` key |

---

## Task 1: Create directory + scaffold empty sub-components

**Files:**
- Create: `resources/js/Components/invitation/templates/netflix/NetflixWhoWatching.vue`
- Create: `resources/js/Components/invitation/templates/netflix/NetflixIntro.vue`
- Create: `resources/js/Components/invitation/templates/netflix/NetflixCover.vue`
- Create: `resources/js/Components/invitation/templates/netflix/NetflixHero.vue`

- [ ] **Step 1: Create the `netflix/` directory with minimal stub files**

Create `resources/js/Components/invitation/templates/netflix/NetflixWhoWatching.vue`:
```vue
<script setup>
defineProps({ guestName: { type: String, default: 'Tamu Undangan' } })
defineEmits(['proceed'])
</script>
<template><div>NetflixWhoWatching</div></template>
```

Create `resources/js/Components/invitation/templates/netflix/NetflixIntro.vue`:
```vue
<script setup>
defineEmits(['done'])
</script>
<template><div>NetflixIntro</div></template>
```

Create `resources/js/Components/invitation/templates/netflix/NetflixCover.vue`:
```vue
<script setup>
defineProps({
    coverUrl:    { type: String,  default: null },
    groomNick:   { type: String,  default: '' },
    brideNick:   { type: String,  default: '' },
    subtitle:    { type: String,  default: 'Sebuah Kisah Cinta' },
    eventDate:   { type: String,  default: '' },
    tags:        { type: Array,   default: () => [] },
    musicPlaying:{ type: Boolean, default: false },
})
defineEmits(['open', 'toggleMusic'])
</script>
<template><div>NetflixCover</div></template>
```

Create `resources/js/Components/invitation/templates/netflix/NetflixHero.vue`:
```vue
<script setup>
defineProps({
    coverUrl:    { type: String, default: null },
    groomName:   { type: String, default: '' },
    brideName:   { type: String, default: '' },
    subtitle:    { type: String, default: 'Sebuah Kisah Cinta' },
    eventDate:   { type: String, default: '' },
    heroQuote:   { type: String, default: '' },
    openingText: { type: String, default: '' },
    quoteText:   { type: String, default: '' },
})
</script>
<template><div>NetflixHero</div></template>
```

- [ ] **Step 2: Commit stubs**

```bash
git add resources/js/Components/invitation/templates/netflix/
git commit -m "feat(netflix): scaffold sub-component stubs"
```

---

## Task 2: Implement NetflixWhoWatching.vue

**Files:**
- Modify: `resources/js/Components/invitation/templates/netflix/NetflixWhoWatching.vue`

- [ ] **Step 1: Implement full component**

Replace the stub with:
```vue
<script setup>
defineProps({ guestName: { type: String, default: 'Tamu Undangan' } })
const emit = defineEmits(['proceed'])
</script>

<template>
    <div class="nfw-root" @click="emit('proceed')">
        <div class="nfw-logo">THEDAY</div>
        <p class="nfw-question">Who's watching?</p>
        <div class="nfw-avatar">
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="21" cy="26" r="4" fill="white"/>
                <circle cx="39" cy="26" r="4" fill="white"/>
                <path d="M20 38 Q30 46 40 38" stroke="white" stroke-width="3" stroke-linecap="round" fill="none"/>
            </svg>
        </div>
        <p class="nfw-name">{{ guestName }}</p>
        <button class="nfw-btn" @click.stop="emit('proceed')">OPEN INVITATION</button>
    </div>
</template>

<style scoped>
.nfw-root {
    position: fixed; inset: 0; z-index: 50;
    background: #141414;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 20px; cursor: pointer;
    font-family: Arial, Helvetica, sans-serif;
}
.nfw-logo {
    font-size: 48px; font-weight: 900;
    color: #E50914; letter-spacing: -2px;
    margin-bottom: 8px;
}
.nfw-question {
    font-size: 24px; color: #fff;
    font-weight: 400; margin: 0;
}
.nfw-avatar {
    width: 120px; height: 120px;
    background: linear-gradient(135deg, #E50914, #831010);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
}
.nfw-avatar svg { width: 70px; height: 70px; }
.nfw-name {
    font-size: 18px; color: #E50914;
    font-weight: 600; margin: 0;
}
.nfw-btn {
    margin-top: 12px;
    padding: 14px 40px;
    background: transparent;
    border: 2px solid #fff;
    color: #fff;
    font-size: 14px; font-weight: 700;
    letter-spacing: 3px;
    cursor: pointer;
    transition: background 0.2s;
}
.nfw-btn:hover { background: rgba(255,255,255,0.1); }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/netflix/NetflixWhoWatching.vue
git commit -m "feat(netflix): implement NetflixWhoWatching phase 0"
```

---

## Task 3: Implement NetflixIntro.vue

**Files:**
- Modify: `resources/js/Components/invitation/templates/netflix/NetflixIntro.vue`

- [ ] **Step 1: Implement N animation + Web Audio ta-dum**

```vue
<script setup>
import { onMounted } from 'vue'

const emit = defineEmits(['done'])

function playTaDum() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()

        // Note 1: low "ta" (D2 ~73Hz) with quick decay
        const osc1  = ctx.createOscillator()
        const gain1 = ctx.createGain()
        osc1.connect(gain1); gain1.connect(ctx.destination)
        osc1.type = 'sine'
        osc1.frequency.value = 73.42
        gain1.gain.setValueAtTime(0.9, ctx.currentTime)
        gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5)
        osc1.start(ctx.currentTime)
        osc1.stop(ctx.currentTime + 0.5)

        // Note 2: resonant "dum" (B1 ~61Hz), delayed 0.12s
        const osc2  = ctx.createOscillator()
        const gain2 = ctx.createGain()
        osc2.connect(gain2); gain2.connect(ctx.destination)
        osc2.type = 'sine'
        osc2.frequency.value = 61.74
        gain2.gain.setValueAtTime(0, ctx.currentTime + 0.12)
        gain2.gain.linearRampToValueAtTime(1.0, ctx.currentTime + 0.18)
        gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.2)
        osc2.start(ctx.currentTime + 0.12)
        osc2.stop(ctx.currentTime + 1.2)
    } catch {
        // AudioContext blocked — continue silently
    }
}

onMounted(() => {
    playTaDum()
    // Animation is 2s, auto-emit done after 2.3s
    setTimeout(() => emit('done'), 2300)
})
</script>

<template>
    <div class="nfi-root">
        <div class="nfi-n">
            <div class="nfi-bar nfi-bar--left"/>
            <div class="nfi-bar nfi-bar--diag"/>
            <div class="nfi-bar nfi-bar--right"/>
        </div>
    </div>
</template>

<style scoped>
.nfi-root {
    position: fixed; inset: 0; z-index: 50;
    background: #000;
    display: flex; align-items: center; justify-content: center;
}
.nfi-n {
    position: relative; width: 80px; height: 120px;
    animation: nfi-appear 0.3s ease forwards, nfi-fade 0.4s ease 1.9s forwards;
}
.nfi-bar {
    position: absolute;
    background: #E50914;
    border-radius: 3px;
    animation: nfi-grow 0.5s cubic-bezier(0.25,0.46,0.45,0.94) forwards;
}
.nfi-bar--left {
    left: 0; top: 0; width: 14px; height: 0;
    animation-delay: 0.2s;
    animation: nfi-grow-v 0.4s ease 0.2s forwards;
}
.nfi-bar--right {
    right: 0; top: 0; width: 14px; height: 0;
    animation: nfi-grow-v 0.4s ease 0.4s forwards;
}
.nfi-bar--diag {
    left: 14px; top: 0;
    width: 0; height: 14px;
    transform: skewX(-20deg) scaleX(0);
    transform-origin: left center;
    animation: nfi-grow-d 0.3s ease 0.6s forwards;
}
@keyframes nfi-appear {
    from { opacity: 0; transform: scale(0.8); }
    to   { opacity: 1; transform: scale(1); }
}
@keyframes nfi-grow-v {
    from { height: 0; }
    to   { height: 120px; }
}
@keyframes nfi-grow-d {
    from { width: 0; transform: skewX(-20deg) scaleX(0); }
    to   { width: 52px; transform: skewX(-20deg) scaleX(1); }
}
@keyframes nfi-fade {
    from { opacity: 1; }
    to   { opacity: 0; }
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/netflix/NetflixIntro.vue
git commit -m "feat(netflix): implement NetflixIntro with N animation and ta-dum"
```

---

## Task 4: Implement NetflixCover.vue

**Files:**
- Modify: `resources/js/Components/invitation/templates/netflix/NetflixCover.vue`

- [ ] **Step 1: Implement cover/hero screen**

```vue
<script setup>
defineProps({
    coverUrl:     { type: String,  default: null },
    groomNick:    { type: String,  default: '' },
    brideNick:    { type: String,  default: '' },
    subtitle:     { type: String,  default: 'Sebuah Kisah Cinta' },
    eventDate:    { type: String,  default: '' },
    tags:         { type: Array,   default: () => ['#lovestory', '#romantic'] },
    musicPlaying: { type: Boolean, default: false },
})
const emit = defineEmits(['open', 'toggleMusic'])
</script>

<template>
    <div class="nfc-root">
        <!-- Background -->
        <div
            class="nfc-bg"
            :style="coverUrl
                ? { backgroundImage: `url(${coverUrl})` }
                : { background: '#1a1a1a' }"
        />
        <div class="nfc-overlay"/>

        <!-- Top bar -->
        <div class="nfc-top">
            <span class="nfc-brand">THEDAY</span>
            <div class="nfc-top-actions">
                <button class="nfc-fab" @click.stop="emit('toggleMusic')" aria-label="Toggle musik">
                    {{ musicPlaying ? '🔊' : '🔇' }}
                </button>
            </div>
        </div>

        <!-- Bottom content -->
        <div class="nfc-bottom">
            <div class="nfc-title">{{ groomNick }} &amp; {{ brideNick }}:<br>{{ subtitle }}</div>
            <div class="nfc-meta">
                <span class="nfc-badge">Coming Soon</span>
                <span class="nfc-date">{{ eventDate }}</span>
            </div>
            <div class="nfc-tags">
                <span v-for="tag in tags.slice(0,4)" :key="tag" class="nfc-tag">{{ tag }}</span>
            </div>
            <button class="nfc-play" @click="emit('open')">
                ▶ &nbsp;Buka Undangan
            </button>
        </div>
    </div>
</template>

<style scoped>
.nfc-root {
    position: fixed; inset: 0; z-index: 50;
    font-family: Arial, Helvetica, sans-serif;
    display: flex; flex-direction: column;
    justify-content: space-between;
}
.nfc-bg {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
}
.nfc-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, #141414 30%, transparent 70%);
}
.nfc-top {
    position: relative; z-index: 1;
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 20px 0;
}
.nfc-brand {
    font-size: 28px; font-weight: 900;
    color: #E50914; letter-spacing: -1px;
}
.nfc-top-actions { display: flex; gap: 10px; }
.nfc-fab {
    width: 44px; height: 44px;
    background: #E50914; border: none;
    border-radius: 50%; color: #fff;
    font-size: 18px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.nfc-bottom {
    position: relative; z-index: 1;
    padding: 0 20px 48px;
    display: flex; flex-direction: column; gap: 12px;
}
.nfc-title {
    font-size: 28px; font-weight: 700;
    color: #fff; line-height: 1.2;
}
.nfc-meta { display: flex; align-items: center; gap: 12px; }
.nfc-badge {
    background: #E50914; color: #fff;
    padding: 4px 12px; border-radius: 999px;
    font-size: 13px; font-weight: 600;
}
.nfc-date { color: #fff; font-size: 15px; }
.nfc-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.nfc-tag {
    background: rgba(255,255,255,0.12);
    color: #bcbcbc; padding: 4px 12px;
    border-radius: 999px; font-size: 12px;
}
.nfc-play {
    margin-top: 8px;
    background: transparent; border: none;
    color: #fff; font-size: 22px; font-weight: 700;
    cursor: pointer; text-align: left; padding: 0;
    letter-spacing: 1px;
}
.nfc-play:active { opacity: 0.7; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/netflix/NetflixCover.vue
git commit -m "feat(netflix): implement NetflixCover phase 2"
```

---

## Task 5: Implement NetflixHero.vue

**Files:**
- Modify: `resources/js/Components/invitation/templates/netflix/NetflixHero.vue`

- [ ] **Step 1: Implement series detail / synopsis section**

```vue
<script setup>
import { computed } from 'vue'

const props = defineProps({
    coverUrl:    { type: String, default: null },
    groomName:   { type: String, default: '' },
    brideName:   { type: String, default: '' },
    subtitle:    { type: String, default: 'Sebuah Kisah Cinta' },
    eventDate:   { type: String, default: '' },
    heroQuote:   { type: String, default: '' },
    openingText: { type: String, default: '' },
    quoteText:   { type: String, default: '' },
})

const year = computed(() => {
    if (!props.eventDate) return new Date().getFullYear()
    return props.eventDate.slice(0, 4) || new Date().getFullYear()
})
</script>

<template>
    <section class="nfh-root">
        <!-- Cinematic photo top half -->
        <div class="nfh-photo-wrap">
            <div
                class="nfh-photo"
                :style="coverUrl ? { backgroundImage: `url(${coverUrl})` } : { background: '#222' }"
            />
            <div class="nfh-photo-overlay"/>
            <p v-if="heroQuote" class="nfh-subtitle-text">{{ heroQuote }}</p>
        </div>

        <!-- Detail below -->
        <div class="nfh-detail">
            <div class="nfh-label">
                <span class="nfh-n">N</span>
                <span class="nfh-genre">DOCUMENTER</span>
            </div>
            <h2 class="nfh-title">{{ groomName }} &amp; {{ brideName }}: {{ subtitle }}</h2>
            <div class="nfh-meta">
                <span class="nfh-match">100% match</span>
                <span class="nfh-badge-pill">SU</span>
                <span class="nfh-muted">{{ year }}</span>
                <span class="nfh-badge-pill">4K</span>
                <span class="nfh-badge-pill">HD</span>
            </div>
            <div v-if="eventDate" class="nfh-coming">Coming soon on {{ eventDate }}</div>
            <p v-if="openingText" class="nfh-synopsis">{{ openingText }}</p>
            <p v-if="quoteText" class="nfh-quote">{{ quoteText }}</p>
        </div>
    </section>
</template>

<style scoped>
.nfh-root { background: #141414; font-family: Arial, Helvetica, sans-serif; }
.nfh-photo-wrap { position: relative; height: 50vh; overflow: hidden; }
.nfh-photo {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    filter: brightness(0.6);
}
.nfh-photo-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, transparent 40%, #141414 100%);
}
.nfh-subtitle-text {
    position: absolute; bottom: 20px; left: 20px; right: 20px;
    color: rgba(255,255,255,0.85); font-size: 14px;
    background: rgba(0,0,0,0.5); padding: 6px 10px;
    border-left: 3px solid #fff; font-style: italic;
    margin: 0;
}
.nfh-detail { padding: 20px 20px 32px; }
.nfh-label { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.nfh-n {
    background: #E50914; color: #fff;
    font-weight: 900; font-size: 14px;
    padding: 2px 6px;
}
.nfh-genre {
    color: #fff; font-size: 12px;
    letter-spacing: 3px; font-weight: 500;
}
.nfh-title {
    color: #fff; font-size: 22px; font-weight: 700;
    margin: 0 0 12px; line-height: 1.3;
}
.nfh-meta {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; margin-bottom: 16px;
}
.nfh-match { color: #46D369; font-weight: 700; font-size: 14px; }
.nfh-muted { color: #bcbcbc; font-size: 13px; }
.nfh-badge-pill {
    border: 1px solid #bcbcbc; color: #bcbcbc;
    font-size: 11px; padding: 2px 6px; border-radius: 3px;
}
.nfh-coming {
    background: #E50914; color: #fff;
    padding: 12px 20px; border-radius: 4px;
    font-weight: 700; font-size: 14px; margin-bottom: 20px;
    text-align: center;
}
.nfh-synopsis {
    color: #fff; font-size: 16px; line-height: 1.6;
    margin: 0 0 16px;
}
.nfh-quote {
    color: #808080; font-size: 14px;
    font-style: italic; line-height: 1.6; margin: 0;
    border-left: 3px solid #333; padding-left: 12px;
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/netflix/NetflixHero.vue
git commit -m "feat(netflix): implement NetflixHero detail/synopsis section"
```

---

## Task 6: Implement NetflixTemplate.vue — shell + phase management + Breaking News + Bride & Groom

**Files:**
- Create: `resources/js/Components/invitation/templates/NetflixTemplate.vue`

- [ ] **Step 1: Build main orchestrator with phase management and first two content sections**

Create `resources/js/Components/invitation/templates/NetflixTemplate.vue`:

```vue
<script setup>
import { ref, computed, onMounted } from 'vue'
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
    primary,
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
const cfg            = computed(() => props.invitation.config ?? {})
const netflixSubtitle = computed(() => cfg.value.netflix_subtitle ?? 'Sebuah Kisah Cinta')
const netflixTags     = computed(() => cfg.value.netflix_tags ?? ['#lovestory', '#romantic'])
const heroQuote       = computed(() => cfg.value.netflix_hero_quote ?? sectionData('quote').text ?? '')

// ── Guest name for WhoWatching ────────────────────────────────────────────────
const guestName = computed(() => {
    if (props.isDemo) return 'Tamu Undangan'
    if (props.guest?.name) return props.guest.name
    const key = 'to'
    const params = new URLSearchParams(window.location.search)
    const raw = params.get(key) ?? ''
    return decodeURIComponent(raw).replace(/\+/g, ' ').trim() || 'Tamu Undangan'
})

// ── Phase management ──────────────────────────────────────────────────────────
// Phases: 'who-watching' | 'intro' | 'cover' | 'content'
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
const groomPhoto    = computed(() => details.value.groom_photo_url    ?? null)
const bridePhoto    = computed(() => details.value.bride_photo_url    ?? null)
const groomParents  = computed(() => details.value.groom_parents_text ?? '')
const brideParents  = computed(() => details.value.bride_parents_text ?? '')

// ── Love story ────────────────────────────────────────────────────────────────
const loveStories = computed(() => sectionData('love_story').stories ?? [])

// ── RSVP section scroll target ────────────────────────────────────────────────
const rsvpRef = ref(null)
function scrollToRsvp() {
    rsvpRef.value?.scrollIntoView({ behavior: 'smooth' })
}

// ── First event display ───────────────────────────────────────────────────────
const firstEvent = computed(() => events.value[0] ?? null)
const eventDateForHero = computed(() => {
    if (!firstEvent.value) return ''
    const d = firstEvent.value.event_date_formatted ?? firstEvent.value.event_date ?? ''
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

            <!-- ── Hero / Detail page ── -->
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

            <!-- ── Breaking News (Opening) ── -->
            <section v-if="sectionEnabled('opening')" class="nf-section">
                <h2 class="nf-section-title">BREAKING NEWS</h2>
                <img
                    v-if="coverPhotoUrl"
                    :src="coverPhotoUrl" alt=""
                    class="nf-full-img"
                />
                <p class="nf-body-text">{{ openingText }}</p>
            </section>

            <!-- ── Bride & Groom ── -->
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

            <!-- ── Timeline & Location ── -->
            <section v-if="sectionEnabled('events') && events.length" class="nf-section">
                <h2 class="nf-section-title">TIMELINE &amp; LOCATION</h2>
                <div v-for="event in events" :key="event.id" class="nf-event-card">
                    <img v-if="coverPhotoUrl" :src="coverPhotoUrl" class="nf-event-thumb" alt=""/>
                    <div class="nf-event-body">
                        <span class="nf-event-badge">{{ event.event_name }}</span>
                        <p class="nf-event-date">{{ event.event_date_formatted }}</p>
                        <div class="nf-event-chips">
                            <span v-if="event.start_time" class="nf-chip">{{ event.start_time }}<span v-if="event.end_time"> s.d {{ event.end_time }}</span></span>
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

            <!-- ── Countdown ── -->
            <section v-if="sectionEnabled('countdown') && targetDate && countdown.days >= 0" class="nf-section">
                <div class="nf-countdown">
                    <div v-for="(val, label) in { Hari: countdown.days, Jam: countdown.hours, Menit: countdown.minutes, Detik: countdown.seconds }" :key="label" class="nf-cd-unit">
                        <span class="nf-cd-num">{{ pad(val) }}</span>
                        <span class="nf-cd-label">{{ label }}</span>
                    </div>
                </div>
            </section>

            <!-- ── Our Love Story ── -->
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

            <!-- ── Gallery ── -->
            <section v-if="sectionEnabled('gallery') && galleries.length" class="nf-section">
                <h2 class="nf-section-title">GALLERY</h2>
                <div class="nf-gallery-grid">
                    <img
                        v-for="img in galleries" :key="img.id"
                        :src="img.file_url" :alt="img.caption ?? ''"
                        class="nf-gallery-img"
                        loading="lazy"
                        @click="lightboxUrl = img.file_url"
                    />
                </div>
            </section>

            <!-- ── RSVP ── -->
            <section v-if="sectionEnabled('rsvp')" ref="rsvpRef" class="nf-section">
                <h2 class="nf-section-title">KONFIRMASI KEHADIRAN</h2>
                <form class="nf-form" @submit.prevent="submitRsvp">
                    <input v-model="rsvpForm.guest_name" class="nf-input" placeholder="Nama lengkap" required />
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

            <!-- ── Gift / Rekening ── -->
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

            <!-- ── Wishes / Buku Tamu ── -->
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

            <!-- ── Closing ── -->
            <section v-if="sectionEnabled('closing')" class="nf-section nf-closing">
                <h2 class="nf-closing-names">{{ groomName }} &amp; {{ brideName }}</h2>
                <p class="nf-body-text nf-closing-text">{{ closingText }}</p>
                <p class="nf-closing-brand">THEDAY</p>
            </section>

        </template>

        <!-- Floating music button (content phase only) -->
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
/* ── Sections ── */
.nf-section { padding: 32px 20px; border-bottom: 1px solid #1F1F1F; }
.nf-section-title {
    font-size: 28px; font-weight: 900;
    color: #fff; margin: 0 0 20px;
    letter-spacing: -0.5px;
}

/* ── Typography ── */
.nf-body-text { color: #fff; font-size: 16px; line-height: 1.7; margin: 12px 0 0; }

/* ── Images ── */
.nf-full-img { width: 100%; border-radius: 6px; margin: 16px 0 0; display: block; object-fit: cover; max-height: 300px; }

/* ── Couple ── */
.nf-couple-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.nf-person { display: flex; flex-direction: column; gap: 8px; }
.nf-portrait { width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 6px; display: block; }
.nf-portrait--placeholder { background: #2D2D2D; }
.nf-person-name { color: #fff; font-weight: 700; font-size: 15px; margin: 0; }
.nf-person-parents { color: #bcbcbc; font-size: 13px; line-height: 1.4; margin: 0; }

/* ── Events ── */
.nf-event-card { display: flex; gap: 14px; margin-bottom: 24px; }
.nf-event-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.nf-event-body { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.nf-event-badge { background: #E50914; color: #fff; font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 999px; align-self: flex-start; }
.nf-event-date { color: #fff; font-weight: 700; font-size: 16px; margin: 0; }
.nf-event-chips { display: flex; gap: 6px; flex-wrap: wrap; }
.nf-chip { background: #333; color: #bcbcbc; font-size: 12px; padding: 3px 10px; border-radius: 999px; }
.nf-event-address { color: #bcbcbc; font-size: 13px; margin: 0; line-height: 1.4; }
.nf-maps-link { color: #E50914; font-size: 13px; font-weight: 600; text-decoration: none; }

/* ── CTA button ── */
.nf-cta-btn {
    width: 100%; padding: 16px;
    background: #E50914; border: none;
    color: #fff; font-size: 14px; font-weight: 700;
    letter-spacing: 2px; border-radius: 4px;
    cursor: pointer; margin-top: 16px;
    transition: background 0.2s;
}
.nf-cta-btn:hover  { background: #831010; }
.nf-cta-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* ── Countdown ── */
.nf-countdown { display: flex; justify-content: center; gap: 24px; padding: 20px 0; }
.nf-cd-unit { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.nf-cd-num { font-size: 40px; font-weight: 900; color: #fff; font-variant-numeric: tabular-nums; }
.nf-cd-label { font-size: 12px; color: #bcbcbc; letter-spacing: 1px; text-transform: uppercase; }

/* ── Love story ── */
.nf-episode { margin-bottom: 28px; }
.nf-episode-top { display: flex; gap: 14px; margin-bottom: 10px; }
.nf-episode-thumb { width: 90px; height: 90px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.nf-episode-thumb--ph { background: #2D2D2D; }
.nf-episode-meta { flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 4px; }
.nf-episode-label { color: #fff; font-weight: 700; font-size: 15px; margin: 0; line-height: 1.3; }
.nf-episode-year  { color: #bcbcbc; font-size: 13px; margin: 0; }
.nf-episode-desc  { color: #fff; font-size: 15px; line-height: 1.6; margin: 0; }

/* ── Gallery ── */
.nf-gallery-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.nf-gallery-img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; cursor: pointer; display: block; }

/* ── Forms ── */
.nf-form { display: flex; flex-direction: column; gap: 12px; }
.nf-input {
    background: #333; border: 1px solid #8C8C8C;
    color: #fff; padding: 14px 16px;
    font-size: 16px; border-radius: 4px;
    font-family: inherit; outline: none; width: 100%; box-sizing: border-box;
}
.nf-input:focus { border-color: #fff; }
.nf-textarea { min-height: 100px; resize: vertical; }
.nf-error   { color: #E50914; font-size: 14px; margin: 0; }
.nf-success { color: #46D369; font-size: 14px; margin: 0; }

/* ── Gift / Rekening ── */
.nf-account-card {
    background: #1F1F1F; border-radius: 6px;
    padding: 16px 20px; margin-bottom: 12px;
    display: flex; flex-direction: column; gap: 4px;
}
.nf-account-bank { color: #bcbcbc; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; margin: 0; }
.nf-account-name { color: #fff; font-weight: 700; font-size: 16px; margin: 0; }
.nf-account-num  { color: #fff; font-size: 20px; font-weight: 900; letter-spacing: 2px; margin: 0; }
.nf-copy-btn {
    align-self: flex-start; margin-top: 8px;
    background: transparent; border: 2px solid #E50914;
    color: #E50914; padding: 8px 20px;
    font-size: 12px; font-weight: 700; letter-spacing: 2px;
    border-radius: 4px; cursor: pointer;
    transition: all 0.2s;
}
.nf-copy-btn:hover { background: #E50914; color: #fff; }

/* ── Wishes ── */
.nf-wish-item { padding: 16px 0; border-bottom: 1px solid #222; }
.nf-wish-name { color: #fff; font-weight: 700; font-size: 14px; margin: 0 0 4px; }
.nf-wish-msg  { color: #bcbcbc; font-size: 14px; line-height: 1.5; margin: 0; }

/* ── Closing ── */
.nf-closing { text-align: center; padding-bottom: 64px; }
.nf-closing-names { color: #fff; font-size: 26px; font-weight: 700; margin: 0 0 16px; }
.nf-closing-text { text-align: center; color: #808080; }
.nf-closing-brand {
    margin-top: 32px; font-size: 24px; font-weight: 900;
    color: #333; letter-spacing: -1px;
}

/* ── Floating music ── */
.nf-float-music {
    position: fixed; bottom: 16px; right: 16px; z-index: 40;
    width: 48px; height: 48px;
    background: #E50914; border: none;
    border-radius: 50%; color: #fff;
    font-size: 20px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

/* ── Lightbox ── */
.nf-lightbox {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,0.9);
    display: flex; align-items: center; justify-content: center;
    cursor: zoom-out;
}
.nf-lightbox-img { max-width: 95vw; max-height: 90vh; object-fit: contain; border-radius: 4px; }

/* ── Toast ── */
.nf-toast {
    position: fixed; bottom: 80px; left: 50%;
    transform: translateX(-50%);
    background: #2D2D2D; color: #fff;
    padding: 10px 20px; border-radius: 8px;
    font-size: 14px; z-index: 50;
    white-space: nowrap; box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}
.nf-toast-enter-active, .nf-toast-leave-active { transition: opacity 0.3s; }
.nf-toast-enter-from, .nf-toast-leave-to { opacity: 0; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/NetflixTemplate.vue
git commit -m "feat(netflix): implement NetflixTemplate main orchestrator with all content sections"
```

---

## Task 7: Register template in registry.js

**Files:**
- Modify: `resources/js/Components/invitation/templates/registry.js`

- [ ] **Step 1: Add netflix entry**

In `resources/js/Components/invitation/templates/registry.js`, add import and entry:

```js
// resources/js/Components/invitation/templates/registry.js
import NusantaraTemplate from './NusantaraTemplate.vue'
import PearlTemplate     from './PearlTemplate.vue'
import BeachTemplate     from './BeachTemplate.vue'
import GardenTemplate    from './GardenTemplate.vue'
import NightSkyTemplate  from './NightSkyTemplate.vue'
import NetflixTemplate   from './NetflixTemplate.vue'

export const TEMPLATE_MAP = {
    'nusantara': NusantaraTemplate,
    'pearl':     PearlTemplate,
    'beach':     BeachTemplate,
    'garden':    GardenTemplate,
    'night-sky': NightSkyTemplate,
    'netflix':   NetflixTemplate,
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/registry.js
git commit -m "feat(netflix): register 'netflix' template in registry"
```

---

## Task 8: Smoke test via demo page

**Files:**
- Read: `resources/js/Pages/Templates/Demo.vue` (verify how template key is passed)

- [ ] **Step 1: Check how Demo.vue loads templates**

Open `resources/js/Pages/Templates/Demo.vue` and verify `TEMPLATE_MAP` is used and the template key is settable. Identify how to switch to `'netflix'` for preview.

- [ ] **Step 2: Run dev server**

```bash
npm run dev
```

- [ ] **Step 3: Open demo in browser**

Navigate to the demo route (check `routes/web.php` for the demo URL — typically `/demo` or `/templates/demo`).

Set template key to `netflix` (via URL param or UI toggle depending on how Demo.vue works).

Verify the 4 phases load in sequence:
1. "Who's watching?" screen appears with THEDAY logo
2. Clicking "OPEN INVITATION" → N animation plays with sound
3. Cover screen appears with ▶ button
4. Clicking ▶ → content scrolls with all sections

- [ ] **Step 4: Fix any console errors before proceeding**

Open browser console. If any `[Vue warn]` or JS errors appear, fix them before committing.

- [ ] **Step 5: Commit fix (if any)**

```bash
git add -p   # stage only the fix
git commit -m "fix(netflix): resolve demo smoke test errors"
```

---

## Task 9: Final polish + .gitignore for brainstorm files

**Files:**
- Modify: `.gitignore`

- [ ] **Step 1: Add .superpowers/ to .gitignore if not already present**

Open `.gitignore` and add if missing:
```
.superpowers/
```

- [ ] **Step 2: Commit**

```bash
git add .gitignore
git commit -m "chore: ignore .superpowers brainstorm directory"
```

---

## Self-Review Notes

**Spec coverage:**
- ✅ Phase 0 WhoWatching — Task 2
- ✅ Phase 1 Intro (N anim + ta-dum) — Task 3
- ✅ Phase 2 Cover (THEDAY brand, tags, Coming Soon badge) — Task 4
- ✅ Phase 3 Hero (N DOCUMENTER, 100% match, synopsis, quote) — Task 5
- ✅ Breaking News section — Task 6
- ✅ Bride & Groom section — Task 6
- ✅ Timeline & Location + scroll-to-RSVP CTA — Task 6
- ✅ Countdown — Task 6
- ✅ Our Love Story (episode style) — Task 6
- ✅ Gallery (2-col grid + lightbox) — Task 6
- ✅ RSVP form — Task 6
- ✅ Gift/Rekening with copy — Task 6
- ✅ Wishes/Buku Tamu — Task 6
- ✅ Closing + THEDAY brand — Task 6
- ✅ Music float button — Task 6
- ✅ registry.js — Task 7
- ✅ netflix_subtitle, netflix_tags, netflix_hero_quote config fields — used in Task 6
