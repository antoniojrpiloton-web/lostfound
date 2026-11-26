<?php

namespace Database\Seeders;

use App\Enums\CustodyAction;
use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemCustodyRecord;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\ReportStatusHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\Reports\SearchTextBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $roles = Role::query()->pluck('id', 'key');
        $orgs = Organization::query()->pluck('id', 'code');
        $barangays = Barangay::query()->pluck('id', 'code');
        $locations = Location::query()->pluck('id', 'name');
        $categories = Category::query()->pluck('id', 'slug');
        $searchBuilder = app(SearchTextBuilder::class);

        $staffUsers = [
            ['email' => 'admin.balabag@boracay-lf.local', 'name' => 'Elena Reyes', 'org' => 'LGU-BALABAG', 'role' => 'institution_admin'],
            ['email' => 'staff.manocmanoc@boracay-lf.local', 'name' => 'Marco Villanueva', 'org' => 'LGU-MANOCMANOC', 'role' => 'institution_staff'],
            ['email' => 'admin.yapak@boracay-lf.local', 'name' => 'Clarissa Bautista', 'org' => 'LGU-YAPAK', 'role' => 'institution_admin'],
            ['email' => 'officer.btac@boracay-lf.local', 'name' => 'PSgt. Ramon Dizon', 'org' => 'PNP-MALAY-BTAC', 'role' => 'institution_admin'],
        ];

        $userMap = [];

        foreach ($staffUsers as $index => $entry) {
            $user = User::query()->updateOrCreate(
                ['email' => $entry['email']],
                [
                    'google_id' => 'demo-staff-'.($index + 1),
                    'name' => $entry['name'],
                    'email_verified_at' => now(),
                    'status' => UserStatus::Active,
                    'last_login_at' => now()->subHours($index + 1),
                ]
            );

            OrganizationMembership::query()->updateOrCreate(
                ['user_id' => $user->id, 'organization_id' => $orgs[$entry['org']]],
                [
                    'role_id' => $roles[$entry['role']],
                    'status' => MembershipStatus::Active,
                    'approved_at' => now()->subDays(10),
                ]
            );

            $userMap[$entry['org']] = $user;
        }

        $publicUsers = [
            ['email' => 'maria.santos@example.com', 'name' => 'Maria Santos', 'gid' => 'demo-public-1'],
            ['email' => 'kenji.tanaka@example.com', 'name' => 'Kenji Tanaka', 'gid' => 'demo-public-2'],
            ['email' => 'jihoon.park@example.com', 'name' => 'Jihoon Park', 'gid' => 'demo-public-3'],
            ['email' => 'anna.mendoza@example.com', 'name' => 'Anna Mendoza', 'gid' => 'demo-public-4'],
        ];

        $reporters = [];
        foreach ($publicUsers as $entry) {
            $reporters[] = User::query()->updateOrCreate(
                ['email' => $entry['email']],
                [
                    'google_id' => $entry['gid'],
                    'name' => $entry['name'],
                    'email_verified_at' => now(),
                    'status' => UserStatus::Active,
                    'last_login_at' => now()->subDays(1),
                ]
            );
        }

        $pendingInvites = [
            ['email' => 'desk.balabag@boracay-lf.local', 'org' => 'LGU-BALABAG', 'role' => 'institution_staff'],
            ['email' => 'patrol.btac@boracay-lf.local', 'org' => 'PNP-MALAY-BTAC', 'role' => 'institution_staff'],
        ];

        foreach ($pendingInvites as $invite) {
            OrganizationInvitation::query()->firstOrCreate(
                ['email' => $invite['email'], 'organization_id' => $orgs[$invite['org']]],
                [
                    'role_id' => $roles[$invite['role']],
                    'token_hash' => hash('sha256', Str::random(40)),
                    'invited_by' => $userMap[$invite['org']]->id,
                    'expires_at' => now()->addDays(6),
                ]
            );
        }

        $year = now()->year;

        $demoReports = [
            [
                'reference_no' => "BLF-L-{$year}-900001",
                'type' => ReportType::Lost,
                'reporter' => $reporters[0],
                'org' => 'LGU-BALABAG',
                'barangay' => 'BRGY-BALABAG',
                'location' => 'Station 2 Beachfront',
                'category' => 'mobile-phones',
                'item_name' => 'Titanium Blue iPhone 15 Pro',
                'brand' => 'Apple',
                'model' => 'iPhone 15 Pro',
                'color' => 'Blue',
                'description' => 'Lost near Station 2 beachfront lounge chairs after sunset.',
                'distinguishing_features' => 'Clear MagSafe case with a small sea turtle sticker.',
                'days_ago' => 2,
                'verification' => VerificationStatus::Verified,
                'item_status' => ItemStatus::Open,
                'is_public' => true,
                'reward_offered' => true,
                'reward_details' => 'Modest appreciation reward upon verified return.',
            ],
            [
                'reference_no' => "BLF-F-{$year}-900002",
                'type' => ReportType::Found,
                'reporter' => $reporters[3],
                'org' => 'PNP-MALAY-BTAC',
                'barangay' => 'BRGY-BALABAG',
                'location' => 'Station 2 Beachfront',
                'category' => 'mobile-phones',
                'item_name' => 'Blue iPhone 15 Pro in Clear Case',
                'brand' => 'Apple',
                'model' => 'iPhone 15 Pro',
                'color' => 'Blue',
                'description' => 'Turned over by resort staff who found it near Station 2 shoreline.',
                'distinguishing_features' => 'Transparent case with turtle decal on lower right corner.',
                'days_ago' => 1,
                'verification' => VerificationStatus::Verified,
                'item_status' => ItemStatus::InCustody,
                'is_public' => true,
                'finder_name' => 'Resort Lifeguard Team',
            ],
            [
                'reference_no' => "BLF-L-{$year}-900003",
                'type' => ReportType::Lost,
                'reporter' => $reporters[1],
                'org' => 'LGU-BALABAG',
                'barangay' => 'BRGY-BALABAG',
                'location' => "D'Mall de Boracay",
                'category' => 'wallets-purses',
                'item_name' => 'Brown Bifold Leather Wallet',
                'brand' => 'Bellroy',
                'model' => 'Hide & Seek',
                'color' => 'Brown',
                'description' => 'Misplaced near D\'Mall food alley while paying for dinner.',
                'distinguishing_features' => 'Initials KT embossed inside.',
                'days_ago' => 3,
                'verification' => VerificationStatus::Verified,
                'item_status' => ItemStatus::Open,
                'is_public' => true,
            ],
            [
                'reference_no' => "BLF-F-{$year}-900004",
                'type' => ReportType::Found,
                'reporter' => null,
                'org' => 'LGU-MANOCMANOC',
                'barangay' => 'BRGY-MANOCMANOC',
                'location' => 'Cagban Jetty Port',
                'category' => 'passports',
                'item_name' => 'Travel Document Pouch with Boarding Passes',
                'brand' => 'Muji',
                'model' => null,
                'color' => 'Navy',
                'description' => 'Found on waiting bench at Cagban Jetty Port departure area.',
                'distinguishing_features' => 'Navy zip organizer with luggage tag attached.',
                'days_ago' => 1,
                'verification' => VerificationStatus::Verified,
                'item_status' => ItemStatus::InCustody,
                'is_public' => true,
                'finder_name' => 'Port Terminal Security',
            ],
            [
                'reference_no' => "BLF-L-{$year}-900005",
                'type' => ReportType::Lost,
                'reporter' => $reporters[2],
                'org' => 'LGU-YAPAK',
                'barangay' => 'BRGY-YAPAK',
                'location' => 'Puka Shell Beach',
                'category' => 'eyewear',
                'item_name' => 'Polarized Wayfarer Sunglasses',
                'brand' => 'Ray-Ban',
                'model' => 'RB2140',
                'color' => 'Black',
                'description' => 'Left on a shaded picnic table at Puka Shell Beach.',
                'distinguishing_features' => 'Minor scratch on right temple arm.',
                'days_ago' => 4,
                'verification' => VerificationStatus::Verified,
                'item_status' => ItemStatus::Open,
                'is_public' => true,
            ],
            [
                'reference_no' => "BLF-F-{$year}-900006",
                'type' => ReportType::Found,
                'reporter' => null,
                'org' => 'LGU-BALABAG',
                'barangay' => 'BRGY-BALABAG',
                'location' => 'Station 1 Beachfront',
                'category' => 'jewelry-watches',
                'item_name' => 'Silver Diver Automatic Watch',
                'brand' => 'Seiko',
                'model' => 'Prospex',
                'color' => 'Silver',
                'description' => 'Found in shallow sand near Station 1 morning tide.',
                'distinguishing_features' => 'Dark blue bezel with stainless steel bracelet.',
                'days_ago' => 2,
                'verification' => VerificationStatus::Verified,
                'item_status' => ItemStatus::InCustody,
                'is_public' => true,
                'finder_name' => 'Paraw Boatman Association',
            ],
            [
                'reference_no' => "BLF-L-{$year}-900007",
                'type' => ReportType::Lost,
                'reporter' => $reporters[0],
                'org' => 'LGU-BALABAG',
                'barangay' => 'BRGY-BALABAG',
                'location' => 'Bulabog Beach',
                'category' => 'cameras-action-cams',
                'item_name' => 'GoPro Hero 12 Black Action Camera',
                'brand' => 'GoPro',
                'model' => 'Hero 12 Black',
                'color' => 'Black',
                'description' => 'Detached from wrist strap while paddleboarding at Bulabog Beach.',
                'distinguishing_features' => 'Attached to bright orange floating hand grip.',
                'days_ago' => 0,
                'verification' => VerificationStatus::Pending,
                'item_status' => ItemStatus::Open,
                'is_public' => false,
            ],
            [
                'reference_no' => "BLF-F-{$year}-900008",
                'type' => ReportType::Found,
                'reporter' => $reporters[3],
                'org' => 'LGU-MANOCMANOC',
                'barangay' => 'BRGY-MANOCMANOC',
                'location' => 'Station 3 Beachfront',
                'category' => 'backpacks-bags',
                'item_name' => 'Yellow 10L Waterproof Dry Bag',
                'brand' => 'Ocean Pack',
                'model' => '10L',
                'color' => 'Yellow',
                'description' => 'Found beside Station 3 kayak rental area with towel and powerbank inside.',
                'distinguishing_features' => 'Black carabiner clipped to top buckle.',
                'days_ago' => 0,
                'verification' => VerificationStatus::Pending,
                'item_status' => ItemStatus::AwaitingTurnover,
                'is_public' => false,
                'finder_name' => 'Anna Mendoza',
            ],
            [
                'reference_no' => "BLF-F-{$year}-900009",
                'type' => ReportType::Found,
                'reporter' => null,
                'org' => 'LGU-YAPAK',
                'barangay' => 'BRGY-YAPAK',
                'location' => 'CityMall Boracay',
                'category' => 'keys',
                'item_name' => 'Hotel Keycard & Motorcycle Key Set',
                'brand' => 'Honda',
                'model' => null,
                'color' => 'Black',
                'description' => 'Surrendered at CityMall Boracay concierge and turned over to Barangay Yapak.',
                'distinguishing_features' => 'Woven rattan keychain.',
                'days_ago' => 1,
                'verification' => VerificationStatus::Pending,
                'item_status' => ItemStatus::InCustody,
                'is_public' => false,
                'finder_name' => 'CityMall Concierge',
            ],
            [
                'reference_no' => "BLF-L-{$year}-900010",
                'type' => ReportType::Lost,
                'reporter' => $reporters[2],
                'org' => 'PNP-MALAY-BTAC',
                'barangay' => 'BRGY-BALABAG',
                'location' => 'Boracay Tourist Assistance Center Area',
                'category' => 'audio-wearables',
                'item_name' => 'White Wireless Earbuds in Charging Case',
                'brand' => 'Apple',
                'model' => 'AirPods Pro 2',
                'color' => 'White',
                'description' => 'Dropped while boarding an e-trike along the main road.',
                'distinguishing_features' => 'Mint green silicone protective case.',
                'days_ago' => 5,
                'verification' => VerificationStatus::Rejected,
                'item_status' => ItemStatus::Open,
                'is_public' => false,
                'rejection_reason' => 'Duplicate entry of an earlier verified report.',
            ],
        ];

        foreach ($demoReports as $item) {
            $orgId = $orgs[$item['org']];
            $actor = $userMap[$item['org']];
            $isFound = $item['type'] === ReportType::Found;

            $report = ItemReport::query()->updateOrCreate(
                ['reference_no' => $item['reference_no']],
                [
                    'type' => $item['type'],
                    'reporter_user_id' => $item['reporter']?->id,
                    'encoded_by' => $item['reporter']?->id ?? $actor->id,
                    'handling_organization_id' => $orgId,
                    'custody_organization_id' => $isFound ? $orgId : null,
                    'category_id' => $categories[$item['category']],
                    'item_name' => $item['item_name'],
                    'description' => $item['description'],
                    'brand' => $item['brand'],
                    'model' => $item['model'],
                    'color' => $item['color'],
                    'distinguishing_features' => $item['distinguishing_features'],
                    'occurred_date' => now()->subDays($item['days_ago'])->toDateString(),
                    'barangay_id' => $barangays[$item['barangay']],
                    'location_id' => $locations[$item['location']] ?? null,
                    'reward_offered' => (bool) ($item['reward_offered'] ?? false),
                    'reward_details' => $item['reward_details'] ?? null,
                    'finder_name' => $item['finder_name'] ?? null,
                    'verification_status' => $item['verification'],
                    'item_status' => $item['item_status'],
                    'is_public' => $item['is_public'],
                    'verified_by' => $item['verification'] === VerificationStatus::Verified ? $actor->id : null,
                    'verified_at' => $item['verification'] === VerificationStatus::Verified ? now()->subHours(6) : null,
                    'rejection_reason' => $item['rejection_reason'] ?? null,
                    'received_at' => $item['item_status'] === ItemStatus::InCustody ? now()->subHours(12) : null,
                    'source_locale' => 'en',
                ]
            );

            $report->forceFill(['search_text' => $searchBuilder->build($report)])->save();

            if ($report->statusHistories()->count() === 0) {
                ReportStatusHistory::query()->create([
                    'item_report_id' => $report->id,
                    'dimension' => StatusDimension::Verification,
                    'from_status' => null,
                    'to_status' => $item['verification']->value,
                    'changed_by' => $actor->id,
                    'remarks' => __('Seeded demo verification status.'),
                ]);

                ReportStatusHistory::query()->create([
                    'item_report_id' => $report->id,
                    'dimension' => StatusDimension::Item,
                    'from_status' => null,
                    'to_status' => $item['item_status']->value,
                    'changed_by' => $actor->id,
                    'remarks' => __('Seeded demo item status.'),
                ]);
            }

            if ($isFound && $item['item_status'] === ItemStatus::InCustody && $report->custodyRecords()->count() === 0) {
                ItemCustodyRecord::query()->create([
                    'item_report_id' => $report->id,
                    'organization_id' => $orgId,
                    'action' => CustodyAction::Received,
                    'received_from' => $item['finder_name'] ?? 'Walk-in Finder',
                    'recorded_by' => $actor->id,
                    'recorded_at' => now()->subHours(12),
                    'remarks' => __('Physical item logged at institutional desk.'),
                ]);
            }

            AuditLog::query()->firstOrCreate(
                [
                    'action' => $isFound ? 'report.found_submitted' : 'report.lost_submitted',
                    'auditable_type' => ItemReport::class,
                    'auditable_id' => $report->id,
                ],
                [
                    'user_id' => $item['reporter']?->id ?? $actor->id,
                    'organization_id' => $orgId,
                    'new_values' => ['reference_no' => $report->reference_no],
                    'created_at' => now()->subDays($item['days_ago']),
                ]
            );
        }
    }
}
