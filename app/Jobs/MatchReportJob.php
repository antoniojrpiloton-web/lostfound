<?php

namespace App\Jobs;

use App\Models\ItemReport;
use App\Services\Matching\ItemMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MatchReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $itemReportId) {}

    public function handle(ItemMatchingService $matchingService): void
    {
        $report = ItemReport::query()->find($this->itemReportId);

        if (! $report instanceof ItemReport) {
            return;
        }

        $matchingService->matchForReport($report);
    }
}
