<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BarangayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper('BRGY-'.Str::random(6)),
            'name' => fake()->unique()->city(),
            'municipality' => 'Malay',
            'province' => 'Aklan',
            'is_active' => true,
        ];
    }
}
