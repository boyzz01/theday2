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
                'name'        => 'Storybook',
                'slug'        => 'storybook',
                'description' => 'Template undangan bergaya dunia ilustrasi interaktif — seperti membuka buku cerita.',
                'icon_url'    => null,
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Cinema',
                'slug'        => 'cinema',
                'description' => 'Template undangan bertema hiburan dan streaming — Netflix, Spotify, dan lainnya.',
                'icon_url'    => null,
                'is_active'   => true,
                'sort_order'  => 3,
            ],
        ];

        foreach ($categories as $category) {
            TemplateCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }

        // Remove any leftover non-wedding categories
        TemplateCategory::where('slug', 'ulang-tahun')->delete();
    }
}
