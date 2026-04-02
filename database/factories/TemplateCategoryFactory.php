<?php

// database/factories/TemplateCategoryFactory.php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TemplateCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TemplateCategory>
 */
class TemplateCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name'        => ucfirst($name),
            'slug'        => Str::slug($name),
            'description' => fake()->sentence(),
            'icon_url'    => null,
            'is_active'   => true,
            'sort_order'  => fake()->numberBetween(1, 10),
        ];
    }
}
