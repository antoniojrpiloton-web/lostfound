<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'role_id' => fn () => Role::query()->where('key', 'institution_staff')->value('id'),
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
        ];
    }
}
