<?php

namespace Tests\Feature;

use App\Enums\CustodyAction;
use App\Enums\DisposalStatus;
use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemDisposal;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisposalMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(SystemSettingSeeder::class);
    }

    public function test_retention_command_flags_old_unclaimed_found_items_without_auto_disposing(): void
    {
        $org = Organization::factory()->create();
        $category = Category::factory()->create();
        $barangay = Barangay::factory()->create();

        $oldFound = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'occurred_date' => now()->subDays(200)->toDateString(),
            'is_public' => true,
        ]);

        $recentFound = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'occurred_date' => now()->subDays(30)->toDateString(),
            'is_public' => true,
        ]);

        $this->artisan('blf:flag-disposal-eligible')->assertSuccessful();

        $this->assertDatabaseHas('item_disposals', [
            'item_report_id' => $oldFound->id,
            'organization_id' => $org->id,
            'status' => DisposalStatus::Eligible->value,
        ]);
        $this->assertDatabaseMissing('item_disposals', [
            'item_report_id' => $recentFound->id,
        ]);

        $this->assertSame(ItemStatus::DisposalPending, $oldFound->fresh()->item_status);
        $this->assertTrue($oldFound->fresh()->is_public);
    }

    public function test_staff_can_propose_only_admin_can_approve_and_complete_with_documents(): void
    {
        Storage::fake('local');

        $org = Organization::factory()->create();
        $otherOrg = Organization::factory()->create();
        $category = Category::factory()->create();
        $barangay = Barangay::factory()->create();

        $staff = $this->createOrgUser($org, 'institution_staff');
        $admin = $this->createOrgUser($org, 'institution_admin');
        $otherAdmin = $this->createOrgUser($otherOrg, 'institution_admin');

        $found = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::DisposalPending,
            'is_public' => true,
        ]);

        $disposal = ItemDisposal::query()->create([
            'item_report_id' => $found->id,
            'organization_id' => $org->id,
            'eligible_on' => now()->toDateString(),
            'status' => DisposalStatus::Eligible,
        ]);

        $this->actingAs($otherAdmin)
            ->get(route('admin.disposals.show', $disposal))
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('admin.disposals.propose', $disposal), [
                'method' => 'donation',
                'remarks' => 'Unclaimed for over 180 days.',
            ])
            ->assertRedirect(route('admin.disposals.show', $disposal));

        $this->assertSame(DisposalStatus::Proposed, $disposal->fresh()->status);

        $this->actingAs($staff)
            ->patch(route('admin.disposals.approve', $disposal))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.disposals.approve', $disposal), [
                'remarks' => 'Approved by Barangay Captain.',
            ])
            ->assertRedirect(route('admin.disposals.show', $disposal));

        $this->assertSame(DisposalStatus::Approved, $disposal->fresh()->status);

        $doc = UploadedFile::fake()->create('disposal-certificate.pdf', 100, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('admin.disposals.complete', $disposal), [
                'disposed_on' => now()->toDateString(),
                'method' => 'donation',
                'remarks' => 'Turned over to Municipal Social Welfare.',
                'documents' => [$doc],
            ])
            ->assertRedirect(route('admin.disposals.show', $disposal));

        $disposal->refresh();
        $found->refresh();

        $this->assertSame(DisposalStatus::Completed, $disposal->status);
        $this->assertSame(ItemStatus::Disposed, $found->item_status);
        $this->assertFalse($found->is_public);
        $this->assertCount(1, $disposal->documents);
        $this->assertDatabaseHas('item_custody_records', [
            'item_report_id' => $found->id,
            'action' => CustodyAction::Disposed->value,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.disposals.document', [$disposal, $disposal->documents->first()]))
            ->assertOk();
    }

    protected function createOrgUser(Organization $org, string $roleKey): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('key', $roleKey)->firstOrFail();

        OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
        ]);

        return $user;
    }
}
