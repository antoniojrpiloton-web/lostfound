<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ItemReport;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\SystemEventNotification;
use App\Services\MasterData\MasterDataCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_in_app_notifications_and_delivery_logs_are_created_for_report_and_claim_lifecycle(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $admin = $this->createUserWithRole('institution_admin', $balabag);
        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $this->actingAs($user)->post(route('reports.lost.store'), [
            'item_name' => 'Notified Lost Phone',
            'category_id' => $category->id,
            'description' => 'Lost near Station 2 beachfront.',
            'occurred_date' => now()->toDateString(),
            'barangay_id' => $barangay->id,
        ])->assertRedirect();

        $report = ItemReport::query()->firstOrFail();
        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($admin)->patch(route('admin.reports.verify', $report))->assertRedirect();
        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Report Verified');
        $this->actingAs($user)->post(route('notifications.read_all'))->assertRedirect();
        $this->assertSame(0, $user->unreadNotifications()->count());

        $this->assertGreaterThanOrEqual(2, NotificationDelivery::query()->where('user_id', $user->id)->where('channel', 'database')->count());
    }

    public function test_lost_owner_is_automatically_notified_on_submission_and_when_similar_found_item_exists(): void
    {
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $owner = User::factory()->create();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();

        $found = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
            'category_id' => $category->id,
            'item_name' => 'Blue iPhone 15 Pro',
            'brand' => 'Apple',
            'color' => 'Blue',
            'description' => 'Found near White Beach Station 2 beachfront.',
            'occurred_date' => now()->toDateString(),
            'item_status' => ItemStatus::InCustody,
            'is_public' => true,
        ]);

        $this->actingAs($owner)->post(route('reports.lost.store'), [
            'item_name' => 'Blue iPhone 15 Pro',
            'category_id' => $category->id,
            'brand' => 'Apple',
            'color' => 'Blue',
            'description' => 'Lost near White Beach Station 2 beachfront.',
            'occurred_date' => now()->toDateString(),
        ])->assertRedirect();

        $notifications = $owner->notifications()->get();
        $this->assertTrue($notifications->contains(fn ($n): bool => ($n->data['event'] ?? null) === 'report.submitted'));
        $this->assertTrue($notifications->contains(fn ($n): bool => ($n->data['event'] ?? null) === 'match.identified'));
    }

    public function test_email_channel_is_included_when_email_notifications_setting_is_enabled(): void
    {
        Notification::fake();

        SystemSetting::query()->where('key', 'notifications.email_enabled')->update(['value' => ['value' => true]]);
        app(MasterDataCacheService::class)->forgetSettings();

        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $admin = $this->createUserWithRole('institution_admin', $balabag);
        $claimant = User::factory()->create();

        $found = ItemReport::factory()->found()->verified()->create([
            'handling_organization_id' => $balabag->id,
            'custody_organization_id' => $balabag->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'barangay_id' => Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail()->id,
            'item_status' => ItemStatus::ClaimPending,
        ]);

        $claim = Claim::factory()->create([
            'item_report_id' => $found->id,
            'claimant_user_id' => $claimant->id,
            'status' => ClaimStatus::Submitted,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.claims.approve', $claim), ['remarks' => 'Verified ownership'])
            ->assertRedirect();

        Notification::assertSentTo($claimant, SystemEventNotification::class, function (SystemEventNotification $notification): bool {
            return $notification->eventKey === 'claim.approved'
                && in_array('database', $notification->channels, true)
                && in_array('mail', $notification->channels, true);
        });

        $this->assertTrue(NotificationDelivery::query()->where('user_id', $claimant->id)->where('channel', 'mail')->exists());
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
