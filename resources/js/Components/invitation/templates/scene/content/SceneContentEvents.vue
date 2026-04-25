<!-- resources/js/Components/invitation/templates/scene/content/SceneContentEvents.vue -->
<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

const props = defineProps({
    events: { type: Array, default: () => [] },
})

const now = ref(Date.now())
let timer = null

onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 1000) })
onUnmounted(() => clearInterval(timer))

// Pick the soonest upcoming event date
const nearestEvent = computed(() => {
    const future = props.events
        .filter(e => e.event_date && new Date(e.event_date).getTime() > now.value)
        .sort((a, b) => new Date(a.event_date) - new Date(b.event_date))
    return future[0] ?? null
})

const countdown = computed(() => {
    if (!nearestEvent.value) return null
    const diff = new Date(nearestEvent.value.event_date).getTime() - now.value
    if (diff <= 0) return null
    return {
        days:    Math.floor(diff / 86400000),
        hours:   Math.floor((diff % 86400000) / 3600000),
        minutes: Math.floor((diff % 3600000) / 60000),
        seconds: Math.floor((diff % 60000) / 1000),
    }
})

function toGcalDate(date, time) {
    const d = date.replace(/-/g, '')
    if (!time) return d
    const t = time.replace(':', '') + '00'
    return `${d}T${t}00`
}

function googleCalendarUrl(event) {
    const start = toGcalDate(event.event_date, event.start_time)
    const end   = event.end_time
        ? toGcalDate(event.event_date, event.end_time)
        : toGcalDate(event.event_date, event.start_time)
    const params = new URLSearchParams({
        action:   'TEMPLATE',
        text:     event.event_name,
        dates:    `${start}/${end}`,
        location: [event.venue_name, event.venue_address].filter(Boolean).join(', '),
    })
    return `https://calendar.google.com/calendar/render?${params}`
}
</script>

<template>
    <div class="events-wrap">
        <p v-if="!events.length" class="text-center text-gray-400 py-8">
            Belum ada jadwal acara.
        </p>

        <!-- Single countdown above all cards -->
        <div v-if="countdown" class="countdown-wrap">
            <p class="countdown-label">Menghitung hari — {{ nearestEvent.event_name }}</p>
            <div class="countdown-grid">
                <div class="countdown-unit">
                    <span class="countdown-num">{{ countdown.days }}</span>
                    <span class="countdown-tag">Hari</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-unit">
                    <span class="countdown-num">{{ String(countdown.hours).padStart(2,'0') }}</span>
                    <span class="countdown-tag">Jam</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-unit">
                    <span class="countdown-num">{{ String(countdown.minutes).padStart(2,'0') }}</span>
                    <span class="countdown-tag">Menit</span>
                </div>
                <div class="countdown-sep">:</div>
                <div class="countdown-unit">
                    <span class="countdown-num">{{ String(countdown.seconds).padStart(2,'0') }}</span>
                    <span class="countdown-tag">Detik</span>
                </div>
            </div>
        </div>

        <div v-for="event in events" :key="event.id" class="event-card">
            <h3 class="event-name">{{ event.event_name }}</h3>
            <p class="event-meta">📅 {{ event.event_date_formatted }}</p>
            <p v-if="event.start_time" class="event-meta">
                🕐 {{ event.start_time }}{{ event.end_time ? ' – ' + event.end_time : '' }}
            </p>
            <p v-if="event.venue_name" class="event-venue">📍 {{ event.venue_name }}</p>
            <p v-if="event.venue_address" class="event-address">{{ event.venue_address }}</p>
            <div class="event-actions">
                <a
                    v-if="event.maps_url"
                    :href="event.maps_url"
                    target="_blank"
                    rel="noopener"
                    class="action-btn maps-btn"
                >
                    📍 Buka Maps
                </a>
                <a
                    :href="googleCalendarUrl(event)"
                    target="_blank"
                    rel="noopener"
                    class="action-btn save-btn"
                >
                    🗓 Save the Date
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.events-wrap { display: flex; flex-direction: column; gap: 14px; }

.event-card {
    background:    rgba(255,255,255,0.55);
    border:        1px solid rgba(200,160,100,0.2);
    border-radius: 16px;
    padding:       16px;
    backdrop-filter: blur(6px);
}

.event-name {
    font-size:   15px;
    font-weight: 700;
    color:       #2d1a0e;
    margin:      0 0 8px;
}

.event-meta {
    font-size:   13px;
    color:       #7a5a40;
    margin:      2px 0;
}

.event-venue {
    font-size:   13px;
    font-weight: 600;
    color:       #5a3a20;
    margin:      8px 0 2px;
}

.event-address {
    font-size:   11px;
    color:       #9a7a60;
    line-height: 1.5;
    margin:      0;
}

/* ── Countdown ── */
.countdown-wrap {
    margin:        0 0 14px;
    text-align:    center;
    padding:       12px 8px;
    background:    linear-gradient(135deg, rgba(212,163,115,0.12), rgba(180,120,80,0.08));
    border-radius: 12px;
    border:        1px solid rgba(212,163,115,0.25);
}

.countdown-label {
    font-size:      9px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color:          #c8956a;
    margin:         0 0 8px;
}

.countdown-grid {
    display:     flex;
    align-items: center;
    justify-content: center;
    gap:         4px;
}

.countdown-unit {
    display:        flex;
    flex-direction: column;
    align-items:    center;
    min-width:      38px;
}

.countdown-num {
    font-size:   22px;
    font-weight: 800;
    color:       #3d2010;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.countdown-tag {
    font-size:      8px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color:          #c8956a;
    margin-top:     3px;
}

.countdown-sep {
    font-size:   20px;
    font-weight: 700;
    color:       #d4a373;
    align-self:  flex-start;
    padding-top: 2px;
    line-height: 1;
}

.event-actions {
    display:    flex;
    gap:        8px;
    margin-top: 12px;
}

.action-btn {
    flex:            1;
    text-align:      center;
    padding:         8px 6px;
    border-radius:   10px;
    font-size:       12px;
    font-weight:     600;
    text-decoration: none;
    transition:      background 0.2s, transform 0.15s;
}
.action-btn:active { transform: scale(0.97); }

.maps-btn {
    background: rgba(200,149,106,0.12);
    border:     1px solid rgba(200,149,106,0.3);
    color:      #a0703a;
}
.maps-btn:hover { background: rgba(200,149,106,0.22); }

.save-btn {
    background: rgba(212,163,115,0.18);
    border:     1px solid rgba(212,163,115,0.4);
    color:      #7a4e20;
}
.save-btn:hover { background: rgba(212,163,115,0.32); }
</style>
