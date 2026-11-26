<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LostItemReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guests_are_redirected_to_login_for_lost_reporting_and_my_reports(): void
    {
        $this->get(route('reports.lost.create'))->assertRedirect(route('login'));
        $this->post(route('reports.lost.store'))->assertRedirect(route('login'));
        $this->get(route('reports.mine'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_submit_lost_report_without_photos_and_with_optional_reward(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $btacLocation = Location::query()->where('name', 'Boracay Tourist Assistance Center Area')->firstOrFail();
        $btacOrg = Organization::query()->where('code', 'PNP-MALAY-BTAC')->firstOrFail();

        $response = $this->actingAs($user)->post(route('reports.lost.store'), [
            'item_name' => 'Blue iPhone 15',
            'category_id' => $category->id,
            'description' => 'Lost near the tourist assistance center after sunset walk.',
            'brand' => 'Apple',
            'model' => 'iPhone 15',
            'color' => 'Blue',
            'distinguishing_features' => 'Clear case with turtle sticker on the back',
            'occurred_date' => now()->subDay()->toDateString(),
            'occurred_time' => '18:30',
            'barangay_id' => $barangay->id,
            'location_id' => $btacLocation->id,
            'location_details' => 'Bench near main pathwalk',
            'reward_offered' => '1',
            'reward_details' => 'Cash appreciation upon verified return',
            'prefer_in_app' => '1',
            'prefer_email' => '1',
            'contact_phone' => '+639171112222',
        ]);

        $report = ItemReport::query()->firstOrFail();
        $response->assertRedirect(route('reports.show_own', $report));

        $this->assertSame('BLF-L-'.now()->year.'-000001', $report->reference_no);
        $this->assertSame(ReportType::Lost, $report->type);
        $this->assertSame(VerificationStatus::Pending, $report->verification_status);
        $this->assertSame(ItemStatus::Open, $report->item_status);
        $this->assertFalse($report->is_public);
        $this->assertSame($btacOrg->id, $report->handling_organization_id);
        $this->assertTrue($report->reward_offered);
        $this->assertSame('+639171112222', $report->contact_phone_enc);
        $this->assertNotSame('+639171112222', DB::table('item_reports')->where('id', $report->id)->value('contact_phone_enc'));
        $this->assertStringContainsString('blue iphone 15', (string) $report->search_text);
        $this->assertCount(2, $report->statusHistories);
        $this->assertTrue(AuditLog::query()->where('action', 'report.lost_submitted')->where('auditable_id', $report->id)->exists());
    }

    public function test_authenticated_user_can_submit_lost_report_with_optional_photos_and_view_only_own_reports(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::query()->where('slug', 'wallets-purses')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-YAPAK')->firstOrFail();
        $yapakOrg = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();

        $photo = UploadedFile::fake()->image('wallet.jpg', 640, 480);

        $this->actingAs($owner)->post(route('reports.lost.store'), [
            'item_name' => 'Brown Leather Wallet',
            'category_id' => $category->id,
            'description' => 'Dropped near Puka Shell Beach parking area.',
            'occurred_date' => now()->toDateString(),
            'barangay_id' => $barangay->id,
            'photos' => [$photo],
        ])->assertRedirect();

        $report = ItemReport::query()->firstOrFail();
        $this->assertSame($yapakOrg->id, $report->handling_organization_id);
        $this->assertCount(1, $report->images);

        $image = $report->images->firstOrFail();
        Storage::disk('local')->assertExists($image->private_path);

        $this->actingAs($owner)
            ->get(route('reports.mine'))
            ->assertOk()
            ->assertSee($report->reference_no);

        $this->actingAs($owner)
            ->get(route('reports.show_own', $report))
            ->assertOk()
            ->assertSee('Brown Leather Wallet');

        $this->actingAs($owner)
            ->get(route('reports.image_own', [$report, $image]))
            ->assertOk();

        $this->actingAs($otherUser)
            ->get(route('reports.mine'))
            ->assertOk()
            ->assertDontSee($report->reference_no);

        $this->actingAs($otherUser)
            ->get(route('reports.show_own', $report))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->get(route('reports.image_own', [$report, $image]))
            ->assertForbidden();
    }

    public function test_lost_report_precheck_suggests_matching_verified_found_items_via_ajax(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'wallets-purses')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $found = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'item_name' => 'Black Leather Coach Wallet',
            'search_text' => 'black leather coach wallet station 2',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'is_public' => true,
            'is_confidential' => false,
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.lost.precheck'), [
                'keyword' => 'Coach Wallet',
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('has_matches', true)
            ->assertJsonPath('matches.0.reference_no', $found->reference_no);

        $this->actingAs($user)
            ->postJson(route('reports.lost.precheck'), [
                'keyword' => 'UnicornSpaceship999',
            ])
            ->assertOk()
            ->assertJsonPath('has_matches', false);
    }
}
