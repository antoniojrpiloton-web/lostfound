<?php

namespace Tests\Feature;

use App\Enums\CustodyAction;
use App\Enums\ImageValidationStatus;
use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemImage;
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

class ReportVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_institution_admin_can_verify_publish_and_generate_public_image_derivative(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $yapak = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();
        $balabagAdmin = $this->createUserWithRole('institution_admin', $balabag);
        $balabagStaff = $this->createUserWithRole('institution_staff', $balabag);
        $yapakAdmin = $this->createUserWithRole('institution_admin', $yapak);

        $report = ItemReport::factory()->create([
            'handling_organization_id' => $balabag->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
            'verification_status' => VerificationStatus::Pending,
            'item_status' => ItemStatus::Open,
            'is_public' => false,
        ]);

        $file = UploadedFile::fake()->image('item.jpg', 400, 300);
        $privatePath = "item-images/private/{$report->id}/sample.jpg";
        Storage::disk('local')->put($privatePath, $file->get());

        $image = ItemImage::query()->create([
            'item_report_id' => $report->id,
            'private_path' => $privatePath,
            'mime' => 'image/jpeg',
            'size' => 12345,
            'sha256' => hash('sha256', 'sample'),
            'validation_status' => ImageValidationStatus::Passed,
            'is_primary' => true,
        ]);

        $this->actingAs($balabagStaff)->patch(route('admin.reports.verify', $report))->assertForbidden();
        $this->actingAs($yapakAdmin)->patch(route('admin.reports.verify', $report))->assertForbidden();
        $this->actingAs($yapakAdmin)->get(route('admin.reports.show', $report))->assertForbidden();

        $this->actingAs($balabagAdmin)
            ->patch(route('admin.reports.verify', $report), ['remarks' => 'Verified with complainant'])
            ->assertRedirect(route('admin.reports.show', $report));

        $report->refresh();
        $this->assertSame(VerificationStatus::Verified, $report->verification_status);
        $this->assertTrue($report->is_public);
        $this->assertSame($balabagAdmin->id, $report->verified_by);
        $this->assertSame('Lost', $report->publicStatusLabel());
        $this->assertNotNull($image->fresh()->public_path);
        Storage::disk('public')->assertExists($image->fresh()->public_path);
        $this->assertTrue(AuditLog::query()->where('action', 'report.verified')->where('auditable_id', $report->id)->exists());
    }

    public function test_institution_admin_can_reject_and_request_revision_with_history(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $admin = $this->createUserWithRole('institution_admin', $balabag);

        $report = ItemReport::factory()->create([
            'handling_organization_id' => $balabag->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.request_revision', $report), ['remarks' => 'Please clarify the exact landmark.'])
            ->assertRedirect(route('admin.reports.show', $report));

        $this->assertSame(VerificationStatus::NeedsRevision, $report->fresh()->verification_status);

        $this->actingAs($admin)
            ->patch(route('admin.reports.reject', $report), ['rejection_reason' => 'Duplicate submission of an existing report.'])
            ->assertRedirect(route('admin.reports.show', $report));

        $report->refresh();
        $this->assertSame(VerificationStatus::Rejected, $report->verification_status);
        $this->assertFalse($report->is_public);
        $this->assertSame('Rejected', $report->publicStatusLabel());
        $this->assertCount(2, $report->statusHistories);
    }

    public function test_staff_can_confirm_physical_turnover_of_found_item_into_custody(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $staff = $this->createUserWithRole('institution_staff', $balabag);

        $found = ItemReport::factory()->found()->create([
            'handling_organization_id' => $balabag->id,
            'custody_organization_id' => $balabag->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
            'item_status' => ItemStatus::AwaitingTurnover,
            'received_at' => null,
        ]);

        $this->actingAs($staff)
            ->patch(route('admin.reports.confirm_turnover', $found), ['remarks' => 'Received at desk'])
            ->assertRedirect(route('admin.reports.show', $found));

        $found->refresh();
        $this->assertSame(ItemStatus::InCustody, $found->item_status);
        $this->assertNotNull($found->received_at);
        $this->assertSame(CustodyAction::Received, $found->custodyRecords()->firstOrFail()->action);
    }

    public function test_reporter_can_withdraw_pending_report_and_public_status_labels_map_correctly(): void
    {
        $reporter = User::factory()->create();
        $report = ItemReport::factory()->create([
            'reporter_user_id' => $reporter->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
            'verification_status' => VerificationStatus::Pending,
            'item_status' => ItemStatus::Open,
        ]);

        $this->actingAs($reporter)
            ->post(route('reports.withdraw', $report))
            ->assertRedirect(route('reports.show_own', $report));

        $report->refresh();
        $this->assertSame(VerificationStatus::Withdrawn, $report->verification_status);
        $this->assertSame(ItemStatus::Closed, $report->item_status);

        $claimPending = ItemReport::factory()->found()->verified()->create([
            'item_status' => ItemStatus::ClaimPending,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
        ]);
        $this->assertSame('Claim Pending', $claimPending->publicStatusLabel());

        $returned = ItemReport::factory()->found()->verified()->create([
            'item_status' => ItemStatus::Returned,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
        ]);
        $this->assertSame('Claimed', $returned->publicStatusLabel());
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
