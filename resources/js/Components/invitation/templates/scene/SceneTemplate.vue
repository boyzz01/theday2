<!-- resources/js/Components/invitation/templates/scene/SceneTemplate.vue -->
<script setup>
import { ref, computed } from 'vue'
import { useInvitationTemplate } from '@/Composables/useInvitationTemplate'
import SceneHotspot        from './SceneHotspot.vue'
import SceneModal          from './SceneModal.vue'
import SceneGuestbook      from './SceneGuestbook.vue'
import SceneContentGallery   from './content/SceneContentGallery.vue'
import SceneContentEvents    from './content/SceneContentEvents.vue'
import SceneContentCouple    from './content/SceneContentCouple.vue'
import SceneContentLoveStory from './content/SceneContentLoveStory.vue'
import SceneContentRsvp      from './content/SceneContentRsvp.vue'
import SceneContentGift      from './content/SceneContentGift.vue'
import SceneContentGuestbook from './content/SceneContentGuestbook.vue'

const props = defineProps({
    invitation:  { type: Object,  required: true },
    messages:    { type: Array,   default: () => [] },
    guest:       { type: Object,  default: null },
    isDemo:      { type: Boolean, default: false },
    autoOpen:    { type: Boolean, default: false },
    sceneConfig: { type: Object,  required: true },
})

const {
    fontTitle, fontHeading,
    groomName, brideName, groomNick, brideNick,
    details, events, galleries,
    sectionEnabled, sectionData,
    firstEventDate,
    gateOpen, contentOpen, triggerGate,
    audioEl, musicPlaying, toggleMusic,
    toastMsg, toastVisible,
    copiedAccount, copyToClipboard,
    localMessages, msgForm, msgSubmitting, msgSuccess, msgError, submitMessage,
    rsvpForm, rsvpSubmitting, rsvpSuccess, rsvpError, submitRsvp,
} = useInvitationTemplate(props, {
    openingStyle:  'fade',
    galleryLayout: 'grid',
})

// Modal state
const activeSection  = ref(null)
const guestbookOpen  = ref(true)
const guestbookMode  = ref('list')

const modalOpen = computed(() => activeSection.value !== null)

const modalTitles = {
    gallery:    'Gallery',
    events:     'Date & Venue',
    couple:     'Tentang Kami',
    love_story: 'Kisah Cinta',
    rsvp:       'RSVP',
    gift:       'Hadiah',
    guestbook:  'Buku Tamu',
}

const modalTitle = computed(() => modalTitles[activeSection.value] ?? '')

function openSection(section) {
    activeSection.value = section
}

function closeModal() {
    activeSection.value = null
}

function openGuestbookForm() {
    guestbookMode.value  = 'form'
    activeSection.value  = 'guestbook'
}

function openGuestbookList() {
    guestbookMode.value  = 'list'
    activeSection.value  = 'guestbook'
}

const visibleHotspots = computed(() =>
    props.sceneConfig.hotspots.filter(h => sectionEnabled(h.section))
)
</script>

<template>
    <div>
        <!-- Audio -->
        <audio
            v-if="invitation.music?.file_url && sectionEnabled('music')"
            ref="audioEl"
            :src="invitation.music.file_url"
            loop
            preload="none"
            class="sr-only"
        />

        <!-- ── Cover / Gate ── -->
        <div
            v-if="!gateOpen"
            class="scene-cover"
            :style="{
                backgroundImage: `url(${sceneConfig.background}), ${sceneConfig.fallbackBg}`,
            }"
            @click="triggerGate"
        >
            <div class="cover-overlay">
                <h1 class="cover-names" :style="{ fontFamily: fontTitle }">
                    {{ groomNick }} &amp; {{ brideNick }}
                </h1>
                <p v-if="firstEventDate" class="cover-date" :style="{ fontFamily: fontHeading }">
                    {{ firstEventDate }}
                </p>
                <p v-if="guest?.name" class="cover-guest" :style="{ fontFamily: fontHeading }">
                    Kepada Yth. {{ guest.name }}
                </p>
                <button
                    class="open-btn"
                    :style="{ fontFamily: fontHeading }"
                    @click.stop="triggerGate"
                >
                    Buka Undangan
                </button>
            </div>
        </div>

        <!-- ── Scene ── -->
        <div v-if="contentOpen" class="scene-container">
            <!-- Background image -->
            <img
                :src="sceneConfig.background"
                class="scene-bg"
                alt=""
                draggable="false"
            />

            <!-- Top-left: Info -->
            <button
                class="fixed-btn fixed-top-left"
                aria-label="Info undangan"
                @click="openSection('couple')"
            >
                ⓘ
            </button>

            <!-- Top-right: Music -->
            <button
                v-if="sectionEnabled('music') && invitation.music?.file_url"
                class="fixed-btn fixed-top-right"
                :aria-label="musicPlaying ? 'Pause musik' : 'Play musik'"
                @click="toggleMusic"
            >
                {{ musicPlaying ? '🎵' : '🎶' }}
            </button>

            <!-- Hotspots -->
            <SceneHotspot
                v-for="(hotspot, i) in visibleHotspots"
                :key="hotspot.id"
                :hotspot="hotspot"
                :index="i"
                @click="openSection"
            />

            <!-- Bottom guestbook bar -->
            <SceneGuestbook
                v-if="sectionEnabled('wishes')"
                :open="guestbookOpen"
                @open-form="openGuestbookForm"
                @open-list="openGuestbookList"
                @toggle="guestbookOpen = !guestbookOpen"
            />
        </div>

        <!-- ── Modal ── -->
        <SceneModal
            :modelValue="modalOpen"
            :title="modalTitle"
            @update:modelValue="closeModal"
        >
            <SceneContentGallery
                v-if="activeSection === 'gallery'"
                :galleries="galleries"
            />
            <SceneContentEvents
                v-else-if="activeSection === 'events'"
                :events="events"
            />
            <SceneContentCouple
                v-else-if="activeSection === 'couple'"
                :groomName="groomName"
                :brideName="brideName"
                :details="details"
            />
            <SceneContentLoveStory
                v-else-if="activeSection === 'love_story'"
                :stories="sectionData('love_story').stories ?? []"
            />
            <SceneContentRsvp
                v-else-if="activeSection === 'rsvp'"
                :rsvpForm="rsvpForm"
                :rsvpSubmitting="rsvpSubmitting"
                :rsvpSuccess="rsvpSuccess"
                :rsvpError="rsvpError"
                @submit="submitRsvp"
            />
            <SceneContentGift
                v-else-if="activeSection === 'gift'"
                :accounts="sectionData('gift').accounts ?? []"
                :copiedAccount="copiedAccount"
                @copy="copyToClipboard"
            />
            <SceneContentGuestbook
                v-else-if="activeSection === 'guestbook'"
                :messages="localMessages"
                :msgForm="msgForm"
                :msgSubmitting="msgSubmitting"
                :msgSuccess="msgSuccess"
                :msgError="msgError"
                :mode="guestbookMode"
                @submit="submitMessage"
            />
        </SceneModal>

        <!-- Toast -->
        <Transition name="toast-fade">
            <div v-if="toastVisible" class="scene-toast">{{ toastMsg }}</div>
        </Transition>
    </div>
</template>

<style scoped>
/* ── Container ── */
.scene-container {
    position:     relative;
    width:        100%;
    max-width:    480px;
    aspect-ratio: 9 / 16;
    margin:       0 auto;
    overflow:     hidden;
    background:   #111;
}

.scene-bg {
    position:    absolute;
    inset:       0;
    width:       100%;
    height:      100%;
    object-fit:  cover;
    user-select: none;
    -webkit-user-drag: none;
}

/* ── Cover ── */
.scene-cover {
    position:            relative;
    width:               100%;
    max-width:           480px;
    aspect-ratio:        9 / 16;
    margin:              0 auto;
    background-size:     cover;
    background-position: center;
    cursor:              pointer;
    display:             flex;
    align-items:         center;
    justify-content:     center;
}

.cover-overlay {
    position:        absolute;
    inset:           0;
    background:      rgba(0, 0, 0, 0.45);
    display:         flex;
    flex-direction:  column;
    align-items:     center;
    justify-content: center;
    gap:             12px;
    padding:         24px;
    text-align:      center;
}

.cover-names {
    font-size:   28px;
    font-weight: 700;
    color:       #fff;
    line-height: 1.2;
}

.cover-date {
    font-size: 15px;
    color:     rgba(255, 255, 255, 0.85);
}

.cover-guest {
    font-size: 13px;
    color:     rgba(255, 255, 255, 0.7);
}

.open-btn {
    margin-top:      8px;
    background:      rgba(255, 255, 255, 0.2);
    border:          1.5px solid rgba(255, 255, 255, 0.7);
    border-radius:   999px;
    color:           #fff;
    padding:         10px 28px;
    font-size:       14px;
    cursor:          pointer;
    backdrop-filter: blur(4px);
}

/* ── Fixed buttons ── */
.fixed-btn {
    position:        absolute;
    z-index:         20;
    background:      rgba(255, 255, 255, 0.2);
    border:          1.5px solid rgba(255, 255, 255, 0.6);
    border-radius:   50%;
    width:           40px;
    height:          40px;
    display:         flex;
    align-items:     center;
    justify-content: center;
    font-size:       16px;
    cursor:          pointer;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.fixed-top-left  { top: 16px; left:  16px; }
.fixed-top-right { top: 16px; right: 16px; }

/* ── Toast ── */
.scene-toast {
    position:     fixed;
    bottom:       80px;
    left:         50%;
    transform:    translateX(-50%);
    background:   rgba(0, 0, 0, 0.75);
    color:        #fff;
    padding:      8px 18px;
    border-radius: 999px;
    font-size:    13px;
    z-index:      60;
    white-space:  nowrap;
}

.toast-fade-enter-active,
.toast-fade-leave-active { transition: opacity 0.3s; }
.toast-fade-enter-from,
.toast-fade-leave-to     { opacity: 0; }
</style>
