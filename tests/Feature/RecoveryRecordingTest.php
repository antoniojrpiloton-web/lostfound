<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\CustodyAction;
use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ItemRecovery;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_drafts_recovery_and_admin_confirm_atomically_updates_item_lost_report_custody_and_competing_claims(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $yapak = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();
        $staff = $this->createUserWithRole('institution_staff', $balabag);
        $admin = $this->createUserWithRole('institution_admin', $balabag);
        $yapakAdmin = $this->createUserWithRole('institution_admin', $yapak);

        $owner = User::factory()->create(['name' => 'Maria Santos']);
        $otherClaimant = User::factory()->create();
        $category = Category::query()->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $lost = ItemReport::factory()->verified()->create([
            'reporter_user_id' => $owner->id,
            'handling_organization_id' => $balabag->id,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'item_status' => ItemStatus::Open,
        ]);

        $found = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $balabag->id,
            'custody_organization_id' => $balabag->id,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'item_status' => ItemStatus::ClaimPending,
        ]);

        $approvedClaim = Claim::factory()->create([
            'item_report_id' => $found->id,
            'lost_report_id' => $lost->id,
            'claimant_user_id' => $owner->id,
            'status' => ClaimStatus::Approved,
        ]);

        $competingClaim = Claim::factory()->create([
            'item_report_id' => $found->id,
            'claimant_user_id' => $otherClaimant->id,
            'status' => ClaimStatus::Submitted,
        ]);

        $this->actingAs($staff)
            ->post(route('admin.claims.recovery.store', $approvedClaim), [
                'received_by_name' => 'Maria Santos',
                'id_type' => 'Passport',
                'id_last4' => '4821',
                'verification_outcome' => 'verified',
                'confirm_now' => '1',
            ])
            ->assertRedirect(route('admin.claims.show', $approvedClaim));

        $recovery = ItemRecovery::query()->firstOrFail();
        $this->assertNull($recovery->confirmed_by);
        $this->assertSame(ItemStatus::ClaimPending, $found->fresh()->item_status);

        $this->actingAs($staff)->patch(route('admin.recoveries.confirm', $recovery))->assertForbidden();
        $this->actingAs($yapakAdmin)->patch(route('admin.recoveries.confirm', $recovery))->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.recoveries.confirm', $recovery))
            ->assertRedirect(route('admin.recoveries.index'));

        $this->assertSame($admin->id, $recovery->fresh()->confirmed_by);
        $this->assertSame(ClaimStatus::Completed, $approvedClaim->fresh()->status);
        $this->assertSame(ItemStatus::Returned, $found->fresh()->item_status);
        $this->assertSame(ItemStatus::Resolved, $lost->fresh()->item_status);
        $this->assertSame(ClaimStatus::Rejected, $competingClaim->fresh()->status);
        $this->assertSame(CustodyAction::Released, $found->custodyRecords()->latest('id')->firstOrFail()->action);
        $this->assertTrue(AuditLog::query()->where('action', 'recovery.confirmed')->where('auditable_id', $recovery->id)->exists());
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
