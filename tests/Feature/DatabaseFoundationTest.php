<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\CustodyAction;
use App\Enums\DisposalStatus;
use App\Enums\ImageValidationStatus;
use App\Enums\MatchStatus;
use App\Enums\MembershipStatus;
use App\Enums\StatusDimension;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ItemCustodyRecord;
use App\Models\ItemDisposal;
use App\Models\ItemImage;
use App\Models\ItemMatch;
use App\Models\ItemRecovery;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\ReportStatusHistory;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_populate_master_data_and_are_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Role::query()->count());
        $this->assertSame(35, Permission::query()->count());
        $this->assertSame(3, Barangay::query()->count());
        $this->assertSame(4, Organization::query()->count());
        $this->assertSame(13, Location::query()->count());
        $this->assertSame(19, Category::query()->count());
        $this->assertSame(19, SystemSetting::query()->count());

        $publicRole = Role::query()->where('key', 'public_user')->firstOrFail();
        $this->assertTrue($publicRole->permissions()->where('key', 'claim.create')->exists());

        $superAdminRole = Role::query()->where('key', 'platform_super_admin')->firstOrFail();
        $this->assertFalse($superAdminRole->permissions()->where('key', 'claim.create')->exists());

        $electronics = Category::query()->where('slug', 'electronics')->firstOrFail();
        $this->assertSame('Electronics', $electronics->label('en'));
        $this->assertSame('Elektroniks', $electronics->label('fil'));
        $this->assertSame(4, $electronics->children()->count());
    }

    public function test_core_model_relationships_work(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $location = Location::query()->where('barangay_id', $barangay->id)->firstOrFail();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $staffRole = Role::query()->where('key', 'institution_staff')->firstOrFail();

        $membership = OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'role_id' => $staffRole->id,
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
        ]);

        $this->assertTrue($user->memberships()->whereKey($membership->id)->exists());
        $this->assertTrue($org->barangays()->whereKey($barangay->id)->exists());

        $lost = ItemReport::factory()->verified()->create([
            'reporter_user_id' => $user->id,
            'handling_organization_id' => $org->id,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'location_id' => $location->id,
            'contact_phone_enc' => '+639171234567',
            'search_text' => 'black iphone 15 pro balabag station 2',
        ]);

        $rawContact = DB::table('item_reports')->where('id', $lost->id)->value('contact_phone_enc');
        $this->assertNotSame('+639171234567', $rawContact);
        $this->assertSame('+639171234567', $lost->fresh()->contact_phone_enc);

        $found = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'location_id' => $location->id,
            'search_text' => 'black iphone 15 pro found near station 2',
        ]);

        ItemImage::query()->create([
            'item_report_id' => $found->id,
            'private_path' => 'private/items/sample.jpg',
            'mime' => 'image/jpeg',
            'size' => 102400,
            'sha256' => hash('sha256', 'sample'),
            'validation_status' => ImageValidationStatus::Passed,
            'is_primary' => true,
        ]);

        ReportStatusHistory::query()->create([
            'item_report_id' => $found->id,
            'dimension' => StatusDimension::Verification,
            'from_status' => 'pending',
            'to_status' => 'verified',
            'changed_by' => $user->id,
        ]);

        ItemMatch::query()->create([
            'lost_report_id' => $lost->id,
            'found_report_id' => $found->id,
            'score' => 0.8750,
            'factors' => ['category' => 1.0, 'color' => 1.0],
            'algorithm_version' => 'meta-v1',
            'status' => MatchStatus::Suggested,
        ]);

        $claim = Claim::factory()->create([
            'item_report_id' => $found->id,
            'lost_report_id' => $lost->id,
            'claimant_user_id' => $user->id,
            'status' => ClaimStatus::Completed,
        ]);

        ItemRecovery::query()->create([
            'item_report_id' => $found->id,
            'claim_id' => $claim->id,
            'organization_id' => $org->id,
            'claimant_user_id' => $user->id,
            'received_by_name' => $user->name,
            'id_type' => 'Passport',
            'id_last4' => '4821',
            'turnover_at' => now(),
            'verification_outcome' => 'verified',
        ]);

        ItemCustodyRecord::query()->create([
            'item_report_id' => $found->id,
            'organization_id' => $org->id,
            'action' => CustodyAction::Received,
            'recorded_by' => $user->id,
        ]);

        $otherFound = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
        ]);

        ItemDisposal::query()->create([
            'item_report_id' => $otherFound->id,
            'organization_id' => $org->id,
            'eligible_on' => now()->addMonths(6)->toDateString(),
            'status' => DisposalStatus::Eligible,
        ]);

        $this->assertCount(1, $found->images);
        $this->assertCount(1, $found->statusHistories);
        $this->assertCount(1, $found->matchesAsFound);
        $this->assertCount(1, $lost->matchesAsLost);
        $this->assertCount(1, $found->claims);
        $this->assertNotNull($found->recovery);
        $this->assertCount(1, $found->custodyRecords);
        $this->assertNotNull($otherFound->disposal);
    }

    public function test_database_blocks_multiple_completed_claims_for_same_item(): void
    {
        $found = ItemReport::factory()->found()->verified()->create();

        Claim::factory()->create([
            'item_report_id' => $found->id,
            'status' => ClaimStatus::Submitted,
        ]);

        Claim::factory()->create([
            'item_report_id' => $found->id,
            'status' => ClaimStatus::Completed,
        ]);

        $this->expectException(QueryException::class);

        Claim::factory()->create([
            'item_report_id' => $found->id,
            'status' => ClaimStatus::Completed,
        ]);
    }

    public function test_database_blocks_duplicate_platform_membership_for_same_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $superAdminRole = Role::query()->where('key', 'platform_super_admin')->firstOrFail();

        OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => null,
            'role_id' => $superAdminRole->id,
            'status' => MembershipStatus::Active,
        ]);

        $this->expectException(QueryException::class);

        OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => null,
            'role_id' => $superAdminRole->id,
            'status' => MembershipStatus::Active,
        ]);
    }

    public function test_database_enforces_check_constraints(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Invalid Status User',
            'email' => 'invalid-status@example.com',
            'status' => 'deleted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_fulltext_index_exists_on_item_reports_search_text(): void
    {
        $indexes = DB::select("SHOW INDEX FROM item_reports WHERE Index_type = 'FULLTEXT'");

        $this->assertNotEmpty($indexes);
        $this->assertSame('search_text', $indexes[0]->Column_name);
    }
}
