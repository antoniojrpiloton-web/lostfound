<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_user_can_submit_claim_with_private_evidence_and_evidence_is_protected(): void
    {
        Storage::fake('local');

        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $yapak = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();
        $balabagAdmin = $this->createUserWithRole('institution_admin', $balabag);
        $yapakAdmin = $this->createUserWithRole('institution_admin', $yapak);

        $claimant = User::factory()->create();
        $otherPublic = User::factory()->create();

        $found = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $balabag->id,
            'custody_organization_id' => $balabag->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
            'item_status' => ItemStatus::InCustody,
        ]);

        $this->actingAs($balabagAdmin)
            ->get(route('claims.create', $found->reference_no))
            ->assertForbidden();

        $response = $this->actingAs($claimant)->post(route('claims.store', $found->reference_no), [
            'ownership_description' => 'This is my blue phone lost at Station 2 last evening.',
            'distinguishing_details' => 'Lock screen shows my dog and IMEI ends in 8842.',
            'evidence' => [UploadedFile::fake()->image('receipt.jpg', 500, 400)],
        ]);

        $claim = Claim::query()->firstOrFail();
        $response->assertRedirect(route('claims.show_own', $claim));

        $this->assertSame('BLF-C-'.now()->year.'-000001', $claim->reference_no);
        $this->assertSame(ClaimStatus::Submitted, $claim->status);
        $this->assertSame(ItemStatus::ClaimPending, $found->fresh()->item_status);
        $this->assertCount(1, $claim->evidence);

        $evidence = $claim->evidence->firstOrFail();
        Storage::disk('local')->assertExists($evidence->private_path);

        $this->actingAs($claimant)->get(route('claims.evidence_own', [$claim, $evidence]))->assertOk();
        $this->actingAs($otherPublic)->get(route('claims.evidence_own', [$claim, $evidence]))->assertForbidden();
        $this->actingAs($balabagAdmin)->get(route('admin.claims.evidence', [$claim, $evidence]))->assertOk();
        $this->actingAs($yapakAdmin)->get(route('admin.claims.evidence', [$claim, $evidence]))->assertForbidden();
    }

    public function test_staff_can_request_info_claimant_can_respond_and_admin_approval_blocks_second_approval(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $admin = $this->createUserWithRole('institution_admin', $balabag);
        $staff = $this->createUserWithRole('institution_staff', $balabag);

        $claimant1 = User::factory()->create();
        $claimant2 = User::factory()->create();

        $found = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $balabag->id,
            'custody_organization_id' => $balabag->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
            'item_status' => ItemStatus::InCustody,
        ]);

        $this->actingAs($claimant1)->post(route('claims.store', $found->reference_no), [
            'ownership_description' => 'Claimant one ownership description for found item.',
            'distinguishing_details' => 'Has hidden serial mark A123.',
        ])->assertRedirect();

        $this->actingAs($claimant2)->post(route('claims.store', $found->reference_no), [
            'ownership_description' => 'Claimant two ownership description for found item.',
            'distinguishing_details' => 'Claims to recognize the case.',
        ])->assertRedirect();

        $claim1 = Claim::query()->where('claimant_user_id', $claimant1->id)->firstOrFail();
        $claim2 = Claim::query()->where('claimant_user_id', $claimant2->id)->firstOrFail();

        $this->actingAs($staff)
            ->patch(route('admin.claims.request_info', $claim1), ['remarks' => 'Please provide purchase receipt date.'])
            ->assertRedirect(route('admin.claims.show', $claim1));
        $this->assertSame(ClaimStatus::InfoRequested, $claim1->fresh()->status);

        $this->actingAs($claimant1)
            ->post(route('claims.respond', $claim1), ['details' => 'Purchased Jan 15, 2026 in Manila.'])
            ->assertRedirect(route('claims.show_own', $claim1));
        $this->assertSame(ClaimStatus::UnderReview, $claim1->fresh()->status);

        $this->actingAs($staff)->patch(route('admin.claims.approve', $claim1))->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.claims.approve', $claim1), [
                'turnover_scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'remarks' => 'Ownership verified.',
            ])
            ->assertRedirect(route('admin.claims.show', $claim1));
        $this->assertSame(ClaimStatus::Approved, $claim1->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.claims.approve', $claim2))
            ->assertSessionHasErrors('claim');
        $this->assertSame(ClaimStatus::Submitted, $claim2->fresh()->status);
    }

    public function test_withdrawing_or_rejecting_last_active_claim_reverts_item_to_in_custody(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $claimant = User::factory()->create();

        $found = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $balabag->id,
            'custody_organization_id' => $balabag->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
            'item_status' => ItemStatus::InCustody,
        ]);

        $this->actingAs($claimant)->post(route('claims.store', $found->reference_no), [
            'ownership_description' => 'I believe this found item belongs to me.',
            'distinguishing_details' => 'Blue strap attached.',
        ])->assertRedirect();

        $claim = Claim::query()->firstOrFail();
        $this->assertSame(ItemStatus::ClaimPending, $found->fresh()->item_status);

        $this->actingAs($claimant)->post(route('claims.withdraw', $claim))->assertRedirect();

        $this->assertSame(ClaimStatus::Withdrawn, $claim->fresh()->status);
        $this->assertSame(ItemStatus::InCustody, $found->fresh()->item_status);
    }

    protected function createUserWithRole(string $roleKey, ?Organization $organization = null): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('key', $roleKey)->firstOrFail();

        OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => $organization?->id,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
        ]);

        return $user;
    }
}
