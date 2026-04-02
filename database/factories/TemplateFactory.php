<?php

// database/factories/TemplateFactory.php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateTier;
use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id'    => TemplateCategory::factory(),
            'name'           => ucwords($name),
            'slug'           => Str::slug($name),
            'thumbnail_url'  => null,
            'description'    => fake()->sentence(),
            'default_config' => [
                'primary_color'   => '#D4A373',
                'secondary_color' => '#FEFAE0',
                'accent_color'    => '#CCD5AE',
                'font_title'      => 'Playfair Display',
                'font_body'       => 'Poppins',
                'layout'          => 'vertical-scroll',
                'animation'       => 'fade-up',
                'bg_pattern'      => 'minimal',
            ],
            'tier'           => fake()->randomElement(TemplateTier::cases()),
            'is_active'      => true,
            'sort_order'     => fake()->numberBetween(1, 20),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => TemplateTier::Free,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => TemplateTier::Premium,
        ]);
    }
}
