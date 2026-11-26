<?php

namespace App\Services\Reports;

use App\Enums\ImageValidationStatus;
use App\Exceptions\ImageValidationException;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Services\Matching\VisualSearchService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ReportImageService
{
    public function __construct(
        public ImageValidationClient $validator,
        public VisualSearchService $visualSearch,
    ) {}

    public function storeForReport(
        ItemReport $report,
        UploadedFile $file,
        bool $isPrimary = false,
    ): ItemImage {
        $report->loadMissing('category');
        $validation = $this->validator->validate($file, $report->category?->slug);

        if ($validation['status'] === ImageValidationStatus::Rejected) {
            throw new ImageValidationException(
                $validation['messages'] !== [] ? $validation['messages'] : [__('The image could not be processed.')]
            );
        }

        $realPath = $file->getRealPath();
        $sha256 = $realPath !== false ? hash_file('sha256', $realPath) : hash('sha256', (string) $file->get());
        $dimensions = $realPath !== false ? @getimagesize($realPath) : false;
        $width = is_array($dimensions) ? (int) $dimensions[0] : null;
        $height = is_array($dimensions) ? (int) $dimensions[1] : null;

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $uuid = (string) Str::uuid();
        $privatePath = "item-images/private/{$report->id}/{$uuid}.{$extension}";

        Storage::disk('local')->putFileAs(
            "item-images/private/{$report->id}",
            $file,
            "{$uuid}.{$extension}",
        );

        $image = ItemImage::query()->create([
            'item_report_id' => $report->id,
            'private_path' => $privatePath,
            'public_path' => null,
            'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
            'width' => $width,
            'height' => $height,
            'sha256' => (string) $sha256,
            'validation_status' => $validation['status'],
            'validation_result' => $validation['result'],
            'is_primary' => $isPrimary,
        ]);

        try {
            $this->visualSearch->indexItemImage($image);
        } catch (Throwable) {
        }

        return $image;
    }

    public function replaceImage(ItemImage $existing, UploadedFile $file): ItemImage
    {
        $report = $existing->itemReport()->with('category')->firstOrFail();
        $validation = $this->validator->validate($file, $report->category?->slug);

        if ($validation['status'] === ImageValidationStatus::Rejected) {
            throw new ImageValidationException(
                $validation['messages'] !== [] ? $validation['messages'] : [__('The image could not be processed.')]
            );
        }

        $oldPrivatePath = $existing->private_path;
        $oldPublicPath = $existing->public_path;

        $realPath = $file->getRealPath();
        $sha256 = $realPath !== false ? hash_file('sha256', $realPath) : hash('sha256', (string) $file->get());
        $dimensions = $realPath !== false ? @getimagesize($realPath) : false;
        $width = is_array($dimensions) ? (int) $dimensions[0] : null;
        $height = is_array($dimensions) ? (int) $dimensions[1] : null;

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $uuid = (string) Str::uuid();
        $privatePath = "item-images/private/{$report->id}/{$uuid}.{$extension}";

        Storage::disk('local')->putFileAs(
            "item-images/private/{$report->id}",
            $file,
            "{$uuid}.{$extension}",
        );

        $existing->forceFill([
            'private_path' => $privatePath,
            'public_path' => null,
            'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
            'width' => $width,
            'height' => $height,
            'sha256' => (string) $sha256,
            'validation_status' => $validation['status'],
            'validation_result' => $validation['result'],
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->save();

        if ($oldPrivatePath !== '' && Storage::disk('local')->exists($oldPrivatePath)) {
            Storage::disk('local')->delete($oldPrivatePath);
        }

        if ($oldPublicPath !== null && Storage::disk('public')->exists($oldPublicPath)) {
            Storage::disk('public')->delete($oldPublicPath);
        }

        return $existing->fresh();
    }

    public function generatePublicDerivative(ItemImage $image): ?string
    {
        if (! Storage::disk('local')->exists($image->private_path)) {
            return null;
        }

        $raw = Storage::disk('local')->get($image->private_path);
        $gdImage = @imagecreatefromstring($raw);

        if ($gdImage === false) {
            return null;
        }

        ob_start();
        imagejpeg($gdImage, null, 85);
        $encoded = ob_get_clean();
        imagedestroy($gdImage);

        if ($encoded === false) {
            return null;
        }

        $publicPath = "item-images/public/{$image->item_report_id}/".Str::uuid().'.jpg';
        Storage::disk('public')->put($publicPath, $encoded);

        $image->forceFill(['public_path' => $publicPath])->save();

        return $publicPath;
    }
}
