// resources/js/Components/invitation/templates/scene/configs/NightSkyConfig.js
export default {
    background: '/images/templates/night-sky/scene.webp',
    fallbackBg: 'linear-gradient(180deg, #0D1B2A 0%, #1B2A4A 50%, #2E3F6F 100%)',
    hotspots: [
        { id: 'gallery',    x: 22, y: 18, label: 'Gallery',      section: 'gallery' },
        { id: 'date_venue', x: 58, y: 14, label: 'Date & Venue', section: 'events' },
        { id: 'about',      x: 48, y: 42, label: 'About Us',     section: 'couple' },
        { id: 'love_story', x: 28, y: 58, label: 'Love Story',   section: 'love_story' },
        { id: 'rsvp',       x: 70, y: 54, label: 'RSVP',         section: 'rsvp' },
        { id: 'gift',       x: 62, y: 68, label: 'Gift',         section: 'gift' },
    ],
}
