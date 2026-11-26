<?php

namespace App\Jobs;

use App\Models\ItemImage;
use App\Services\Matching\VisualSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateImageEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $itemImageId) {}

    public function handle(VisualSearchService $visualSearch): void
    {
        if (! $visualSearch->isImageMatchingEnabled() && ! $visualSearch->isVisualSearchEnabled()) {
            return;
        }

        $image = ItemImage::query()->find($this->itemImageId);

        if (! $image instanceof ItemImage) {
            return;
        }

        $visualSearch->indexItemImage($image);
    }
}
