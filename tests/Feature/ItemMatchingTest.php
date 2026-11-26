<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemMatch;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Matching\ItemMatchingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_matching_engine_scores_similar_pair_high_and_ignores_unrelated_pair(): void
    {
        $phones = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $wallets = Category::query()->where('slug', 'wallets-purses')->firstOrFail();
        $balabag = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $yapak = Barangay::query()->where('code', 'BRGY-YAPAK')->firstOrFail();
        $station2 = Location::query()->where('name', 'Station 2 Beachfront')->firstOrFail();

        $lostPhone = ItemReport::factory()->verified()->create([
            'category_id' => $phones->id,
            'barangay_id' => $balabag->id,
            'location_id' => $station2->id,
            'item_name' => 'Blue iPhone 15 Pro',
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'color' => 'Navy',
            'description' => 'Lost near Station 2 beachfront with clear case.',
            'distinguishing_features' => 'Turtle sticker on back.',
            'occurred_date' => '2026-11-20',
        ]);

        $foundPhone = ItemReport::factory()->found()->verified()->create([
            'category_id' => $phones->id,
            'barangay_id' => $balabag->id,
            'location_id' => $station2->id,
            'item_name' => 'Blue Cellphone iPhone 15 Pro',
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'color' => 'Blue',
            'description' => 'Found near Station 2 beachfront inside clear case.',
            'distinguishing_features' => 'Has sea turtle sticker on back.',
            'occurred_date' => '2026-11-21',
        ]);

        $unrelatedWallet = ItemReport::factory()->found()->verified()->create([
            'category_id' => $wallets->id,
            'barangay_id' => $yapak->id,
            'item_name' => 'Red Leather Wallet',
            'brand' => 'Coach',
            'color' => 'Red',
            'description' => 'Found near Puka Shell Beach.',
            'occurred_date' => '2026-11-21',
        ]);

        $matches = app(ItemMatchingService::class)->matchForReport($lostPhone);

        $this->assertCount(1, $matches);
        $match = $matches->firstOrFail();
        $this->assertSame($foundPhone->id, $match->found_report_id);
        $this->assertGreaterThan(0.75, (float) $match->score);
        $this->assertSame(ItemMatchingService::ALGORITHM_VERSION, $match->algorithm_version);
        $this->assertSame(MatchStatus::Suggested, $match->status);
        $this->assertArrayHasKey('category', $match->factors);
        $this->assertArrayHasKey('color', $match->factors);
        $this->assertFalse(ItemMatch::query()->where('found_report_id', $unrelatedWallet->id)->exists());
    }

    public function test_verification_automatically_runs_matching_and_admin_review_enforces_permissions(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $btac = Organization::query()->where('code', 'PNP-MALAY-BTAC')->firstOrFail();
        $yapak = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();

        $balabagAdmin = $this->createUserWithRole('institution_admin', $balabag);
        $balabagStaff = $this->createUserWithRole('institution_staff', $balabag);
        $yapakAdmin = $this->createUserWithRole('institution_admin', $yapak);

        $phones = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $balabagBrgy = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $station2 = Location::query()->where('name', 'Station 2 Beachfront')->firstOrFail();

        ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $btac->id,
            'custody_organization_id' => $btac->id,
            'category_id' => $phones->id,
            'barangay_id' => $balabagBrgy->id,
            'location_id' => $station2->id,
            'item_name' => 'Black Samsung Galaxy S24',
            'brand' => 'Samsung',
            'model' => 'Galaxy S24',
            'color' => 'Black',
            'description' => 'Found near Station 2 beachfront.',
            'occurred_date' => now()->toDateString(),
        ]);

        $pendingLost = ItemReport::factory()->create([
            'handling_organization_id' => $balabag->id,
            'category_id' => $phones->id,
            'barangay_id' => $balabagBrgy->id,
            'location_id' => $station2->id,
            'item_name' => 'Black Samsung Galaxy S24',
            'brand' => 'Samsung',
            'model' => 'Galaxy S24',
            'color' => 'Black',
            'description' => 'Lost near Station 2 beachfront.',
            'occurred_date' => now()->toDateString(),
        ]);

        $this->actingAs($balabagAdmin)
            ->patch(route('admin.reports.verify', $pendingLost))
            ->assertRedirect();

        $match = ItemMatch::query()->where('lost_report_id', $pendingLost->id)->firstOrFail();

        $this->actingAs($balabagAdmin)->get(route('admin.matches.index'))->assertOk()->assertSee($pendingLost->reference_no);
        $this->actingAs($balabagStaff)->patch(route('admin.matches.decide', $match), ['status' => 'possible'])->assertForbidden();
        $this->actingAs($yapakAdmin)->patch(route('admin.matches.decide', $match), ['status' => 'possible'])->assertForbidden();

        $this->actingAs($balabagAdmin)
            ->patch(route('admin.matches.decide', $match), [
                'status' => MatchStatus::Possible->value,
                'review_remarks' => 'Strong match; advise owner to file claim.',
            ])
            ->assertRedirect(route('admin.matches.index'));

        $this->assertSame(MatchStatus::Possible, $match->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'match.reviewed')->where('auditable_id', $match->id)->exists());

        app(ItemMatchingService::class)->matchForReport($pendingLost->fresh());
        $this->assertSame(MatchStatus::Possible, $match->fresh()->status);
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
