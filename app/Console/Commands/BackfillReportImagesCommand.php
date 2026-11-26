<?php

namespace App\Console\Commands;

use App\Enums\ImageValidationStatus;
use App\Models\ImageEmbedding;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Services\Matching\VisualSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackfillReportImagesCommand extends Command
{
    protected $signature = 'blf:backfill-report-images';

    protected $description = 'Attach realistic category-matched photographs and visual embeddings to all reports without images.';

    public function handle(VisualSearchService $visualSearch): int
    {
        $sourceDir = storage_path('app/demo-items');
        $exportDir = base_path('demo-search-photos');
        File::ensureDirectoryExists($exportDir);

        $availableFiles = [
            'phone' => 'blue-iphone-15-pro.jpg',
            'wallet' => 'black-leather-wallet.jpg',
            'camera' => 'gopro-action-camera.jpg',
            'eyewear' => 'rayban-sunglasses.jpg',
            'bag' => 'blue-waterproof-drybag.jpg',
            'audio' => 'white-airpods-pro.jpg',
            'watch' => 'black-gshock-watch.jpg',
            'laptop' => 'silver-laptop.jpg',
            'passport' => 'navy-passport-holder.jpg',
            'keys' => 'keys-blue-lanyard.jpg',
        ];

        $prepared = [];

        foreach ($availableFiles as $key => $filename) {
            $path = $sourceDir.DIRECTORY_SEPARATOR.$filename;
            if (! File::exists($path)) {
                continue;
            }

            $raw = File::get($path);
            $privatePath = 'item-images/shared/'.$filename;
            $publicPath = 'item-images/shared/'.$filename;

            Storage::disk('local')->put($privatePath, $raw);
            Storage::disk('public')->put($publicPath, $raw);

            $gd = @imagecreatefromstring($raw);
            $width = $gd !== false ? imagesx($gd) : null;
            $height = $gd !== false ? imagesy($gd) : null;
            if ($gd !== false) {
                imagedestroy($gd);
            }

            $embedded = $visualSearch->embedRawBytes($raw, $filename);

            $prepared[$key] = [
                'filename' => $filename,
                'private_path' => $privatePath,
                'public_path' => $publicPath,
                'size' => strlen($raw),
                'width' => $width,
                'height' => $height,
                'sha256' => hash('sha256', $raw),
                'embedding' => $embedded,
            ];

            $exportPath = $exportDir.DIRECTORY_SEPARATOR.'search-'.$filename;
            if (! File::exists($exportPath)) {
                File::put($exportPath, $this->createVariant($raw));
            }
        }

        if ($prepared === []) {
            $this->error('No demo images found in '.$sourceDir);

            return self::FAILURE;
        }

        $fallbackKeys = array_keys($prepared);
        $missingReports = ItemReport::query()
            ->doesntHave('images')
            ->with('category')
            ->get();

        $attached = 0;

        foreach ($missingReports as $index => $report) {
            $chosenKey = $this->resolvePhotoKey($report, $prepared) ?? $fallbackKeys[$index % count($fallbackKeys)];
            $asset = $prepared[$chosenKey];

            $image = ItemImage::query()->create([
                'item_report_id' => $report->id,
                'private_path' => $asset['private_path'],
                'public_path' => $report->is_public ? $asset['public_path'] : null,
                'mime' => 'image/jpeg',
                'size' => $asset['size'],
                'width' => $asset['width'],
                'height' => $asset['height'],
                'sha256' => $asset['sha256'],
                'validation_status' => ImageValidationStatus::Passed,
                'validation_result' => ['source' => 'category_backfill', 'status' => 'passed'],
                'is_primary' => true,
            ]);

            if (! empty($asset['embedding']['supported']) && ! empty($asset['embedding']['vector'])) {
                ImageEmbedding::query()->updateOrCreate(
                    ['item_image_id' => $image->id],
                    [
                        'item_report_id' => $report->id,
                        'model_version' => (string) $asset['embedding']['model_version'],
                        'dimensions' => count($asset['embedding']['vector']),
                        'vector' => $asset['embedding']['vector'],
                    ]
                );
            }

            $attached++;
        }

        $remaining = ItemReport::query()->doesntHave('images')->count();
        $totalReports = ItemReport::query()->count();

        $this->info("Attached images & visual embeddings to {$attached} report(s).");
        $this->info("Total reports: {$totalReports} | Reports without images: {$remaining}");

        return self::SUCCESS;
    }

    protected function resolvePhotoKey(ItemReport $report, array $prepared): ?string
    {
        $haystack = strtolower(trim(
            ($report->category?->slug ?? '').' '.
            ($report->category?->name_en ?? '').' '.
            $report->item_name.' '.
            ($report->brand ?? '')
        ));

        $rules = [
            'laptop' => ['laptop', 'macbook', 'ipad', 'tablet', 'acer', 'thinkpad', 'notebook'],
            'phone' => ['phone', 'iphone', 'samsung', 'galaxy', 'xiaomi', 'pixel', 'mobile', 'cellphone'],
            'wallet' => ['wallet', 'purse', 'billfold', 'cardholder', 'pitaka'],
            'camera' => ['camera', 'gopro', 'dji', 'action', 'lens', 'dslr', 'canon', 'sony'],
            'eyewear' => ['sunglass', 'eyewear', 'glasses', 'ray-ban', 'rayban', 'oakley', 'shades'],
            'bag' => ['bag', 'drybag', 'dry bag', 'backpack', 'luggage', 'tote', 'pouch', 'sling'],
            'audio' => ['airpod', 'earbud', 'earphone', 'headphone', 'speaker', 'audio', 'jbl'],
            'watch' => ['watch', 'g-shock', 'gshock', 'casio', 'rolex', 'seiko', 'smartwatch', 'jewelry', 'ring', 'bracelet'],
            'passport' => ['passport', 'id', 'license', 'card', 'document', 'visa', 'prc', 'umid'],
            'keys' => ['key', 'lanyard', 'keychain', 'keycard', 'fob'],
        ];

        foreach ($rules as $key => $keywords) {
            if (! isset($prepared[$key])) {
                continue;
            }
            foreach ($keywords as $word) {
                if (str_contains($haystack, $word)) {
                    return $key;
                }
            }
        }

        return null;
    }

    protected function createVariant(string $rawBytes): string
    {
        $source = @imagecreatefromstring($rawBytes);
        if ($source === false) {
            return $rawBytes;
        }

        $w = imagesx($source);
        $h = imagesy($source);
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
