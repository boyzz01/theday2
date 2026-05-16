<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SectionVariant;
use App\Models\Template;
use App\Models\TemplateSection;
use Illuminate\Database\Seeder;

class SectionVariantSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Section Variants ───────────────────────────────────────────
        $variants = [
            // Envelope (amplop / buka undangan screen)
            ['section_type' => 'envelope', 'code' => 'envelope.default',       'name' => 'Default'],

            // Cover
            ['section_type' => 'cover',   'code' => 'cover.default',          'name' => 'Default'],
            ['section_type' => 'cover',   'code' => 'cover.fullscreen_photo',  'name' => 'Fullscreen Photo'],

            // Opening
            ['section_type' => 'opening', 'code' => 'opening.default',         'name' => 'Default'],

            // Couple
            ['section_type' => 'couple',  'code' => 'couple.default',          'name' => 'Default'],
            ['section_type' => 'couple',  'code' => 'couple.side_by_side',      'name' => 'Side by Side'],

            // Quote
            ['section_type' => 'quote',   'code' => 'quote.default',           'name' => 'Default'],

            // Events
            ['section_type' => 'events',  'code' => 'events.default',          'name' => 'Default'],
            ['section_type' => 'events',  'code' => 'events.timeline',         'name' => 'Timeline'],

            // Countdown
            ['section_type' => 'countdown', 'code' => 'countdown.default',     'name' => 'Default'],

            // Location
            ['section_type' => 'location', 'code' => 'location.default',       'name' => 'Default'],

            // Love Story
            ['section_type' => 'love_story', 'code' => 'love_story.default',   'name' => 'Default'],

            // Gallery
            ['section_type' => 'gallery', 'code' => 'gallery.grid',            'name' => 'Grid'],
            ['section_type' => 'gallery', 'code' => 'gallery.carousel',        'name' => 'Carousel'],

            // Video
            ['section_type' => 'video',   'code' => 'video.default',           'name' => 'Default'],

            // RSVP
            ['section_type' => 'rsvp',    'code' => 'rsvp.default',            'name' => 'Default'],

            // Wishes
            ['section_type' => 'wishes',  'code' => 'wishes.default',          'name' => 'Default'],

            // Gift
            ['section_type' => 'gift',    'code' => 'gift.default',            'name' => 'Default'],

            // Live Streaming
            ['section_type' => 'live_streaming', 'code' => 'live_streaming.default', 'name' => 'Default'],

            // Additional Info
            ['section_type' => 'additional_info', 'code' => 'additional_info.default', 'name' => 'Default'],

            // Closing
            ['section_type' => 'closing', 'code' => 'closing.default',         'name' => 'Default'],
        ];

        foreach ($variants as $v) {
            SectionVariant::firstOrCreate(['code' => $v['code']], $v + ['status' => 'active']);
        }

        // ── 2. Template Sections for ALL templates ────────────────────────
        $defaultSections = $this->defaultSections();
        $variantMap = SectionVariant::pluck('id', 'code');

        $allTemplates = Template::all();

        foreach ($allTemplates as $template) {
            foreach ($defaultSections as $s) {
                $variantId  = $variantMap[$s['_variant_code']] ?? null;
                $attributes = array_diff_key($s, ['_variant_code' => true]);

                TemplateSection::firstOrCreate(
                    ['template_id' => $template->id, 'section_key' => $attributes['section_key']],
                    array_merge($attributes, [
                        'template_id'        => $template->id,
                        'default_variant_id' => $variantId,
                    ])
                );
            }
        }
    }

    private function defaultSections(): array
    {
        // All default templates share this section blueprint.
        // sort_order drives display order in editor sidebar.
        return [
            // ── Wajib ──────────────────────────────────────────
            [
                'section_key'           => 'envelope',
                'section_type'          => 'envelope',
                'label'                 => 'Cover',
                'sort_order'            => 5,
                'is_required'           => true,
                'is_enabled_by_default' => true,
                'is_removable'          => false,
                '_variant_code'         => 'envelope.default',
            ],
            [
                'section_key'           => 'cover',
                'section_type'          => 'cover',
                'label'                 => 'Pembuka Undangan',
                'sort_order'            => 10,
                'is_required'           => true,
                'is_enabled_by_default' => true,
                'is_removable'          => false,
                '_variant_code'         => 'cover.default',
                'default_data_json'     => [
                    'pretitle'               => 'The Wedding Of',
                    'couple_names'           => '',
                    'event_date_text'        => '',
                    'intro_text'             => 'Kepada Bapak/Ibu/Saudara/i',
                    'button_text'            => 'Buka Undangan',
                    'guest_name_mode'        => 'query_param',
                    'guest_name'             => null,
                    'guest_query_key'        => 'to',
                    'fallback_guest_text'    => 'Tamu Undangan',
                    'show_guest_name'        => true,
                    'background_image'       => null,
                    'background_mobile_image' => null,
                    'background_position'    => 'center',
                    'background_size'        => 'cover',
                    'text_align'             => 'center',
                    'content_position'       => 'center',
                    'overlay_opacity'        => 0.35,
                    'show_ornament'          => true,
                    'show_date'              => true,
                    'show_pretitle'          => true,
                    'music_on_open'          => true,
                    'show_music_button'      => false,
                    'open_action'            => 'enter_content',
                ],
            ],
            [
                'section_key'           => 'couple',
                'section_type'          => 'couple',
                'label'                 => 'Mempelai',
                'sort_order'            => 20,
                'is_required'           => true,
                'is_enabled_by_default' => true,
                'is_removable'          => false,
                '_variant_code'         => 'couple.default',
            ],
            [
                'section_key'           => 'events',
                'section_type'          => 'events',
                'label'                 => 'Acara',
                'sort_order'            => 30,
                'is_required'           => true,
                'is_enabled_by_default' => true,
                'is_removable'          => false,
                '_variant_code'         => 'events.default',
            ],
            [
                'section_key'           => 'closing',
                'section_type'          => 'closing',
                'label'                 => 'Penutup',
                'sort_order'            => 90,
                'is_required'           => true,
                'is_enabled_by_default' => true,
                'is_removable'          => false,
                '_variant_code'         => 'closing.default',
            ],

            // ── Cerita & Visual ────────────────────────────────
            [
                'section_key'           => 'opening',
                'section_type'          => 'opening',
                'label'                 => 'Pembuka',
                'sort_order'            => 15,
                'is_required'           => false,
                'is_enabled_by_default' => true,
                'is_removable'          => true,
                '_variant_code'         => 'opening.default',
            ],
            [
                'section_key'           => 'quote',
                'section_type'          => 'quote',
                'label'                 => 'Kutipan',
                'sort_order'            => 25,
                'is_required'           => false,
                'is_enabled_by_default' => false,
                'is_removable'          => true,
                '_variant_code'         => 'quote.default',
            ],
            [
                'section_key'           => 'love_story',
                'section_type'          => 'love_story',
                'label'                 => 'Love Story',
                'sort_order'            => 45,
                'is_required'           => false,
                'is_enabled_by_default' => false,
                'is_removable'          => true,
                '_variant_code'         => 'love_story.default',
            ],
            [
                'section_key'           => 'gallery',
                'section_type'          => 'gallery',
                'label'                 => 'Galeri',
                'sort_order'            => 50,
                'is_required'           => false,
                'is_enabled_by_default' => true,
                'is_removable'          => true,
                '_variant_code'         => 'gallery.grid',
            ],
            [
                'section_key'           => 'video',
                'section_type'          => 'video',
                'label'                 => 'Video',
                'sort_order'            => 55,
                'is_required'           => false,
                'is_enabled_by_default' => false,
                'is_removable'          => true,
                '_variant_code'         => 'video.default',
            ],

            // ── Interaksi ─────────────────────────────────────
            [
                'section_key'           => 'rsvp',
                'section_type'          => 'rsvp',
                'label'                 => 'RSVP',
                'sort_order'            => 60,
                'is_required'           => false,
                'is_enabled_by_default' => true,
                'is_removable'          => true,
                '_variant_code'         => 'rsvp.default',
            ],
            [
                'section_key'           => 'wishes',
                'section_type'          => 'wishes',
                'label'                 => 'Ucapan',
                'sort_order'            => 65,
                'is_required'           => false,
                'is_enabled_by_default' => true,
                'is_removable'          => true,
                '_variant_code'         => 'wishes.default',
            ],
            [
                'section_key'           => 'live_streaming',
                'section_type'          => 'live_streaming',
                'label'                 => 'Live Streaming',
                'sort_order'            => 70,
                'is_required'           => false,
                'is_enabled_by_default' => false,
                'is_removable'          => true,
                '_variant_code'         => 'live_streaming.default',
            ],

            // ── Tambahan ──────────────────────────────────────
            [
                'section_key'           => 'gift',
                'section_type'          => 'gift',
                'label'                 => 'Hadiah',
                'sort_order'            => 75,
                'is_required'           => false,
                'is_enabled_by_default' => false,
                'is_removable'          => true,
                '_variant_code'         => 'gift.default',
            ],
            [
                'section_key'           => 'additional_info',
                'section_type'          => 'additional_info',
                'label'                 => 'Info Tambahan',
                'sort_order'            => 80,
                'is_required'           => false,
                'is_enabled_by_default' => false,
                'is_removable'          => true,
                '_variant_code'         => 'additional_info.default',
            ],
        ];
    }
}
