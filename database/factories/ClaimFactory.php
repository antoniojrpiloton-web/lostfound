<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClaimFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_no' => 'BLF-C-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'item_report_id' => ItemReport::factory()->found()->verified(),
            'lost_report_id' => null,
            'claimant_user_id' => User::factory(),
            'ownership_description' => fake()->paragraph(),
            'distinguishing_details' => fake()->sentence(),
            'status' => ClaimStatus::Submitted,
            'submitted_at' => now(),
        ];
    }
}
