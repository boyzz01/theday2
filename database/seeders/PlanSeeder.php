<?php

// database/seeders/PlanSeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                => 'Free',
                'price'               => 0,
                'duration_days'       => 0,
                'max_invitations'     => 1,
                'max_gallery_photos'  => 5,
                'custom_music'        => false,
                'remove_watermark'    => false,
                'custom_domain'       => false,
                'analytics_access'    => false,
                'features'            => [
                    'Template dasar (10+)',
                    'Konfirmasi RSVP',
                    'Link undangan',
                    'Peta lokasi',
                ],
                'is_active'           => true,
                'sort_order'          => 1,
            ],
            [
                'name'                => 'Silver',
                'price'               => 99000,
                'duration_days'       => 30,
                'max_invitations'     => 5,
                'max_gallery_photos'  => 20,
                'custom_music'        => true,
                'remove_watermark'    => true,
                'custom_domain'       => false,
                'analytics_access'    => true,
                'features'            => [
                    'Semua template (50+)',
                    'Konfirmasi RSVP',
                    'Custom URL slug',
                    'Upload musik sendiri',
                    'Analitik lengkap',
                    'Tanpa watermark',
                ],
                'is_active'           => true,
                'sort_order'          => 2,
            ],
            [
                'name'                => 'Gold',
                'price'               => 199000,
                'duration_days'       => 30,
                'max_invitations'     => 9999,
                'max_gallery_photos'  => 9999,
                'custom_music'        => true,
                'remove_watermark'    => true,
                'custom_domain'       => true,
                'analytics_access'    => true,
                'features'            => [
                    'Undangan tidak terbatas',
                    'Semua template (50+)',
                    'Custom URL slug',
                    'Custom domain',
                    'Upload musik sendiri',
                    'Foto galeri tidak terbatas',
                    'Analitik lengkap',
                    'Tanpa watermark',
                    'Prioritas support',
                ],
                'is_active'           => true,
                'sort_order'          => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
