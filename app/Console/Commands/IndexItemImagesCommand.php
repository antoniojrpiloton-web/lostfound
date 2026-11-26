<?php

namespace App\Console\Commands;

use App\Models\ImageEmbedding;
use App\Models\ItemImage;
use App\Services\Matching\VisualSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class IndexItemImagesCommand extends Command
{
    protected $signature = 'blf:index-images';

    protected $description = 'Generate visual search embeddings for all stored item images.';

    public function handle(VisualSearchService $visualSearch): int
    {
        $total = ItemImage::query()->count();
        $indexed = 0;

        $cacheByHash = [];

        foreach (ItemImage::query()->with('itemReport')->cursor() as $image) {
            $hash = (string) $image->sha256;
            if ($hash !== '' && isset($cacheByHash[$hash])) {
                $embedded = $cacheByHash[$hash];
            } else {
                if (! Storage::disk('local')->exists($image->private_path)) {
                    continue;
                }
                $bytes = Storage::disk('local')->get($image->private_path);
                $embedded = $visualSearch->embedRawBytes($bytes, basename($image->private_path));
                if ($hash !== '' && $embedded['supported'] && $embedded['vector'] !== []) {
                    $cacheByHash[$hash] = $embedded;
                }
            }

            if (! $embedded['supported'] || $embedded['vector'] === []) {
                continue;
            }

            ImageEmbedding::query()->updateOrCreate(
                ['item_image_id' => $image->id],
                [
                    'item_report_id' => $image->item_report_id,
                    'model_version' => $embedded['model_version'],
                    'dimensions' => count($embedded['vector']),
                    'vector' => $embedded['vector'],
                ]
            );
            $indexed++;
        }

        $this->info("Indexed {$indexed} of {$total} item image(s) for visual search.");
        $this->line(sprintf(
            'Settings: visual_search_enabled=%s | image_enabled=%s',
            $visualSearch->isVisualSearchEnabled() ? 'true' : 'false',
            $visualSearch->isImageMatchingEnabled() ? 'true' : 'false'
        ));

        foreach (ItemImage::query()->with('itemReport')->latest()->limit(5)->get() as $img) {
            $report = $img->itemReport;
            if ($report !== null) {
                $this->line(sprintf(
                    ' - %s (%s) | verification=%s | public=%s | %s',
                    $report->reference_no,
                    $report->type->value,
                    $report->verification_status->value,
                    $report->is_public ? 'yes' : 'no',
                    $report->item_name
                ));
            }
        }

        return self::SUCCESS;
    }
}
