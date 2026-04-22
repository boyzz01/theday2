# Template System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Buat template registry tunggal, composable `useInvitationTemplate`, scaffold script, dan refactor NusantaraTemplate sebagai referensi — sehingga menambah template baru cukup 3 langkah: scaffold, convert HTML, insert DB record.

**Architecture:** `registry.js` jadi single source of truth untuk `TEMPLATE_MAP`. Semua shared logic (theme, sections, RSVP, messages, countdown, music, dll) diekstrak ke composable `useInvitationTemplate(props, defaults)`. NusantaraTemplate direfactor sebagai referensi implementasi yang benar. Scaffold script `make-template.js` otomatis buat file + register di registry.

**Tech Stack:** Vue 3 Composition API, Vite, TailwindCSS, Node.js (ESM) untuk scaffold script.

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| **Create** | `resources/js/Components/invitation/templates/registry.js` | Single source of truth untuk TEMPLATE_MAP |
| **Create** | `resources/js/Composables/useInvitationTemplate.js` | Semua shared logic: theme, data, section, RSVP, messages, music, countdown |
| **Create** | `resources/js/Components/invitation/templates/_template-boilerplate.vue` | Base file yang di-copy scaffold script |
| **Create** | `scripts/make-template.js` | Scaffold script: buat .vue + register di registry |
| **Modify** | `resources/js/Components/invitation/InvitationRenderer.vue` | Import TEMPLATE_MAP dari registry (hapus definisi lokal) |
| **Modify** | `resources/js/Components/templates/TemplatePreviewModal.vue` | Import TEMPLATE_MAP dari registry (hapus definisi lokal) |
| **Modify** | `resources/js/Components/invitation/templates/NusantaraTemplate.vue` | Refactor: pakai composable, hapus semua logic duplikat |
| **Modify** | `package.json` | Tambah script `make:template` |

---

## Task 1: Buat `registry.js`

**Files:**
- Create: `resources/js/Components/invitation/templates/registry.js`

- [ ] **Step 1: Buat file registry**

```js
// resources/js/Components/invitation/templates/registry.js
import NusantaraTemplate from './NusantaraTemplate.vue'

export const TEMPLATE_MAP = {
    'nusantara': NusantaraTemplate,
}
```

- [ ] **Step 2: Verifikasi file ada**

```bash
ls resources/js/Components/invitation/templates/registry.js
```

Expected: file terdaftar

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/invitation/templates/registry.js
git commit -m "feat: add template registry as single source of truth"
```

---

## Task 2: Refactor `InvitationRenderer.vue` — import dari registry

**Files:**
- Modify: `resources/js/Components/invitation/InvitationRenderer.vue`

- [ ] **Step 1: Ganti import dan TEMPLATE_MAP lokal**

Hapus baris-baris ini dari `<script setup>`:
```js
// ── Premium template imports ───────────────────────────────────────────────
import NusantaraTemplate from '@/Components/invitation/templates/NusantaraTemplate.vue';

// Template routing map: slug → component
const TEMPLATE_MAP = {
    'nusantara': NusantaraTemplate,
};
```

Ganti dengan:
```js
import { TEMPLATE_MAP } from '@/Components/invitation/templates/registry';
```

- [ ] **Step 2: Verifikasi manual**

Buka browser → buka undangan yang pakai template Nusantara → pastikan template masih render dengan benar.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/invitation/InvitationRenderer.vue
git commit -m "refactor: InvitationRenderer import TEMPLATE_MAP from registry"
```

---

## Task 3: Refactor `TemplatePreviewModal.vue` — import dari registry

**Files:**
- Modify: `resources/js/Components/templates/TemplatePreviewModal.vue`

- [ ] **Step 1: Ganti import dan PREMIUM_TEMPLATE_MAP lokal**

Hapus baris-baris ini dari `<script setup>`:
```js
// ── Premium template components ───────────────────────────────────────────────
import NusantaraTemplate from '@/Components/invitation/templates/NusantaraTemplate.vue';

const PREMIUM_TEMPLATE_MAP = {
    'nusantara': NusantaraTemplate,
};
```

Ganti dengan:
```js
import { TEMPLATE_MAP } from '@/Components/invitation/templates/registry';
```

- [ ] **Step 2: Ganti semua referensi `PREMIUM_TEMPLATE_MAP` → `TEMPLATE_MAP`**

Cari dan ganti di file yang sama:
```js
// Sebelum:
const premiumComponent = computed(() =>
    props.template ? (PREMIUM_TEMPLATE_MAP[props.template.slug] ?? null) : null
);

// Sesudah:
const premiumComponent = computed(() =>
    props.template ? (TEMPLATE_MAP[props.template.slug] ?? null) : null
);
```

- [ ] **Step 3: Verifikasi manual**

Buka dashboard → Template Gallery → buka preview modal Nusantara → pastikan preview masih render dengan benar.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/templates/TemplatePreviewModal.vue
git commit -m "refactor: TemplatePreviewModal import TEMPLATE_MAP from registry"
```

---

## Task 4: Buat composable `useInvitationTemplate.js`

**Files:**
- Create: `resources/js/Composables/useInvitationTemplate.js`

- [ ] **Step 1: Buat composable dengan seluruh shared logic**

```js
// resources/js/Composables/useInvitationTemplate.js
import { ref, computed, onMounted, onUnmounted } from 'vue'

export function useInvitationTemplate(props, defaults = {}) {

    // ── Config / theme ────────────────────────────────────────────────────
    const cfg = computed(() => props.invitation.config ?? {})

    const primary      = computed(() => cfg.value.primary_color       ?? '#8B6914')
    const primaryLight = computed(() => cfg.value.primary_color_light ?? '#C9A84C')
    const darkBg       = computed(() => cfg.value.dark_bg             ?? '#2C1810')
    const bgColor      = computed(() => cfg.value.secondary_color     ?? '#F5F0E8')
    const accent       = computed(() => cfg.value.accent_color        ?? '#6B1D1D')
    const fontTitle    = computed(() => cfg.value.font_title          ?? 'Cinzel Decorative')
    const fontHeading  = computed(() => cfg.value.font_heading        ?? 'Cormorant Garamond')
    const fontBody     = computed(() => cfg.value.font_body           ?? 'Crimson Text')

    // ── Section style defaults (config override untuk future use) ─────────
    const galleryLayout = computed(() => cfg.value.gallery_layout ?? defaults.galleryLayout ?? 'vertical')
    const openingStyle  = computed(() => cfg.value.opening_style  ?? defaults.openingStyle  ?? 'gate')
    const revealClass   = defaults.revealClass ?? 'is-visible'

    // ── Invitation data ───────────────────────────────────────────────────
    const details   = computed(() => props.invitation.details   ?? {})
    const events    = computed(() => props.invitation.events    ?? [])
    const galleries = computed(() => props.invitation.galleries ?? [])

    const groomName = computed(() => details.value.groom_name ?? '—')
    const brideName = computed(() => details.value.bride_name ?? '—')
    const groomNick = computed(() => details.value.groom_nickname?.trim() || groomName.value)
    const brideNick = computed(() => details.value.bride_nickname?.trim() || brideName.value)

    const coverPhotoUrl  = computed(() => details.value.cover_photo_url ?? null)
    const coverTextColor = computed(() => coverPhotoUrl.value ? primaryLight.value : primary.value)

    const openingText = computed(() =>
        details.value.opening_text ??
        'Dengan memohon rahmat dan ridho Allah SWT, kami mengundang Bapak/Ibu/Saudara/i untuk hadir di hari istimewa kami.'
    )
    const closingText = computed(() =>
        details.value.closing_text ??
        'Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir. Atas doa restu yang diberikan, kami ucapkan terima kasih.'
    )

    // ── Section visibility ────────────────────────────────────────────────
    const sectionsMap = computed(() => props.invitation.sections ?? null)

    function sectionEnabled(key) {
        if (!sectionsMap.value) return true
        const s = sectionsMap.value[key]
        if (s === undefined || s === null) return true
        if (typeof s === 'boolean') return s
        return s.enabled ?? true
    }

    function sectionData(key) {
        if (!sectionsMap.value) return {}
        const s = sectionsMap.value[key]
        if (!s || typeof s === 'boolean') return {}
        return s.data ?? {}
    }

    // ── Events ────────────────────────────────────────────────────────────
    const firstEvent     = computed(() => events.value[0] ?? null)
    const firstEventDate = computed(() => firstEvent.value?.event_date_formatted ?? '')

    // ── Countdown ─────────────────────────────────────────────────────────
    const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 })
    const targetDate = computed(() => {
        if (!firstEvent.value?.event_date) return null
        const t = firstEvent.value.start_time ? `T${firstEvent.value.start_time}` : 'T00:00'
        return new Date(firstEvent.value.event_date + t)
    })

    let cdTimer
    function updateCountdown() {
        if (!targetDate.value) return
        const diff = targetDate.value - Date.now()
        if (diff <= 0) {
            countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }
            return
        }
        countdown.value = {
            days:    Math.floor(diff / 86400000),
            hours:   Math.floor((diff % 86400000) / 3600000),
            minutes: Math.floor((diff % 3600000) / 60000),
            seconds: Math.floor((diff % 60000) / 1000),
        }
    }

    const pad = n => String(n).padStart(2, '0')

    // ── Gate / opening animation ──────────────────────────────────────────
    const gateOpen      = ref(false)
    const contentOpen   = ref(false)
    const gateAnimating = ref(false)

    // ── Music ─────────────────────────────────────────────────────────────
    const audioEl      = ref(null)
    const musicPlaying = ref(false)

    async function triggerGate() {
        if (gateAnimating.value || gateOpen.value) return
        gateAnimating.value = true
        await new Promise(r => setTimeout(r, 1400))
        gateOpen.value      = true
        contentOpen.value   = true
        gateAnimating.value = false
        if (props.invitation.music?.file_url && audioEl.value) {
            audioEl.value.play().catch(() => {})
            musicPlaying.value = true
        }
    }

    function toggleMusic() {
        if (!audioEl.value) return
        if (musicPlaying.value) {
            audioEl.value.pause()
            musicPlaying.value = false
        } else {
            audioEl.value.play().then(() => { musicPlaying.value = true }).catch(() => {})
        }
    }

    // ── Toast ─────────────────────────────────────────────────────────────
    const toastMsg     = ref('')
    const toastVisible = ref(false)
    let toastTimer

    function showToast(msg) {
        toastMsg.value     = msg
        toastVisible.value = true
        clearTimeout(toastTimer)
        toastTimer = setTimeout(() => { toastVisible.value = false }, 2500)
    }

    // ── Gift: copy rekening ───────────────────────────────────────────────
    const copiedAccount = ref(null)

    async function copyToClipboard(text) {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text)
            } else {
                const el = Object.assign(document.createElement('textarea'), {
                    value: text,
                    style: 'position:fixed;opacity:0;top:0;left:0',
                })
                document.body.appendChild(el)
                el.focus()
                el.select()
                document.execCommand('copy')
                document.body.removeChild(el)
            }
            copiedAccount.value = text
            showToast('Nomor rekening berhasil disalin ✓')
            setTimeout(() => { copiedAccount.value = null }, 2500)
        } catch {
            showToast('Gagal menyalin — salin manual ya')
        }
    }

    // ── Guest messages ────────────────────────────────────────────────────
    const localMessages = ref([...(props.messages ?? [])])
    const msgForm       = ref({ name: '', message: '' })
    const msgSubmitting = ref(false)
    const msgSuccess    = ref(false)
    const msgError      = ref('')

    async function submitMessage() {
        if (props.isDemo) { msgError.value = 'Form tidak aktif di halaman demo.'; return }
        if (!msgForm.value.name.trim() || !msgForm.value.message.trim()) {
            msgError.value = 'Nama dan ucapan wajib diisi.'
            return
        }
        msgSubmitting.value = true
        msgError.value = ''
        try {
            const res = await fetch(`/${props.invitation.slug}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(msgForm.value),
            })
            const data = await res.json()
            if (!res.ok) throw new Error(data.message ?? 'Gagal mengirim ucapan.')
            localMessages.value.unshift(data.data)
            msgForm.value = { name: '', message: '' }
            msgSuccess.value = true
            setTimeout(() => { msgSuccess.value = false }, 4000)
        } catch (e) {
            msgError.value = e.message
        } finally {
            msgSubmitting.value = false
        }
    }

    // ── RSVP ─────────────────────────────────────────────────────────────
    const rsvpForm = ref({ guest_name: '', attendance: '', guest_count: 1, notes: '' })
    const rsvpSubmitting = ref(false)
    const rsvpSuccess    = ref(false)
    const rsvpError      = ref('')

    async function submitRsvp() {
        if (props.isDemo) { rsvpError.value = 'Form tidak aktif di halaman demo.'; return }
        if (!rsvpForm.value.guest_name.trim() || !rsvpForm.value.attendance) {
            rsvpError.value = 'Nama dan konfirmasi kehadiran wajib diisi.'
            return
        }
        rsvpSubmitting.value = true
        rsvpError.value = ''
        try {
            const res = await fetch(`/${props.invitation.slug}/rsvp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(rsvpForm.value),
            })
            const data = await res.json()
            if (!res.ok) throw new Error(data.message ?? 'Gagal mengirim RSVP.')
            rsvpSuccess.value = true
        } catch (e) {
            rsvpError.value = e.message
        } finally {
            rsvpSubmitting.value = false
        }
    }

    // ── Video embed ───────────────────────────────────────────────────────
    function videoEmbedUrl(url) {
        if (!url) return null
        let m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?/\s]+)/)
        if (m) return `https://www.youtube.com/embed/${m[1]}?rel=0`
        m = url.match(/vimeo\.com\/(\d+)/)
        if (m) return `https://player.vimeo.com/video/${m[1]}`
        return null
    }

    // ── Intersection reveal ───────────────────────────────────────────────
    function vReveal(el) {
        if (!el) return
        const obs = new IntersectionObserver(([e]) => {
            if (e.isIntersecting) { el.classList.add(revealClass); obs.disconnect() }
        }, { threshold: 0.12 })
        obs.observe(el)
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────
    onMounted(() => {
        if (props.autoOpen || props.isDemo) {
            gateOpen.value    = true
            contentOpen.value = true
        }
        updateCountdown()
        cdTimer = setInterval(updateCountdown, 1000)
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            gateOpen.value    = true
            contentOpen.value = true
        }
    })

    onUnmounted(() => {
        clearInterval(cdTimer)
        clearTimeout(toastTimer)
    })

    return {
        // Theme
        cfg, primary, primaryLight, darkBg, bgColor, accent,
        fontTitle, fontHeading, fontBody,
        galleryLayout, openingStyle,
        // Data
        details, events, galleries,
        groomName, brideName, groomNick, brideNick,
        coverPhotoUrl, coverTextColor,
        openingText, closingText,
        firstEvent, firstEventDate,
        // Section
        sectionEnabled, sectionData,
        // Countdown
        countdown, targetDate, pad,
        // Gate
        gateOpen, contentOpen, gateAnimating, triggerGate,
        // Music
        audioEl, musicPlaying, toggleMusic,
        // Toast
        toastMsg, toastVisible, showToast,
        // Gift
        copiedAccount, copyToClipboard,
        // Messages
        localMessages, msgForm, msgSubmitting, msgSuccess, msgError, submitMessage,
        // RSVP
        rsvpForm, rsvpSubmitting, rsvpSuccess, rsvpError, submitRsvp,
        // Utils
        videoEmbedUrl, vReveal,
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Composables/useInvitationTemplate.js
git commit -m "feat: add useInvitationTemplate composable"
```

---

## Task 5: Refactor `NusantaraTemplate.vue` pakai composable

**Files:**
- Modify: `resources/js/Components/invitation/templates/NusantaraTemplate.vue`

Seluruh `<script setup>` (baris 1–289) diganti. Bagian `<template>` dan `<style>` **tidak disentuh**.

- [ ] **Step 1: Ganti seluruh `<script setup>` dengan versi yang pakai composable**

```vue
<script setup>
import { onMounted } from 'vue';
import { useInvitationTemplate } from '@/Composables/useInvitationTemplate';
import BatikKawung    from '@/Components/invitation/ornaments/BatikKawung.vue';
import SulurDivider   from '@/Components/invitation/ornaments/SulurDivider.vue';
import MandalaBg      from '@/Components/invitation/ornaments/MandalaBg.vue';
import JavaneseGate   from '@/Components/invitation/ornaments/JavaneseGate.vue';
import WayangBorder   from '@/Components/invitation/ornaments/WayangBorder.vue';
import SectionGallery from '@/Pages/Invitation/Sections/SectionGallery.vue';

const props = defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
});

const {
    primary, primaryLight, darkBg, bgColor, accent,
    fontTitle, fontHeading, fontBody,
    groomName, brideName, groomNick, brideNick,
    coverPhotoUrl, coverTextColor,
    details, events, galleries,
    sectionEnabled, sectionData,
    openingText, closingText,
    firstEvent, firstEventDate,
    countdown, targetDate, pad,
    gateOpen, gateAnimating, triggerGate,
    audioEl, musicPlaying, toggleMusic,
    toastMsg, toastVisible,
    copiedAccount, copyToClipboard,
    localMessages, msgForm, msgSubmitting, msgSuccess, msgError, submitMessage,
    rsvpForm, rsvpSubmitting, rsvpSuccess, rsvpError, submitRsvp,
    videoEmbedUrl, vReveal,
} = useInvitationTemplate(props, {
    galleryLayout: 'grid',
    openingStyle:  'gate',
    revealClass:   'n-visible',
});

// Nusantara-specific: inject Google Fonts
onMounted(() => {
    const fontParam = [
        'Cinzel+Decorative:wght@400;700',
        'Cormorant+Garamond:ital,wght@0,400;0,600;1,400',
        'Crimson+Text:ital,wght@0,400;0,600;1,400',
    ].join('&family=');
    const link = Object.assign(document.createElement('link'), {
        rel:  'stylesheet',
        href: `https://fonts.googleapis.com/css2?family=${fontParam}&display=swap`,
    });
    document.head.appendChild(link);
});
</script>
```

- [ ] **Step 2: Verifikasi browser**

- Jalankan `npm run dev`
- Buka `/templates/nusantara/demo`
- Checklist:
  - [ ] Gate animation muncul dan terbuka saat diklik
  - [ ] Nama mempelai tampil dari demo_data
  - [ ] Semua section tampil: cover, opening, events, gallery, rsvp, wishes, closing
  - [ ] Countdown berjalan
  - [ ] Tidak ada error di browser console

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/invitation/templates/NusantaraTemplate.vue
git commit -m "refactor: NusantaraTemplate use useInvitationTemplate composable"
```

---

## Task 6: Buat `_template-boilerplate.vue`

**Files:**
- Create: `resources/js/Components/invitation/templates/_template-boilerplate.vue`

File ini di-copy scaffold script untuk template baru. Underscore prefix mencegahnya terdaftar secara tidak sengaja.

- [ ] **Step 1: Buat boilerplate**

```vue
<script setup>
import { onMounted } from 'vue';
import { useInvitationTemplate } from '@/Composables/useInvitationTemplate';

const props = defineProps({
    invitation: { type: Object,  required: true },
    messages:   { type: Array,   default: () => [] },
    guest:      { type: Object,  default: null },
    isDemo:     { type: Boolean, default: false },
    autoOpen:   { type: Boolean, default: false },
});

const {
    primary, primaryLight, darkBg, bgColor, accent,
    fontTitle, fontHeading, fontBody,
    groomName, brideName, groomNick, brideNick,
    coverPhotoUrl, coverTextColor,
    details, events, galleries,
    sectionEnabled, sectionData,
    openingText, closingText,
    firstEvent, firstEventDate,
    countdown, targetDate, pad,
    gateOpen, gateAnimating, triggerGate,
    audioEl, musicPlaying, toggleMusic,
    toastMsg, toastVisible,
    copiedAccount, copyToClipboard,
    localMessages, msgForm, msgSubmitting, msgSuccess, msgError, submitMessage,
    rsvpForm, rsvpSubmitting, rsvpSuccess, rsvpError, submitRsvp,
    videoEmbedUrl, vReveal,
} = useInvitationTemplate(props, {
    galleryLayout: 'vertical',  // ganti: 'vertical' | 'horizontal' | 'grid' | 'masonry'
    openingStyle:  'fade',      // ganti: 'fade' | 'gate' | 'slide'
    revealClass:   'is-visible',
});

// Inject Google Fonts untuk template ini
onMounted(() => {
    // Ganti dengan font yang dipakai template ini
    // const link = Object.assign(document.createElement('link'), {
    //     rel:  'stylesheet',
    //     href: 'https://fonts.googleapis.com/css2?family=...',
    // });
    // document.head.appendChild(link);
});
</script>

<template>
    <div>
        <!-- ── Audio ── -->
        <audio
            v-if="invitation.music?.file_url && sectionEnabled('music')"
            ref="audioEl"
            :src="invitation.music.file_url"
            loop preload="none"
            class="sr-only"
        />

        <!-- ── Cover / Opening screen ── -->
        <!-- Ganti dengan cover visual sesuai desain HTML -->
        <div
            v-if="!gateOpen"
            @click="triggerGate"
            style="min-height:100vh;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;cursor:pointer"
            :style="{ background: bgColor }"
        >
            <h1 :style="{ fontFamily: fontTitle, color: primary }">
                {{ groomNick }} &amp; {{ brideNick }}
            </h1>
            <p v-if="firstEventDate" :style="{ color: accent, fontFamily: fontHeading }">
                {{ firstEventDate }}
            </p>
            <p v-if="guest?.name" :style="{ fontFamily: fontHeading }">
                Kepada Yth. {{ guest.name }}
            </p>
            <button
                @click.stop="triggerGate"
                :style="{ background: primary, color: '#fff', padding: '12px 32px', border: 'none', borderRadius: '8px', fontFamily: fontHeading, cursor: 'pointer' }"
            >
                Buka Undangan
            </button>
        </div>

        <!-- ── Main content (setelah gate terbuka) ── -->
        <div v-if="gateOpen">

            <!-- Music float button -->
            <button
                v-if="sectionEnabled('music') && invitation.music?.file_url"
                @click="toggleMusic"
                :style="{ position:'fixed', bottom:'24px', right:'16px', zIndex:40, background:primary, color:'#fff', border:'none', borderRadius:'50%', width:'48px', height:'48px', cursor:'pointer', fontSize:'18px' }"
                aria-label="Toggle musik"
            >♪</button>

            <!-- Cover section -->
            <section v-if="sectionEnabled('cover')">
                <!-- TODO: implementasi cover visual dari HTML -->
            </section>

            <!-- Opening / Sambutan -->
            <section v-if="sectionEnabled('opening')">
                <!-- TODO: implementasi opening dari HTML -->
                <p>{{ openingText }}</p>
            </section>

            <!-- Quote / Ayat -->
            <section v-if="sectionEnabled('quote') && sectionData('quote').text">
                <blockquote>{{ sectionData('quote').text }}</blockquote>
            </section>

            <!-- Events: Akad + Resepsi -->
            <section v-if="sectionEnabled('events') && events.length">
                <!-- TODO: implementasi tampilan event dari HTML -->
                <div v-for="event in events" :key="event.id">
                    <h3 :style="{ fontFamily: fontHeading }">{{ event.event_name }}</h3>
                    <p>{{ event.event_date_formatted }}</p>
                    <p v-if="event.start_time">{{ event.start_time }}</p>
                    <p>{{ event.location }}</p>
                    <a v-if="event.maps_url" :href="event.maps_url" target="_blank">Buka Maps</a>
                </div>
            </section>

            <!-- Countdown -->
            <section v-if="sectionEnabled('countdown') && targetDate">
                <!-- TODO: implementasi countdown visual dari HTML -->
                <div>
                    {{ pad(countdown.days) }} Hari
                    {{ pad(countdown.hours) }} Jam
                    {{ pad(countdown.minutes) }} Menit
                    {{ pad(countdown.seconds) }} Detik
                </div>
            </section>

            <!-- Live streaming -->
            <section v-if="sectionEnabled('live_streaming') && sectionData('live_streaming').url">
                <a :href="sectionData('live_streaming').url" target="_blank" rel="noopener">
                    Tonton Live Streaming
                </a>
            </section>

            <!-- Additional info -->
            <section v-if="sectionEnabled('additional_info') && sectionData('additional_info').text">
                <p>{{ sectionData('additional_info').text }}</p>
            </section>

            <!-- Gallery -->
            <section v-if="sectionEnabled('gallery') && galleries.length">
                <!-- TODO: implementasi gallery sesuai galleryLayout dari HTML -->
                <!-- galleryLayout = 'vertical' | 'horizontal' | 'grid' | 'masonry' -->
                <div v-for="img in galleries" :key="img.id">
                    <img :src="img.file_url" :alt="img.caption ?? ''" loading="lazy" />
                </div>
            </section>

            <!-- Love story -->
            <section v-if="sectionEnabled('love_story') && sectionData('love_story').stories?.length">
                <!-- TODO: implementasi timeline kisah cinta dari HTML -->
                <div v-for="story in sectionData('love_story').stories" :key="story.date">
                    <h4>{{ story.title }}</h4>
                    <p>{{ story.date }}</p>
                    <p>{{ story.description }}</p>
                </div>
            </section>

            <!-- Video embed -->
            <section v-if="sectionEnabled('video') && videoEmbedUrl(sectionData('video').url)">
                <iframe
                    :src="videoEmbedUrl(sectionData('video').url)"
                    frameborder="0"
                    allowfullscreen
                    style="width:100%;aspect-ratio:16/9"
                />
            </section>

            <!-- RSVP -->
            <section v-if="sectionEnabled('rsvp')">
                <!-- TODO: implementasi RSVP form dari HTML -->
                <form @submit.prevent="submitRsvp">
                    <input v-model="rsvpForm.guest_name" placeholder="Nama lengkap" required />
                    <select v-model="rsvpForm.attendance" required>
                        <option value="">Konfirmasi kehadiran</option>
                        <option value="hadir">Hadir</option>
                        <option value="tidak_hadir">Tidak Hadir</option>
                    </select>
                    <input v-model.number="rsvpForm.guest_count" type="number" min="1" max="10" />
                    <textarea v-model="rsvpForm.notes" placeholder="Catatan (opsional)" />
                    <p v-if="rsvpError" style="color:red">{{ rsvpError }}</p>
                    <p v-if="rsvpSuccess" style="color:green">Terima kasih atas konfirmasinya!</p>
                    <button type="submit" :disabled="rsvpSubmitting">
                        {{ rsvpSubmitting ? 'Mengirim...' : 'Kirim RSVP' }}
                    </button>
                </form>
            </section>

            <!-- Gift / Nomor rekening -->
            <section v-if="sectionEnabled('gift') && sectionData('gift').accounts?.length">
                <!-- TODO: implementasi tampilan rekening dari HTML -->
                <div v-for="acc in sectionData('gift').accounts" :key="acc.account_number">
                    <p>{{ acc.bank }}</p>
                    <p>{{ acc.account_name }}</p>
                    <p>{{ acc.account_number }}</p>
                    <button @click="copyToClipboard(acc.account_number)">
                        {{ copiedAccount === acc.account_number ? 'Tersalin ✓' : 'Salin' }}
                    </button>
                </div>
            </section>

            <!-- Wishes / Buku tamu -->
            <section v-if="sectionEnabled('wishes')">
                <!-- TODO: implementasi form + daftar ucapan dari HTML -->
                <form @submit.prevent="submitMessage">
                    <input v-model="msgForm.name" placeholder="Nama" required />
                    <textarea v-model="msgForm.message" placeholder="Ucapan & doa" required />
                    <p v-if="msgError" style="color:red">{{ msgError }}</p>
                    <p v-if="msgSuccess" style="color:green">Ucapan terkirim!</p>
                    <button type="submit" :disabled="msgSubmitting">
                        {{ msgSubmitting ? 'Mengirim...' : 'Kirim Ucapan' }}
                    </button>
                </form>
                <div v-for="msg in localMessages" :key="msg.id ?? msg.name">
                    <strong>{{ msg.name }}</strong>
                    <p>{{ msg.message }}</p>
                </div>
            </section>

            <!-- Closing -->
            <section v-if="sectionEnabled('closing')">
                <!-- TODO: implementasi penutup dari HTML -->
                <p>{{ closingText }}</p>
                <p>{{ groomName }} &amp; {{ brideName }}</p>
            </section>

        </div>

        <!-- Toast notification -->
        <Transition name="fade">
            <div
                v-if="toastVisible"
                style="position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#333;color:#fff;padding:8px 16px;border-radius:8px;z-index:50;white-space:nowrap"
            >
                {{ toastMsg }}
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/invitation/templates/_template-boilerplate.vue
git commit -m "feat: add template boilerplate for scaffold script"
```

---

## Task 7: Buat scaffold script `scripts/make-template.js`

**Files:**
- Create: `scripts/make-template.js`

- [ ] **Step 1: Buat direktori scripts**

```bash
mkdir -p scripts
```

- [ ] **Step 2: Buat scaffold script**

```js
// scripts/make-template.js
const { readFileSync, writeFileSync, existsSync } = require('fs')
const { join } = require('path')

const root = join(__dirname, '..')

const slug = process.argv[2]

if (!slug) {
    console.error('❌  Usage: npm run make:template <slug>')
    console.error('    Contoh: npm run make:template jasmine')
    process.exit(1)
}

if (!/^[a-z][a-z0-9-]*$/.test(slug)) {
    console.error('❌  Slug harus huruf kecil, angka, atau dash.')
    console.error('    Contoh valid: jasmine, royal-garden, bali-sunset')
    process.exit(1)
}

// 'jasmine' → 'JasmineTemplate', 'royal-garden' → 'RoyalGardenTemplate'
const componentName = slug.split('-')
    .map(s => s[0].toUpperCase() + s.slice(1))
    .join('') + 'Template'

const filename      = `${componentName}.vue`
const templatesDir  = join(root, 'resources/js/Components/invitation/templates')
const targetPath    = join(templatesDir, filename)
const boilerplate   = join(templatesDir, '_template-boilerplate.vue')
const registryPath  = join(templatesDir, 'registry.js')

// Guard: target already exists
if (existsSync(targetPath)) {
    console.error(`❌  ${filename} sudah ada.`)
    process.exit(1)
}

// Guard: boilerplate must exist
if (!existsSync(boilerplate)) {
    console.error(`❌  _template-boilerplate.vue tidak ditemukan di ${templatesDir}`)
    process.exit(1)
}

// 1. Copy boilerplate → new template file
writeFileSync(targetPath, readFileSync(boilerplate, 'utf8'))
console.log(`✓  Buat ${filename}`)

// 2. Update registry.js
let registry = readFileSync(registryPath, 'utf8')

// Insert import after last import line
const lastImportIdx = registry.lastIndexOf('\nimport ')
const insertAt = registry.indexOf('\n', lastImportIdx + 1)
registry =
    registry.slice(0, insertAt) +
    `\nimport ${componentName} from './${componentName}.vue'` +
    registry.slice(insertAt)

// Insert map entry before closing }
const mapCloseIdx = registry.lastIndexOf('}')
registry =
    registry.slice(0, mapCloseIdx) +
    `    '${slug}': ${componentName},\n` +
    registry.slice(mapCloseIdx)

writeFileSync(registryPath, registry)
console.log(`✓  Register '${slug}' di registry.js`)

// 3. Print checklist
console.log(`
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅  Template siap: ${componentName}
  📄  ${filename}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Checklist konversi HTML → Vue:
  ☐  Prep HTML (hapus CDN/JS kompetitor)
  ☐  Replace hardcoded warna → primary, accent, bgColor
  ☐  Replace hardcoded font  → fontTitle, fontHeading, fontBody
  ☐  Map setiap section HTML → section key (lihat spec)
  ☐  Wrap tiap section: v-if="sectionEnabled('key')"
  ☐  Set galleryLayout + openingStyle di composable call
  ☐  Insert DB record + demo_data lengkap
  ☐  Test di http://127.0.0.1:8000/templates/${slug}/demo
  ☐  Screenshot → upload sebagai thumbnail_url
`)
```

- [ ] **Step 3: Commit**

```bash
git add scripts/make-template.js
git commit -m "feat: add make-template scaffold script"
```

---

## Task 8: Update `package.json` — tambah script `make:template`

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Tambah script**

Ganti bagian `"scripts"` di `package.json`:

```json
"scripts": {
    "build": "vite build",
    "dev": "vite",
    "make:template": "node scripts/make-template.js"
},
```

- [ ] **Step 2: Test scaffold script end-to-end**

```bash
npm run make:template jasmine
```

Expected output:
```
✓  Buat JasmineTemplate.vue
✓  Register 'jasmine' di registry.js

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅  Template siap: JasmineTemplate
  📄  JasmineTemplate.vue
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
...
```

Verifikasi:
- `resources/js/Components/invitation/templates/JasmineTemplate.vue` ada
- `registry.js` sudah mengandung `import JasmineTemplate` dan `'jasmine': JasmineTemplate`

- [ ] **Step 3: Hapus test file Jasmine (cleanup)**

```bash
# Hapus file test yang dibuat di Step 2
rm resources/js/Components/invitation/templates/JasmineTemplate.vue
```

Restore `registry.js` ke kondisi hanya Nusantara (hapus entry Jasmine yang baru ditambah):

```js
// resources/js/Components/invitation/templates/registry.js
import NusantaraTemplate from './NusantaraTemplate.vue'

export const TEMPLATE_MAP = {
    'nusantara': NusantaraTemplate,
}
```

- [ ] **Step 4: Commit**

```bash
git add package.json resources/js/Components/invitation/templates/registry.js
git commit -m "feat: add make:template npm script"
```

---

## Task 9: Smoke test final end-to-end

- [ ] **Step 1: Jalankan dev server**

```bash
npm run dev
```

- [ ] **Step 2: Verifikasi Nusantara template (refactored)**

- Buka `http://127.0.0.1:8000/templates/nusantara/demo`
- Checklist acceptance criteria:
  - [ ] Gate animation muncul, terbuka saat diklik
  - [ ] Semua section tampil (cover, events, gallery, rsvp, wishes, closing)
  - [ ] Countdown berjalan real-time
  - [ ] Toggle section di dashboard → section hilang/muncul tanpa crash
  - [ ] Kustomisasi warna di TemplatePreviewModal → warna berubah
  - [ ] Submit RSVP/wishes di demo → ditolak dengan pesan isDemo
  - [ ] Tidak ada error di browser console

- [ ] **Step 3: Verifikasi scaffold script**

```bash
npm run make:template test-template
```

Cek:
- `TestTemplateTemplate.vue` terbuat
- `registry.js` terupdate dengan import dan entry baru
- Buka `http://127.0.0.1:8000/templates/test-template/demo` → 404 expected (belum ada DB record), ini normal

- [ ] **Step 4: Cleanup test template**

```bash
rm resources/js/Components/invitation/templates/TestTemplateTemplate.vue
```

Restore `registry.js`:
```js
import NusantaraTemplate from './NusantaraTemplate.vue'

export const TEMPLATE_MAP = {
    'nusantara': NusantaraTemplate,
}
```

- [ ] **Step 5: Final commit**

```bash
git add resources/js/Components/invitation/templates/registry.js
git commit -m "chore: restore registry after smoke test cleanup"
```

---

## Ringkasan Perubahan

| File | Action |
|------|--------|
| `registry.js` | Dibuat baru — single source of truth |
| `useInvitationTemplate.js` | Dibuat baru — semua shared logic |
| `_template-boilerplate.vue` | Dibuat baru — base untuk scaffold |
| `scripts/make-template.js` | Dibuat baru — scaffold script |
| `InvitationRenderer.vue` | Import dari registry (remove duplikat) |
| `TemplatePreviewModal.vue` | Import dari registry (remove duplikat) |
| `NusantaraTemplate.vue` | Refactor: script setup → composable |
| `package.json` | Tambah script make:template |

Setelah ini selesai: menambah template baru = `npm run make:template <slug>` + konversi HTML + insert DB record.
