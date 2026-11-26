<?php

namespace Database\Factories;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_no' => 'BLF-L-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'type' => ReportType::Lost,
            'reporter_user_id' => User::factory(),
            'handling_organization_id' => Organization::factory(),
            'custody_organization_id' => null,
            'category_id' => Category::factory(),
            'item_name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'brand' => fake()->optional()->company(),
            'model' => fake()->optional()->bothify('Model-###'),
            'color' => fake()->safeColorName(),
            'distinguishing_features' => fake()->optional()->sentence(),
            'occurred_date' => now()->subDays(fake()->numberBetween(0, 14))->toDateString(),
            'occurred_time' => fake()->optional()->time('H:i:s'),
            'barangay_id' => Barangay::factory(),
            'location_id' => null,
            'location_details' => fake()->optional()->streetAddress(),
            'reward_offered' => false,
            'reward_details' => null,
            'verification_status' => VerificationStatus::Pending,
            'item_status' => ItemStatus::Open,
            'is_confidential' => false,
            'is_public' => false,
            'source_locale' => 'en',
        ];
    }

    public function found(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reference_no' => 'BLF-F-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'type' => ReportType::Found,
            'custody_organization_id' => Organization::factory(),
            'item_status' => ItemStatus::InCustody,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
            'is_public' => true,
        ]);
    }
}
