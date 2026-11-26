<?php

namespace Tests\Feature;

use App\Enums\CustodyAction;
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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FoundItemRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_user_must_upload_photo_when_registering_found_item(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();

        $this->actingAs($user)
            ->post(route('reports.found.store'), [
                'item_name' => 'Found Smartwatch',
                'category_id' => $category->id,
                'description' => 'Found near Station 1 beachfront bench.',
                'occurred_date' => now()->toDateString(),
                'barangay_id' => $barangay->id,
                'custody_organization_id' => $org->id,
            ])
            ->assertSessionHasErrors('photos');
    }

    public function test_public_user_can_register_found_item_with_photo_and_awaiting_turnover_status(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'cameras-action-cams')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $org = Organization::query()->where('code', 'PNP-MALAY-BTAC')->firstOrFail();

        $response = $this->actingAs($user)->post(route('reports.found.store'), [
            'item_name' => 'GoPro Hero 12',
            'category_id' => $category->id,
            'description' => 'Found near Station 2 shoreline with floating grip.',
            'occurred_date' => now()->toDateString(),
            'barangay_id' => $barangay->id,
            'custody_organization_id' => $org->id,
            'finder_name' => 'Juan Dela Cruz',
            'finder_contact' => '+639181234567',
            'photos' => [UploadedFile::fake()->image('gopro.jpg', 800, 600)],
        ]);

        $report = ItemReport::query()->firstOrFail();
        $response->assertRedirect(route('reports.show_own', $report));

        $this->assertSame('BLF-F-'.now()->year.'-000001', $report->reference_no);
        $this->assertSame(ReportType::Found, $report->type);
        $this->assertSame(VerificationStatus::Pending, $report->verification_status);
        $this->assertSame(ItemStatus::AwaitingTurnover, $report->item_status);
        $this->assertSame($org->id, $report->custody_organization_id);
        $this->assertSame('+639181234567', $report->finder_contact_enc);
        $this->assertNotSame('+639181234567', DB::table('item_reports')->where('id', $report->id)->value('finder_contact_enc'));
        $this->assertCount(1, $report->images);
        $this->assertCount(0, $report->custodyRecords);
    }

    public function test_institution_staff_registers_found_item_in_custody_but_cannot_approve_photo_exception(): void
    {
        Storage::fake('local');

        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $staff = $this->createUserWithRole('institution_staff', $balabag);
        $category = Category::query()->where('slug', 'wallets-purses')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $this->actingAs($staff)
            ->post(route('admin.found.store'), [
                'item_name' => 'Passport Without Photo',
                'category_id' => $category->id,
                'description' => 'Attempted photo exception by staff.',
                'occurred_date' => now()->toDateString(),
                'barangay_id' => $barangay->id,
                'finder_name' => 'Walk-in Finder',
                'photo_exception_reason' => 'Sensitive document',
            ])
            ->assertSessionHasErrors(['photo_exception_reason', 'photos']);

        $this->actingAs($staff)
            ->post(route('admin.found.store'), [
                'item_name' => 'Black Dry Bag',
                'category_id' => $category->id,
                'description' => 'Surrendered by boatman at Barangay Balabag hall.',
                'occurred_date' => now()->toDateString(),
                'barangay_id' => $barangay->id,
                'finder_name' => 'Boatman Pedro',
                'photos' => [UploadedFile::fake()->image('bag.jpg', 640, 480)],
            ])
            ->assertRedirect(route('admin.dashboard'));

        $report = ItemReport::query()->where('item_name', 'Black Dry Bag')->firstOrFail();
        $this->assertSame(ItemStatus::InCustody, $report->item_status);
        $this->assertNotNull($report->received_at);
        $this->assertSame($balabag->id, $report->custody_organization_id);
        $this->assertCount(1, $report->custodyRecords);
        $this->assertSame(CustodyAction::Received, $report->custodyRecords->firstOrFail()->action);
    }

    public function test_institution_admin_can_register_found_item_with_approved_photo_exception(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $admin = $this->createUserWithRole('institution_admin', $balabag);
        $category = Category::query()->where('slug', 'passports')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.found.store'), [
                'item_name' => 'Foreign Passport',
                'category_id' => $category->id,
                'description' => 'Sealed in evidence envelope for privacy protection.',
                'occurred_date' => now()->toDateString(),
                'barangay_id' => $barangay->id,
                'finder_name' => 'Hotel Front Desk',
                'photo_exception_reason' => 'Sealed government passport — privacy restriction',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $report = ItemReport::query()->where('item_name', 'Foreign Passport')->firstOrFail();
        $this->assertSame($admin->id, $report->photo_exception_by);
        $this->assertSame('Sealed government passport — privacy restriction', $report->photo_exception_reason);
        $this->assertSame(ItemStatus::InCustody, $report->item_status);
        $this->assertCount(1, $report->custodyRecords);
    }

    public function test_desk_found_precheck_ajax_returns_similar_open_lost_items(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $staff = $this->createUserWithRole('institution_staff', $balabag);
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $lost = ItemReport::factory()->verified()->create([
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'item_name' => 'Blue iPhone 15 Pro',
            'search_text' => 'blue iphone 15 pro balabag',
        ]);

        $response = $this->actingAs($staff)->postJson(route('admin.found.precheck'), [
            'photo' => UploadedFile::fake()->image('found-phone.jpg', 640, 480),
            'keyword' => 'iPhone',
            'category_id' => $category->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('has_matches', true)
            ->assertJsonPath('matches.0.reference_no', $lost->reference_no);
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
