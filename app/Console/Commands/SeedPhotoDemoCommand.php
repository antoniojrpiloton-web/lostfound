<?php

namespace App\Console\Commands;

use App\Enums\CustodyAction;
use App\Enums\ImageValidationStatus;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemCustodyRecord;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Matching\ItemMatchingService;
use App\Services\Matching\VisualSearchService;
use App\Services\Reports\ReferenceNumberService;
use App\Services\Reports\ReportImageService;
use App\Services\Reports\ReportRoutingService;
use App\Services\Reports\SearchTextBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeedPhotoDemoCommand extends Command
{
    protected $signature = 'blf:seed-photo-demo
        {--export-dir= : Folder to save slightly transformed test photos for searching (defaults to base_path("demo-search-photos"))}';

    protected $description = 'Seed verified Found & Lost item reports with actual photographs and export test search photos to a separate folder.';

    public function handle(
        ReferenceNumberService $refNumbers,
        ReportRoutingService $routingService,
        SearchTextBuilder $searchText,
        ReportImageService $imageService,
        VisualSearchService $visualSearch,
        ItemMatchingService $matchingService,
    ): int {
        $sourceDir = storage_path('app/demo-items');
        $exportDir = (string) ($this->option('export-dir') ?: base_path('demo-search-photos'));

        File::ensureDirectoryExists($sourceDir);
        File::ensureDirectoryExists($exportDir);

        $items = [
            [
                'file' => 'blue-iphone-15-pro.jpg',
                'item_name' => 'Blue Titanium iPhone 15 Pro',
                'category_slug' => 'mobile-phones',
                'barangay_code' => 'BRGY-BALABAG',
                'brand' => 'Apple',
                'model' => 'iPhone 15 Pro',
                'color' => 'Blue',
                'description' => 'Blue Titanium iPhone 15 Pro inside a clear protective case found near Station 2 beachfront.',
                'lost_pair' => true,
            ],
            [
                'file' => 'black-leather-wallet.jpg',
                'item_name' => 'Black Leather Bifold Wallet',
                'category_slug' => 'wallets-purses',
                'barangay_code' => 'BRGY-BALABAG',
                'brand' => 'Coach',
                'model' => 'Bifold',
                'color' => 'Black',
                'description' => 'Classic black leather bifold wallet found on a bench along D-Mall walkway.',
                'lost_pair' => true,
            ],
            [
                'file' => 'gopro-action-camera.jpg',
                'item_name' => 'GoPro Hero 12 Action Camera with Yellow Grip',
                'category_slug' => 'cameras-accessories',
                'barangay_code' => 'BRGY-YAPAK',
                'brand' => 'GoPro',
                'model' => 'Hero 12 Black',
                'color' => 'Black',
                'description' => 'Black GoPro action camera attached to a bright yellow floating hand grip found near Puka Beach.',
                'lost_pair' => true,
            ],
            [
                'file' => 'rayban-sunglasses.jpg',
                'item_name' => 'Tortoiseshell Wayfarer Sunglasses',
                'category_slug' => 'eyewear',
                'barangay_code' => 'BRGY-MANOCMANOC',
                'brand' => 'Ray-Ban',
                'model' => 'Wayfarer',
                'color' => 'Brown',
                'description' => 'Dark tortoiseshell Wayfarer sunglasses left at a beachfront table near Station 3.',
                'lost_pair' => false,
            ],
            [
                'file' => 'blue-waterproof-drybag.jpg',
                'item_name' => 'Ocean Blue 10L Waterproof Dry Bag',
                'category_slug' => 'bags-luggage',
                'barangay_code' => 'BRGY-MANOCMANOC',
                'brand' => 'Naturehike',
                'model' => '10L Dry Bag',
                'color' => 'Blue',
                'description' => 'Ocean-blue 10L waterproof dry bag with black buckle straps found at Cagban Jetty Port waiting area.',
                'lost_pair' => false,
            ],
            [
                'file' => 'white-airpods-pro.jpg',
                'item_name' => 'White AirPods Pro with Navy Case',
                'category_slug' => 'audio-earbuds',
                'barangay_code' => 'BRGY-BALABAG',
                'brand' => 'Apple',
                'model' => 'AirPods Pro 2',
                'color' => 'White',
                'description' => 'White wireless earbuds inside charging case with navy silicone cover found near White Beach Station 1.',
                'lost_pair' => false,
            ],
        ];

        $admin = User::query()->first() ?? User::factory()->create();
        $defaultCategory = Category::query()->firstOrFail();
        $defaultBarangay = Barangay::query()->firstOrFail();
        $defaultOrg = Organization::query()->firstOrFail();

        $createdReports = [];
        $exportedCount = 0;

        foreach ($items as $spec) {
            $filePath = $this->resolveDemoImagePath($sourceDir, $spec['file']);
            if ($filePath === null) {
                $this->warn('Skipping missing photo: '.$spec['file']);

                continue;
            }

            $category = Category::query()->where('slug', $spec['category_slug'])->first() ?? $defaultCategory;
            $barangay = Barangay::query()->where('code', $spec['barangay_code'])->first() ?? $defaultBarangay;
            $orgId = $routingService->resolveHandlingOrganizationId($barangay->id);
            $org = ($orgId !== null ? Organization::query()->find($orgId) : null) ?? $defaultOrg;

            $rawBytes = File::get($filePath);

            $found = $this->createVerifiedReport(
                type: ReportType::Found,
                spec: $spec,
                category: $category,
                barangay: $barangay,
                org: $org,
                actor: $admin,
                imageBytes: $rawBytes,
                refNumbers: $refNumbers,
                searchText: $searchText,
                imageService: $imageService,
                visualSearch: $visualSearch,
            );
            $createdReports[] = $found;
            $this->info("Created Found: {$found->reference_no} — {$found->item_name}");

            $searchVariantBytes = $this->createSearchVariant($rawBytes, blur: false);
            $exportPath = $exportDir.DIRECTORY_SEPARATOR.'search-'.pathinfo($spec['file'], PATHINFO_FILENAME).'.jpg';
            File::put($exportPath, $searchVariantBytes);
            $exportedCount++;

            if ($spec['lost_pair']) {
                $lostSpec = $spec;
                $lostSpec['description'] = 'Lost my '.$spec['item_name'].' around '.$barangay->name.' beach area earlier today. Please contact me if found.';

                $lost = $this->createVerifiedReport(
                    type: ReportType::Lost,
                    spec: $lostSpec,
                    category: $category,
                    barangay: $barangay,
                    org: $org,
                    actor: $admin,
                    imageBytes: $searchVariantBytes,
                    refNumbers: $refNumbers,
                    searchText: $searchText,
                    imageService: $imageService,
                    visualSearch: $visualSearch,
                );
                $createdReports[] = $lost;
                $this->info("Created Lost Pair: {$lost->reference_no} — {$lost->item_name}");
            }
        }

        $firstImage = $this->resolveDemoImagePath($sourceDir, $items[0]['file']);
        if ($firstImage !== null) {
            $blurryBytes = $this->createSearchVariant(File::get($firstImage), blur: true);
            File::put($exportDir.DIRECTORY_SEPARATOR.'test-blurry-rejected.jpg', $blurryBytes);
            $exportedCount++;
        }

        $matchedTotal = 0;
        foreach ($createdReports as $report) {
            if ($report->type === ReportType::Found) {
                $matchedTotal += $matchingService->matchForReport($report)->count();
            }
        }

        $this->newLine();
        $this->info('Seeded '.count($createdReports).' verified photo report(s) and scored '.$matchedTotal.' candidate match(es).');
        $this->info("Exported {$exportedCount} test search photo(s) to: {$exportDir}");

        return self::SUCCESS;
    }

    protected function resolveDemoImagePath(string $sourceDir, string $filename): ?string
    {
        $exact = $sourceDir.DIRECTORY_SEPARATOR.$filename;
        if (File::exists($exact)) {
            return $exact;
        }

        $stem = pathinfo($filename, PATHINFO_FILENAME);
        foreach (['png', 'webp', 'jpeg', 'jpg'] as $ext) {
            $candidate = $sourceDir.DIRECTORY_SEPARATOR.$stem.'.'.$ext;
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function createVerifiedReport(
        ReportType $type,
        array $spec,
        Category $category,
        Barangay $barangay,
        Organization $org,
        User $actor,
        string $imageBytes,
        ReferenceNumberService $refNumbers,
        SearchTextBuilder $searchText,
        ReportImageService $imageService,
        VisualSearchService $visualSearch,
    ): ItemReport {
        $isFound = $type === ReportType::Found;
        $refNo = $refNumbers->next($isFound ? 'BLF-F' : 'BLF-L');

        $report = ItemReport::query()->create([
            'reference_no' => $refNo,
            'type' => $type,
            'reporter_user_id' => $isFound ? null : $actor->id,
            'encoded_by' => $actor->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $isFound ? $org->id : null,
            'category_id' => $category->id,
            'item_name' => $spec['item_name'],
            'description' => $spec['description'],
            'brand' => $spec['brand'],
            'model' => $spec['model'],
            'color' => $spec['color'],
            'occurred_date' => now()->subDays(rand(0, 5))->toDateString(),
            'barangay_id' => $barangay->id,
            'finder_name' => $isFound ? 'Boracay Desk Officer' : null,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => $isFound ? ItemStatus::InCustody : ItemStatus::Open,
            'is_confidential' => false,
            'is_public' => true,
            'verified_by' => $actor->id,
            'verified_at' => now(),
            'received_at' => $isFound ? now() : null,
            'source_locale' => 'en',
        ]);

        $report->forceFill(['search_text' => $searchText->build($report)])->save();

        $uuid = (string) Str::uuid();
        $privatePath = "item-images/private/{$report->id}/{$uuid}.jpg";
        Storage::disk('local')->put($privatePath, $imageBytes);

        $gd = @imagecreatefromstring($imageBytes);
        $width = $gd !== false ? imagesx($gd) : null;
        $height = $gd !== false ? imagesy($gd) : null;
        if ($gd !== false) {
            imagedestroy($gd);
        }

        $image = ItemImage::query()->create([
            'item_report_id' => $report->id,
            'private_path' => $privatePath,
            'public_path' => null,
            'mime' => 'image/jpeg',
            'size' => strlen($imageBytes),
            'width' => $width,
            'height' => $height,
            'sha256' => hash('sha256', $imageBytes),
            'validation_status' => ImageValidationStatus::Passed,
            'validation_result' => ['source' => 'demo_seeder', 'status' => 'passed'],
            'is_primary' => true,
        ]);

        $imageService->generatePublicDerivative($image);
        $visualSearch->indexItemImage($image);

        if ($isFound) {
            ItemCustodyRecord::query()->create([
                'item_report_id' => $report->id,
                'organization_id' => $org->id,
                'action' => CustodyAction::Received,
                'received_from' => 'Boracay Desk Officer',
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
                'remarks' => 'Demo custody record.',
            ]);
        }

        ReportStatusHistory::query()->create([
            'item_report_id' => $report->id,
            'dimension' => StatusDimension::Verification,
            'from_status' => VerificationStatus::Pending->value,
            'to_status' => VerificationStatus::Verified->value,
            'changed_by' => $actor->id,
            'remarks' => 'Verified demo report with photo.',
        ]);

        return $report;
    }

    protected function createSearchVariant(string $rawBytes, bool $blur): string
    {
        $source = @imagecreatefromstring($rawBytes);
        if ($source === false) {
            return $rawBytes;
        }

        $w = imagesx($source);
        $h = imagesy($source);

        if ($blur) {
            $tiny = imagecreatetruecolor(24, 24);
            imagecopyresampled($tiny, $source, 0, 0, 0, 0, 24, 24, $w, $h);
            $blurred = imagecreatetruecolor($w, $h);
            imagecopyresampled($blurred, $tiny, 0, 0, 0, 0, $w, $h, 24, 24);
            for ($i = 0; $i < 8; $i++) {
                imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
            }
            ob_start();
            imagejpeg($blurred, null, 75);
            $out = (string) ob_get_clean();
            imagedestroy($tiny);
            imagedestroy($blurred);
            imagedestroy($source);

            return $out !== '' ? $out : $rawBytes;
        }

        $cropX = (int) round($w * 0.04);
        $cropY = (int) round($h * 0.04);
        $cropW = max(1, $w - ($cropX * 2));
        $cropH = max(1, $h - ($cropY * 2));

        $variant = imagecreatetruecolor($cropW, $cropH);
        imagecopyresampled($variant, $source, 0, 0, $cropX, $cropY, $cropW, $cropH, $cropW, $cropH);
        imagefilter($variant, IMG_FILTER_BRIGHTNESS, 8);
        imagefilter($variant, IMG_FILTER_CONTRAST, -4);

        ob_start();
        imagejpeg($variant, null, 82);
        $out = (string) ob_get_clean();

        imagedestroy($variant);
        imagedestroy($source);

        return $out !== '' ? $out : $rawBytes;
    }
}
