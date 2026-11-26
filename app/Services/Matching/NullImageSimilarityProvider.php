<?php

namespace App\Services\Matching;

use App\Contracts\Matching\ImageSimilarityContract;
use App\Models\ItemReport;

class NullImageSimilarityProvider implements ImageSimilarityContract
{
    public function isEnabled(): bool
    {
        return false;
    }

    public function similarity(ItemReport $lost, ItemReport $found): ?float
    {
        return null;
    }

    public function modelVersion(): string
    {
        return 'none';
    }
}
