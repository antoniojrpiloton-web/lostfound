<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dashboard_and_analytics_compute_org_scoped_metrics_from_real_database(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Barangay Balabag Desk']);
        $orgB = Organization::factory()->create(['name' => 'Barangay Yapak Desk']);
        $barangay = Barangay::factory()->create(['name' => 'Balabag']);
        $category = Category::factory()->create();

        $adminA = $this->createOrgUser($orgA, 'institution_admin');
        $staffA = $this->createOrgUser($orgA, 'institution_staff');

        ItemReport::factory()->create([
            'reference_no' => 'BLF-L-2026-000101',
            'type' => ReportType::Lost,
            'item_name' => 'Org A Lost Wallet',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $orgA->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::Open,
            'occurred_date' => now()->toDateString(),
        ]);

        ItemReport::factory()->create([
            'reference_no' => 'BLF-F-2026-000102',
            'type' => ReportType::Found,
            'item_name' => 'Org A Found Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $orgA->id,
            'custody_organization_id' => $orgA->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'occurred_date' => now()->toDateString(),
        ]);

        ItemReport::factory()->create([
            'reference_no' => 'BLF-F-2026-000999',
            'type' => ReportType::Found,
            'item_name' => 'Org B Secret Item',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $orgB->id,
            'custody_organization_id' => $orgB->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'occurred_date' => now()->toDateString(),
        ]);

        $this->actingAs($adminA)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['total_lost'] === 1
                    && $stats['total_found'] === 1
                    && $stats['verified'] === 2
                    && $stats['unclaimed'] === 1;
            });

        $this->actingAs($adminA)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Barangay Balabag Desk')
            ->assertDontSee('Barangay Yapak Desk');

        $this->actingAs($staffA)
            ->get(route('admin.analytics.export.csv'))
            ->assertForbidden();

        $csvResponse = $this->actingAs($adminA)
            ->get(route('admin.analytics.export.csv'))
            ->assertOk();

        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('BLF-L-2026-000101', $csvContent);
        $this->assertStringContainsString('BLF-F-2026-000102', $csvContent);
        $this->assertStringNotContainsString('BLF-F-2026-000999', $csvContent);

        $pdfResponse = $this->actingAs($adminA)
            ->get(route('admin.analytics.export.pdf'))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));
        $this->assertNotEmpty($pdfResponse->getContent());
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
