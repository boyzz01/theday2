<?php

// config/plans.php
// Centralized plan feature definitions.
// DB is authoritative for limits; this file documents the canonical defaults.

return [
    'free' => [
        'max_invitations'   => 1,
        'max_photos'        => 5,
        'premium_templates' => false,
        'custom_music'      => false,
        'watermark'         => true,
        'custom_slug'       => false,
        'password_protect'  => false,
        'analytics'         => false,
        'priority_support'  => false,
    ],
    'premium' => [
        'max_invitations'   => null,  // unlimited (DB stores 9999)
        'max_photos'        => null,  // unlimited (DB stores 9999)
        'premium_templates' => true,
        'custom_music'      => true,
        'watermark'         => false,
        'custom_slug'       => true,
        'password_protect'  => true,
        'analytics'         => true,
        'priority_support'  => true,
    ],
];
