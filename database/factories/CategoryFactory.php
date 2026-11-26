<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'slug' => Str::slug($name),
            'labels' => [
                'en' => ucfirst($name),
                'fil' => ucfirst($name),
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
