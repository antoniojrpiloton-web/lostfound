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

class SecurityAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_protected_and_admin_routes(): void
    {
        $protectedRoutes = [
            route('reports.mine'),
            route('claims.mine'),
            route('notifications.index'),
            route('admin.dashboard'),
            route('admin.reports.index'),
            route('admin.claims.index'),
            route('admin.disposals.index'),
            route('admin.analytics.index'),
            route('admin.settings.index'),
        ];

        foreach ($protectedRoutes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_public_user_is_forbidden_from_all_institutional_admin_routes(): void
    {
        $publicUser = User::factory()->create();

        $adminRoutes = [
            route('admin.dashboard'),
            route('admin.reports.index'),
            route('admin.claims.index'),
            route('admin.disposals.index'),
            route('admin.analytics.index'),
            route('admin.analytics.export.csv'),
            route('admin.settings.index'),
        ];

        foreach ($adminRoutes as $url) {
            $this->actingAs($publicUser)->get($url)->assertForbidden();
        }
    }

    public function test_institution_staff_cannot_access_platform_or_admin_only_endpoints(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createOrgUser($org, 'institution_staff');

        $this->actingAs($staff)->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.organizations.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.analytics.export.csv'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.analytics.export.pdf'))->assertForbidden();
    }

    public function test_public_pages_never_expose_confidential_unverified_or_personal_contact_data(): void
    {
        $reporter = User::factory()->create(['email' => 'private-owner@example.com']);
        $category = Category::factory()->create();
        $barangay = Barangay::factory()->create();

        $publicReport = ItemReport::factory()->create([
            'reporter_user_id' => $reporter->id,
            'type' => ReportType::Found,
            'item_name' => 'Verified Public Umbrella',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'is_public' => true,
            'is_confidential' => false,
            'contact_phone_enc' => '0917-555-0199',
            'finder_contact_enc' => '0918-555-0188',
        ]);

        $confidentialReport = ItemReport::factory()->create([
            'type' => ReportType::Lost,
            'item_name' => 'Confidential Passport Case',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::Open,
            'is_public' => true,
            'is_confidential' => true,
        ]);

        $this->get(route('public.items.show', $publicReport->reference_no))
            ->assertOk()
            ->assertSee('Verified Public Umbrella')
            ->assertDontSee('private-owner@example.com')
            ->assertDontSee('0917-555-0199')
            ->assertDontSee('0918-555-0188');

        $this->get(route('public.items.show', $confidentialReport->reference_no))
            ->assertNotFound();
    }

    public function test_route_model_ids_are_encrypted_and_reject_raw_numeric_or_tampered_ids(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgUser($org, 'institution_admin');
        $category = Category::factory()->create();
        $barangay = Barangay::factory()->create();

        $report = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
        ]);

        $encryptedUrl = route('admin.reports.show', $report);
        $this->assertStringNotContainsString('/admin/reports/'.$report->id, $encryptedUrl);

        $this->actingAs($admin)->get($encryptedUrl)->assertOk();
        $this->actingAs($admin)->get('/admin/reports/'.$report->id)->assertNotFound();
        $this->actingAs($admin)->get('/admin/reports/tampered-invalid-token')->assertNotFound();
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
