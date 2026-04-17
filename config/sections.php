<?php

// config/sections.php
// Centralized section access config — single source of truth for free vs premium gating.
//
// tier:                  'free' | 'premium'
// visible_in_free_wizard: show in free user's main wizard flow
// editable_by_free:       free user can edit this section
// pattern:               null | 'A' (hidden) | 'B' (visible locked teaser)
// upgrade_copy:          shown on Pattern B teaser card

return [
    // ── Informasi ─────────────────────────────────────────────────────
    'cover' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'konten_utama' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'couple' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'quote' => [
        'tier'                 => 'premium',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => false,
        'pattern'              => 'B',
        'upgrade_copy'         => 'Tampilkan kutipan inspiratif atau ayat pilihan yang mempercantik undangan kalian.',
    ],

    // ── Acara ─────────────────────────────────────────────────────────
    'events' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'countdown' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'live_streaming' => [
        'tier'                 => 'premium',
        'visible_in_free_wizard' => false,
        'editable_by_free'     => false,
        'pattern'              => 'A',
        'upgrade_copy'         => 'Pasang link live streaming agar tamu yang berhalangan tetap bisa ikut menyaksikan.',
    ],
    'additional_info' => [
        'tier'                 => 'premium',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => false,
        'pattern'              => 'B',
        'upgrade_copy'         => 'Tambahkan catatan khusus seperti dress code, info parkir, atau pesan untuk tamu undangan.',
    ],

    // ── Media ─────────────────────────────────────────────────────────
    'gallery' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'video' => [
        'tier'                 => 'premium',
        'visible_in_free_wizard' => false,
        'editable_by_free'     => false,
        'pattern'              => 'A',
        'upgrade_copy'         => 'Tampilkan video prewedding atau teaser perjalanan cinta kalian.',
    ],
    'love_story' => [
        'tier'                 => 'premium',
        'visible_in_free_wizard' => false,
        'editable_by_free'     => false,
        'pattern'              => 'A',
        'upgrade_copy'         => 'Ceritakan perjalanan kisah cinta kalian dalam timeline yang elegan.',
    ],

    // ── Interaksi ─────────────────────────────────────────────────────
    'rsvp' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'wishes' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'gift' => [
        'tier'                 => 'premium',
        'visible_in_free_wizard' => false,
        'editable_by_free'     => false,
        'pattern'              => 'A',
        'upgrade_copy'         => 'Aktifkan amplop digital untuk tamu yang ingin mengirim hadiah pernikahan.',
    ],

    // ── Tampilan ──────────────────────────────────────────────────────
    'music' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'theme_settings' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],

    // ── Publikasi ─────────────────────────────────────────────────────
    'slug_settings' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
    'password_protection' => [
        'tier'                 => 'premium',
        'visible_in_free_wizard' => false,
        'editable_by_free'     => false,
        'pattern'              => 'A',
        'upgrade_copy'         => 'Lindungi undangan dengan kata sandi khusus untuk tamu tertentu.',
    ],
    'preview_and_publish' => [
        'tier'                 => 'free',
        'visible_in_free_wizard' => true,
        'editable_by_free'     => true,
        'pattern'              => null,
    ],
];
