<?php

// database/seeders/TemplateCategorySeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;

class TemplateCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Pernikahan',
                'slug'        => 'pernikahan',
                'description' => 'Template undangan pernikahan elegan dan romantis.',
                'icon_url'    => null,
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Ulang Tahun',
                'slug'        => 'ulang-tahun',
                'description' => 'Template undangan ulang tahun ceria dan penuh warna.',
                'icon_url'    => null,
                'is_active'   => true,
                'sort_order'  => 2,
            ],
        ];

        foreach ($categories as $category) {
            TemplateCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
