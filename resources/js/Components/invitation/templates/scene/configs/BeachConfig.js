// resources/js/Components/invitation/templates/scene/configs/BeachConfig.js
export default {
    background: '/images/templates/beach/scene.webp',
    fallbackBg: 'linear-gradient(180deg, #87CEEB 0%, #F0E68C 60%, #DEB887 100%)',
    hotspots: [
        { id: 'gallery',    x: 18, y: 12, label: 'Gallery',      section: 'gallery' },
        { id: 'date_venue', x: 55, y: 10, label: 'Date & Venue', section: 'events' },
        { id: 'about',      x: 45, y: 38, label: 'About Us',     section: 'couple' },
        { id: 'love_story', x: 52, y: 55, label: 'Love Story',   section: 'love_story' },
        { id: 'rsvp',       x: 72, y: 50, label: 'RSVP',         section: 'rsvp' },
        { id: 'gift',       x: 68, y: 72, label: 'Gift',         section: 'gift' },
    ],
}
