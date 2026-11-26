<?php

namespace Database\Seeders;

use App\Enums\CustodyAction;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemCustodyRecord;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Reports\ReferenceNumberService;
use App\Services\Reports\ReportRoutingService;
use App\Services\Reports\SearchTextBuilder;
use Illuminate\Database\Seeder;

class LostAndFoundSeeder extends Seeder
{
    public function run(): void
    {
        $lostCount = 10;
        $foundCount = 10;

        if ($this->command !== null && $this->command->getInput()->isInteractive()) {
            $lostCount = max(0, (int) $this->command->ask('How many LOST item reports to add?', '10'));
            $foundCount = max(0, (int) $this->command->ask('How many FOUND item reports to add?', '10'));
        }

        $this->seedReports($lostCount, $foundCount);
    }

    public function seedReports(int $lostCount, int $foundCount): array
    {
        if (Barangay::query()->count() === 0 || Category::query()->count() === 0) {
            $this->call(DatabaseSeeder::class);
        }

        $categories = Category::query()->where('is_active', true)->get();
        $barangays = Barangay::query()->where('is_active', true)->get();
        $locationsByBarangay = Location::query()->where('is_active', true)->get()->groupBy('barangay_id');
        $organizations = Organization::query()->where('is_active', true)->get();
        $users = User::query()->get();

        if ($users->isEmpty()) {
            $users = User::factory()->count(5)->create();
        }

        $referenceNumbers = app(ReferenceNumberService::class);
        $routingService = app(ReportRoutingService::class);
        $searchBuilder = app(SearchTextBuilder::class);

        $catalog = [
            ['slug' => 'mobile-phones', 'names' => ['iPhone 15 Pro', 'Samsung Galaxy S24', 'Google Pixel 8', 'Xiaomi 14', 'iPhone 14'], 'brands' => ['Apple', 'Samsung', 'Google', 'Xiaomi']],
            ['slug' => 'wallets-purses', 'names' => ['Leather Bifold Wallet', 'Zip Coin Purse', 'Card Holder', 'Canvas Travel Wallet'], 'brands' => ['Bellroy', 'Herschel', 'Coach', 'Fossil']],
            ['slug' => 'eyewear', 'names' => ['Polarized Sunglasses', 'Wayfarer Sunglasses', 'Prescription Eyeglasses'], 'brands' => ['Ray-Ban', 'Oakley', 'Sunnies Studios']],
            ['slug' => 'cameras-action-cams', 'names' => ['GoPro Hero 12', 'DJI Osmo Action 4', 'Insta360 X4', 'Compact Digital Camera'], 'brands' => ['GoPro', 'DJI', 'Insta360', 'Sony']],
            ['slug' => 'backpacks-bags', 'names' => ['10L Waterproof Dry Bag', '20L Dry Bag', 'Beach Tote Bag', 'Sling Bag'], 'brands' => ['Ocean Pack', 'Decathlon', 'Herschel', 'Uniqlo']],
            ['slug' => 'jewelry-watches', 'names' => ['Diver Automatic Watch', 'G-Shock Digital Watch', 'Gold Chain Bracelet', 'Smartwatch'], 'brands' => ['Seiko', 'Casio', 'Apple', 'Garmin']],
            ['slug' => 'audio-wearables', 'names' => ['Wireless Earbuds', 'Noise Cancelling Earbuds', 'Bluetooth Speaker'], 'brands' => ['Apple', 'Sony', 'JBL', 'Samsung']],
            ['slug' => 'keys', 'names' => ['Hotel Keycard & Keyring', 'Motorcycle Key Set', 'Resort Room Key'], 'brands' => ['Honda', 'Yamaha', 'Generic']],
            ['slug' => 'passports', 'names' => ['Travel Document Pouch', 'Passport Holder'], 'brands' => ['Muji', 'Samsonite', 'Generic']],
        ];

        $colors = ['Black', 'Blue', 'Brown', 'Silver', 'White', 'Yellow', 'Green', 'Red', 'Navy', 'Orange'];
        $features = [
            'Minor scratch on the corner',
            'Has a Boracay island sticker attached',
            'Inside a clear waterproof pouch',
            'With woven rattan keychain',
            'Slightly faded logo on the front',
            'Protective silicone case included',
        ];

        $categoriesBySlug = $categories->keyBy('slug');

        $createOne = function (ReportType $type) use (
            $catalog,
            $categories,
            $categoriesBySlug,
            $barangays,
            $locationsByBarangay,
            $organizations,
            $users,
            $colors,
            $features,
            $referenceNumbers,
            $routingService,
            $searchBuilder,
        ): void {
            $template = fake()->randomElement($catalog);
            $category = $categoriesBySlug->get($template['slug']) ?? $categories->random();
            $barangay = $barangays->random();
            $barangayLocations = $locationsByBarangay->get($barangay->id);
            $location = ($barangayLocations && $barangayLocations->isNotEmpty()) ? $barangayLocations->random() : null;
            $reporter = $users->random();

            $isFound = $type === ReportType::Found;
            $prefix = $isFound ? 'BLF-F' : 'BLF-L';
            $referenceNo = $referenceNumbers->next($prefix);

            $handlingOrgId = $routingService->resolveHandlingOrganizationId($barangay->id, $location?->id);
            $custodyOrgId = $isFound ? ($handlingOrgId ?? $organizations->random()->id) : null;

            $isVerified = fake()->boolean(70);
            $verificationStatus = $isVerified ? VerificationStatus::Verified : VerificationStatus::Pending;

            $itemStatus = $isFound
                ? ($isVerified ? ItemStatus::InCustody : fake()->randomElement([ItemStatus::InCustody, ItemStatus::AwaitingTurnover]))
                : ItemStatus::Open;

            $color = fake()->randomElement($colors);
            $brand = fake()->randomElement($template['brands']);
            $baseName = fake()->randomElement($template['names']);
            $locationText = $location?->name ?? $barangay->name;

            $report = ItemReport::query()->create([
                'reference_no' => $referenceNo,
                'type' => $type,
                'reporter_user_id' => $reporter->id,
                'encoded_by' => $reporter->id,
                'handling_organization_id' => $handlingOrgId,
                'custody_organization_id' => $custodyOrgId,
                'category_id' => $category->id,
                'item_name' => "{$color} {$baseName}",
                'description' => $isFound
                    ? "Found near {$locationText}, Barangay {$barangay->name} and reported for safekeeping."
                    : "Lost near {$locationText}, Barangay {$barangay->name} while visiting the area.",
                'brand' => $brand,
                'model' => null,
                'color' => $color,
                'distinguishing_features' => fake()->randomElement($features),
                'occurred_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
                'occurred_time' => fake()->time('H:i:s'),
                'barangay_id' => $barangay->id,
                'location_id' => $location?->id,
                'reward_offered' => ! $isFound && fake()->boolean(25),
                'reward_details' => ! $isFound ? 'Appreciation reward upon verified return.' : null,
                'finder_name' => $isFound ? fake()->name() : null,
                'verification_status' => $verificationStatus,
                'item_status' => $itemStatus,
                'is_confidential' => false,
                'is_public' => $isVerified,
                'verified_at' => $isVerified ? now()->subHours(fake()->numberBetween(1, 72)) : null,
                'received_at' => $itemStatus === ItemStatus::InCustody ? now()->subHours(fake()->numberBetween(1, 72)) : null,
                'source_locale' => 'en',
            ]);

            $report->forceFill(['search_text' => $searchBuilder->build($report)])->save();

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Verification,
                'from_status' => null,
                'to_status' => $verificationStatus->value,
                'changed_by' => $reporter->id,
                'remarks' => __('Generated via Lost and Found seeder.'),
            ]);

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Item,
                'from_status' => null,
                'to_status' => $itemStatus->value,
                'changed_by' => $reporter->id,
                'remarks' => __('Initial item status recorded.'),
            ]);

            if ($isFound && $itemStatus === ItemStatus::InCustody && $custodyOrgId !== null) {
                ItemCustodyRecord::query()->create([
                    'item_report_id' => $report->id,
                    'organization_id' => $custodyOrgId,
                    'action' => CustodyAction::Received,
                    'received_from' => $report->finder_name ?? 'Walk-in Finder',
                    'recorded_by' => $reporter->id,
                    'recorded_at' => $report->received_at ?? now(),
                    'remarks' => __('Seeded institutional custody receipt.'),
                ]);
            }
        };

        for ($i = 0; $i < $lostCount; $i++) {
            $createOne(ReportType::Lost);
        }

        for ($i = 0; $i < $foundCount; $i++) {
            $createOne(ReportType::Found);
        }

        return ['lost' => $lostCount, 'found' => $foundCount];
    }
}
