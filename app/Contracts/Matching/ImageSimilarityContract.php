<?php

namespace App\Contracts\Matching;

use App\Models\ItemReport;

interface ImageSimilarityContract
{
    public function isEnabled(): bool;

    public function similarity(ItemReport $lost, ItemReport $found): ?float;

    public function modelVersion(): string;
}
