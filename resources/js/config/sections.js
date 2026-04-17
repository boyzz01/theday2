// resources/js/config/sections.js
// Frontend mirror of config/sections.php — single source of truth for UI gating.

export const SECTION_CONFIG = {
    // Informasi
    cover:               { tier: 'free',    pattern: null },
    konten_utama:        { tier: 'free',    pattern: null },
    couple:              { tier: 'free',    pattern: null },
    quote: {
        tier:        'premium',
        pattern:     'B',
        upgradeCopy: 'Tampilkan kutipan inspiratif atau ayat pilihan yang mempercantik undangan kalian.',
    },

    // Acara
    events:    { tier: 'free',    pattern: null },
    countdown: { tier: 'free',    pattern: null },
    live_streaming: {
        tier:        'premium',
        pattern:     'A',
        upgradeCopy: 'Pasang link live streaming agar tamu yang berhalangan tetap bisa ikut menyaksikan.',
    },
    additional_info: {
        tier:        'premium',
        pattern:     'B',
        upgradeCopy: 'Tambahkan catatan khusus seperti dress code, info parkir, atau pesan untuk tamu undangan.',
    },

    // Media
    gallery: { tier: 'free', pattern: null },
    video: {
        tier:        'premium',
        pattern:     'A',
        upgradeCopy: 'Tampilkan video prewedding atau teaser perjalanan cinta kalian.',
    },
    love_story: {
        tier:        'premium',
        pattern:     'A',
        upgradeCopy: 'Ceritakan perjalanan kisah cinta kalian dalam timeline yang elegan.',
    },

    // Interaksi
    rsvp:   { tier: 'free', pattern: null },
    wishes: { tier: 'free', pattern: null },
    gift: {
        tier:        'premium',
        pattern:     'A',
        upgradeCopy: 'Aktifkan amplop digital untuk tamu yang ingin mengirim hadiah pernikahan.',
    },

    // Tampilan
    music:          { tier: 'free', pattern: null },
    theme_settings: { tier: 'free', pattern: null },

    // Publikasi
    slug_settings:       { tier: 'free', pattern: null },
    password_protection: {
        tier:        'premium',
        pattern:     'A',
        upgradeCopy: 'Lindungi undangan dengan kata sandi khusus untuk tamu tertentu.',
    },
    preview_and_publish: { tier: 'free', pattern: null },
};

/** True when the section is premium-only. */
export function isSectionPremium(sectionKey) {
    return SECTION_CONFIG[sectionKey]?.tier === 'premium';
}

/** Pattern A = hidden; Pattern B = visible locked teaser; null = always shown. */
export function getSectionPattern(sectionKey) {
    return SECTION_CONFIG[sectionKey]?.pattern ?? null;
}

/** Upgrade copy shown on Pattern B teaser cards. */
export function getSectionUpgradeCopy(sectionKey) {
    return SECTION_CONFIG[sectionKey]?.upgradeCopy ?? 'Fitur ini tersedia di Premium.';
}
