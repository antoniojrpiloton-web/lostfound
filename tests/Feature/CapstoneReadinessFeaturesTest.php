<?php

namespace Tests\Feature;

use App\Enums\CustodyAction;
use App\Enums\CustodyStage;
use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Matching\ItemMatchingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapstoneReadinessFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_viewer_and_datatable_work_for_authorized_admins(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdmin = $this->createUserWithRole('platform_super_admin');
        $org = Organization::query()->firstOrFail();

        AuditLog::query()->create([
            'user_id' => $superAdmin->id,
            'organization_id' => $org->id,
            'action' => 'report.verified',
            'auditable_type' => ItemReport::class,
            'auditable_id' => 1,
            'old_values' => ['verification_status' => 'pending'],
            'new_values' => ['verification_status' => 'verified'],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.audit_logs.index'))
            ->assertOk()
            ->assertSee('Audit Logs')
            ->assertSee('report.verified');

        $this->actingAs($superAdmin)
            ->getJson(route('admin.datatables.audit_logs', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_inter_agency_custody_transfer_updates_custody_logs_record_and_audits(): void
    {
        $this->seed(DatabaseSeeder::class);

        $orgs = Organization::query()->limit(2)->get();
        $fromOrg = $orgs[0];
        $toOrg = $orgs[1];

        $admin = $this->createUserWithRole('institution_admin', $fromOrg);
        $barangay = Barangay::query()->firstOrFail();
        $category = Category::query()->firstOrFail();

        $report = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'is_public' => true,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $fromOrg->id,
            'custody_organization_id' => $fromOrg->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.transfer_custody', $report), [
                'to_organization_id' => $toOrg->id,
                'remarks' => 'Transferred to Boracay Tourist Police Station.',
            ])
            ->assertRedirect(route('admin.reports.show', $report));

        $report->refresh();
        $this->assertSame($toOrg->id, $report->custody_organization_id);
        $this->assertSame($toOrg->id, $report->handling_organization_id);

        $this->assertDatabaseHas('item_custody_records', [
            'item_report_id' => $report->id,
            'organization_id' => $toOrg->id,
            'from_organization_id' => $fromOrg->id,
            'action' => CustodyAction::Transferred->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'custody.transferred',
            'auditable_type' => ItemReport::class,
            'auditable_id' => $report->id,
        ]);
    }

    public function test_system_health_page_and_printable_qr_reference_slips_render(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdmin = $this->createUserWithRole('platform_super_admin');
        $tourist = User::factory()->create();
        $org = Organization::query()->firstOrFail();
        $barangay = Barangay::query()->firstOrFail();
        $category = Category::query()->firstOrFail();

        $report = ItemReport::factory()->create([
            'type' => ReportType::Lost,
            'reporter_user_id' => $tourist->id,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $org->id,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.system_health.index'))
            ->assertOk()
            ->assertSee('System Health')
            ->assertSee('Run Database Backup Now');

        $this->actingAs($tourist)
            ->get(route('admin.system_health.index'))
            ->assertForbidden();

        $ownSlip = $this->actingAs($tourist)
            ->get(route('reports.reference_slip_own', $report))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $ownSlip->getContent());

        $adminSlip = $this->actingAs($superAdmin)
            ->get(route('admin.reports.reference_slip', $report))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $adminSlip->getContent());

        $adminPoster = $this->actingAs($superAdmin)
            ->get(route('admin.reports.fb_poster', $report))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", (string) $adminPoster->getContent());
    }

    public function test_finder_held_found_item_matches_lost_report_and_police_can_notify_finder_for_booth_return(): void
    {
        $this->seed(DatabaseSeeder::class);

        $org = Organization::query()->firstOrFail();
        $policeAdmin = $this->createUserWithRole('institution_admin', $org);
        $finder = User::factory()->create(['name' => 'Juan Finder']);
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();

        $lost = ItemReport::factory()->create([
            'type' => ReportType::Lost,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::Open,
            'is_public' => true,
            'category_id' => $category->id,
            'item_name' => 'Blue iPhone 15 Pro',
            'brand' => 'Apple',
            'color' => 'Blue',
            'description' => 'Lost near Station 2 beachfront with clear case.',
            'occurred_date' => now()->toDateString(),
            'handling_organization_id' => $org->id,
        ]);

        $found = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'reporter_user_id' => $finder->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::AwaitingTurnover,
            'is_public' => true,
            'category_id' => $category->id,
            'item_name' => 'Blue iPhone 15 Pro',
            'brand' => 'Apple',
            'color' => 'Blue',
            'description' => 'Found near Station 2 beachfront with clear case.',
            'occurred_date' => now()->toDateString(),
            'finder_name' => 'Juan Finder',
            'finder_contact_enc' => '09171234567',
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
        ]);

        $matches = app(ItemMatchingService::class)->matchForReport($found);
        $this->assertGreaterThan(0, $matches->count());
        $this->assertSame($lost->id, $matches->first()->lost_report_id);

        $this->actingAs($policeAdmin)
            ->post(route('admin.reports.notify_finder', $found), [
                'booth_location' => 'Boracay Tourist Police Booth — Station 2',
                'instructions' => 'A matching lost report was found. Please bring the phone tomorrow at 10 AM for supervised return.',
            ])
            ->assertRedirect(route('admin.reports.show', $found));

        $this->assertGreaterThan(0, $finder->notifications()->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finder.notified_for_booth_return',
            'auditable_type' => ItemReport::class,
            'auditable_id' => $found->id,
        ]);
    }

    public function test_owner_can_claim_finder_held_item_and_admin_can_bulk_return_booth_items_to_station(): void
    {
        $this->seed(DatabaseSeeder::class);

        $org = Organization::query()->firstOrFail();
        $policeAdmin = $this->createUserWithRole('institution_admin', $org);
        $owner = User::factory()->create();
        $category = Category::query()->firstOrFail();

        $finderHeld = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::AwaitingTurnover,
            'custody_stage' => CustodyStage::WithFinder,
            'is_public' => true,
            'category_id' => $category->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
        ]);

        $this->actingAs($owner)
            ->post(route('claims.store', $finderHeld->reference_no), [
                'ownership_description' => 'I lost this exact item near Station 2 beach.',
                'distinguishing_details' => 'Has my initials engraved on the back.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('claims', [
            'item_report_id' => $finderHeld->id,
            'claimant_user_id' => $owner->id,
        ]);

        $boothItem = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'custody_stage' => CustodyStage::AtBooth,
            'booth_location' => 'Station 2 Beachfront Police Tent',
            'is_public' => true,
            'category_id' => $category->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
        ]);

        $this->actingAs($policeAdmin)
            ->post(route('admin.reports.return_all_booth_to_station'))
            ->assertRedirect(route('admin.reports.index'));

        $boothItem->refresh();
        $this->assertSame(CustodyStage::AtStation, $boothItem->custody_stage);
        $this->assertNotNull($boothItem->station_transferred_at);
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
        ]);

        return $user;
    }
}
