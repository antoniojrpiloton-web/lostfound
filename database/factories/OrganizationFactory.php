<?php

namespace Database\Factories;

use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => OrganizationType::BarangayLgu,
            'code' => strtoupper('ORG-'.Str::random(6)),
            'name' => fake()->company(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    public function policeStation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => OrganizationType::PoliceStation,
        ]);
    }
}
