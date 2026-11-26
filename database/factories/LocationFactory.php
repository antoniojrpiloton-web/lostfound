<?php

namespace Database\Factories;

use App\Models\Barangay;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barangay_id' => Barangay::factory(),
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->streetName(),
            'type' => 'landmark',
            'description' => fake()->sentence(),
            'latitude' => fake()->latitude(11.93, 12.01),
            'longitude' => fake()->longitude(121.90, 121.95),
            'is_active' => true,
        ];
    }
}
