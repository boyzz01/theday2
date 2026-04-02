<?php

// database/seeders/TemplateSeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $pernikahan = TemplateCategory::where('slug', 'pernikahan')->firstOrFail();
        $ulangTahun = TemplateCategory::where('slug', 'ulang-tahun')->firstOrFail();

        $weddingDemo = [
            'details' => [
                'groom_name'         => 'Ahmad Rizky',
                'bride_name'         => 'Siti Nurhaliza',
                'groom_photo_url'    => '/demo/groom.svg',
                'bride_photo_url'    => '/demo/bride.svg',
                'groom_parent_names' => 'Bpk. Hasan & Ibu Fatimah',
                'bride_parent_names' => 'Bpk. Rahmat & Ibu Aminah',
                'opening_text'       => "Bismillahirrahmanirrahim\nDengan memohon rahmat dan ridho Allah SWT, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri pernikahan kami.",
                'closing_text'       => 'Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir untuk memberikan doa restu.',
            ],
            'events' => [
                [
                    'event_name'    => 'Akad Nikah',
                    'event_date'    => '2026-06-15',
                    'start_time'    => '08:00',
                    'end_time'      => '10:00',
                    'venue_name'    => 'Masjid Al-Ikhlas',
                    'venue_address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
                    'maps_url'      => 'https://maps.google.com/?q=-6.2088,106.8456',
                ],
                [
                    'event_name'    => 'Resepsi',
                    'event_date'    => '2026-06-15',
                    'start_time'    => '11:00',
                    'end_time'      => '14:00',
                    'venue_name'    => 'Ballroom Hotel Mulia',
                    'venue_address' => 'Jl. Asia Afrika No. 8, Jakarta Selatan',
                    'maps_url'      => 'https://maps.google.com/?q=-6.2150,106.8070',
                ],
            ],
            'gallery' => [
                '/demo/prewedding-1.svg',
                '/demo/prewedding-2.svg',
                '/demo/prewedding-3.svg',
                '/demo/prewedding-4.svg',
            ],
        ];

        $birthdayDemo = [
            'details' => [
                'birthday_person_name' => 'Arya Pratama',
                'birthday_age'         => 7,
                'birthday_photo_url'   => '/demo/birthday-kid.svg',
                'opening_text'         => 'Dengan penuh kebahagiaan, kami mengundang teman-teman untuk merayakan ulang tahun ke-7!',
                'closing_text'         => 'Kehadiran kalian adalah kado terindah untuk Arya!',
            ],
            'events' => [
                [
                    'event_name'    => 'Birthday Party',
                    'event_date'    => '2026-07-20',
                    'start_time'    => '10:00',
                    'end_time'      => '13:00',
                    'venue_name'    => 'Playground Kemang',
                    'venue_address' => 'Jl. Kemang Raya No. 45, Jakarta Selatan',
                    'maps_url'      => 'https://maps.google.com/?q=-6.2600,106.8133',
                ],
            ],
            'gallery' => [
                '/demo/birthday-1.svg',
                '/demo/birthday-2.svg',
                '/demo/birthday-3.svg',
            ],
        ];

        $templates = [
            // ── Pernikahan ─────────────────────────────────────────
            [
                'category_id'    => $pernikahan->id,
                'name'           => 'Bunga Abadi',
                'slug'           => 'bunga-abadi',
                'thumbnail_url'  => null,
                'description'    => 'Template pernikahan elegan dengan sentuhan bunga dan nuansa hangat keemasan.',
                'default_config' => [
                    'primary_color'   => '#D4A373',
                    'secondary_color' => '#FEFAE0',
                    'accent_color'    => '#CCD5AE',
                    'font_title'      => 'Playfair Display',
                    'font_body'       => 'Poppins',
                    'layout'          => 'vertical-scroll',
                    'animation'       => 'fade-up',
                    'bg_pattern'      => 'floral',
                ],
                'demo_data'      => array_merge($weddingDemo, ['custom_config' => [
                    'primary_color'   => '#D4A373',
                    'secondary_color' => '#FEFAE0',
                    'font_title'      => 'Playfair Display',
                    'font_body'       => 'Poppins',
                ]]),
                'tier'           => 'free',
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'category_id'    => $pernikahan->id,
                'name'           => 'Langit Senja',
                'slug'           => 'langit-senja',
                'thumbnail_url'  => null,
                'description'    => 'Template pernikahan romantis dengan palet warna sunset yang memukau.',
                'default_config' => [
                    'primary_color'   => '#E57070',
                    'secondary_color' => '#FDE8E8',
                    'accent_color'    => '#F4A261',
                    'font_title'      => 'Cormorant Garamond',
                    'font_body'       => 'Lato',
                    'layout'          => 'vertical-scroll',
                    'animation'       => 'slide-up',
                    'bg_pattern'      => 'watercolor',
                ],
                'demo_data'      => array_merge($weddingDemo, ['custom_config' => [
                    'primary_color'   => '#E57070',
                    'secondary_color' => '#FDE8E8',
                    'font_title'      => 'Cormorant Garamond',
                    'font_body'       => 'Lato',
                ]]),
                'tier'           => 'premium',
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'category_id'    => $pernikahan->id,
                'name'           => 'Hijau Daun',
                'slug'           => 'hijau-daun',
                'thumbnail_url'  => null,
                'description'    => 'Template pernikahan segar dengan nuansa alam dan dedaunan hijau.',
                'default_config' => [
                    'primary_color'   => '#52796F',
                    'secondary_color' => '#CAD2C5',
                    'accent_color'    => '#84A98C',
                    'font_title'      => 'DM Serif Display',
                    'font_body'       => 'Nunito',
                    'layout'          => 'vertical-scroll',
                    'animation'       => 'fade-in',
                    'bg_pattern'      => 'leaves',
                ],
                'demo_data'      => array_merge($weddingDemo, ['custom_config' => [
                    'primary_color'   => '#52796F',
                    'secondary_color' => '#CAD2C5',
                    'font_title'      => 'DM Serif Display',
                    'font_body'       => 'Nunito',
                ]]),
                'tier'           => 'premium',
                'is_active'      => true,
                'sort_order'     => 3,
            ],

            // ── Ulang Tahun ────────────────────────────────────────
            [
                'category_id'    => $ulangTahun->id,
                'name'           => 'Confetti Ceria',
                'slug'           => 'confetti-ceria',
                'thumbnail_url'  => null,
                'description'    => 'Template ulang tahun penuh warna dengan efek confetti yang meriah.',
                'default_config' => [
                    'primary_color'   => '#F72585',
                    'secondary_color' => '#FFF0F6',
                    'accent_color'    => '#7209B7',
                    'font_title'      => 'Pacifico',
                    'font_body'       => 'Poppins',
                    'layout'          => 'vertical-scroll',
                    'animation'       => 'bounce',
                    'bg_pattern'      => 'confetti',
                ],
                'demo_data'      => array_merge($birthdayDemo, ['custom_config' => [
                    'primary_color'   => '#F72585',
                    'secondary_color' => '#FFF0F6',
                    'font_title'      => 'Pacifico',
                    'font_body'       => 'Poppins',
                ]]),
                'tier'           => 'free',
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'category_id'    => $ulangTahun->id,
                'name'           => 'Biru Bintang',
                'slug'           => 'biru-bintang',
                'thumbnail_url'  => null,
                'description'    => 'Template ulang tahun bertema galaxy dengan warna biru gelap dan bintang.',
                'default_config' => [
                    'primary_color'   => '#4361EE',
                    'secondary_color' => '#E0E7FF',
                    'accent_color'    => '#7C3AED',
                    'font_title'      => 'Fredoka One',
                    'font_body'       => 'Nunito',
                    'layout'          => 'vertical-scroll',
                    'animation'       => 'twinkle',
                    'bg_pattern'      => 'stars',
                ],
                'demo_data'      => array_merge($birthdayDemo, ['custom_config' => [
                    'primary_color'   => '#4361EE',
                    'secondary_color' => '#E0E7FF',
                    'font_title'      => 'Fredoka One',
                    'font_body'       => 'Nunito',
                ]]),
                'tier'           => 'premium',
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'category_id'    => $ulangTahun->id,
                'name'           => 'Peach Balon',
                'slug'           => 'peach-balon',
                'thumbnail_url'  => null,
                'description'    => 'Template ulang tahun manis dengan nuansa peach dan dekorasi balon.',
                'default_config' => [
                    'primary_color'   => '#FB8500',
                    'secondary_color' => '#FFF3E0',
                    'accent_color'    => '#FFB703',
                    'font_title'      => 'Nunito',
                    'font_body'       => 'Inter',
                    'layout'          => 'vertical-scroll',
                    'animation'       => 'float-up',
                    'bg_pattern'      => 'balloons',
                ],
                'demo_data'      => array_merge($birthdayDemo, ['custom_config' => [
                    'primary_color'   => '#FB8500',
                    'secondary_color' => '#FFF3E0',
                    'font_title'      => 'Nunito',
                    'font_body'       => 'Inter',
                ]]),
                'tier'           => 'free',
                'is_active'      => true,
                'sort_order'     => 3,
            ],
        ];

        foreach ($templates as $template) {
            Template::updateOrCreate(['slug' => $template['slug']], $template);
        }
    }
}
