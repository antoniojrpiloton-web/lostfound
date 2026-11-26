<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'google_id' => (string) fake()->unique()->numerify('10####################'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'avatar_url' => null,
            'locale' => 'en',
            'status' => UserStatus::Active,
            'last_login_at' => now(),
            'password' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::Suspended,
        ]);
    }

    public function withoutGoogleId(): static
    {
        return $this->state(fn (array $attributes): array => [
            'google_id' => null,
        ]);
    }
}
