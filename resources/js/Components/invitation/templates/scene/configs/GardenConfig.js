// resources/js/Components/invitation/templates/scene/configs/GardenConfig.js
export default {
    background: '/images/templates/garden/scene.webp',
    fallbackBg: 'linear-gradient(180deg, #E8F5E9 0%, #C8E6C9 40%, #A5D6A7 100%)',
    hotspots: [
        { id: 'gallery',    x: 20, y: 15, label: 'Gallery',      section: 'gallery' },
        { id: 'date_venue', x: 60, y: 12, label: 'Date & Venue', section: 'events' },
        { id: 'about',      x: 50, y: 40, label: 'About Us',     section: 'couple' },
        { id: 'love_story', x: 30, y: 55, label: 'Love Story',   section: 'love_story' },
        { id: 'rsvp',       x: 70, y: 52, label: 'RSVP',         section: 'rsvp' },
        { id: 'gift',       x: 65, y: 70, label: 'Gift',         section: 'gift' },
    ],
}
