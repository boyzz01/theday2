<?php

// database/factories/InvitationFactory.php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventType;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(InvitationStatus::cases());

        return [
            'user_id'              => User::factory(),
            'template_id'          => Template::factory(),
            'slug'                 => Str::slug(fake()->unique()->words(3, true)),
            'title'                => 'Pernikahan ' . fake()->firstName() . ' & ' . fake()->firstName(),
            'event_type'           => EventType::Pernikahan,
            'custom_config'        => null,
            'status'               => $status,
            'published_at'         => $status !== InvitationStatus::Draft ? now()->subDays(fake()->numberBetween(1, 30)) : null,
            'expires_at'           => now()->addDays(fake()->numberBetween(30, 365)),
            'is_password_protected' => false,
            'password'             => null,
            'view_count'           => fake()->numberBetween(0, 1000),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => InvitationStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => InvitationStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'expires_at'   => now()->addDays(fake()->numberBetween(30, 180)),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => InvitationStatus::Expired,
            'published_at' => now()->subDays(60),
            'expires_at'   => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function forPernikahan(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::Pernikahan,
            'title'      => 'Pernikahan ' . fake()->firstName() . ' & ' . fake()->firstName(),
        ]);
    }

    public function passwordProtected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_password_protected' => true,
            'password'              => bcrypt('rahasia123'),
        ]);
    }
}
