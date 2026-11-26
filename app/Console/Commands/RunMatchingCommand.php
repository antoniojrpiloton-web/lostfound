<?php

namespace App\Console\Commands;

use App\Enums\ItemStatus;
use App\Enums\VerificationStatus;
use App\Models\ItemReport;
use App\Services\Matching\ItemMatchingService;
use Illuminate\Console\Command;

class RunMatchingCommand extends Command
{
    protected $signature = 'blf:run-matching';

    protected $description = 'Run metadata matching across all verified open lost and in-custody found reports';

    public function handle(ItemMatchingService $matchingService): int
    {
        $reports = ItemReport::query()
            ->where('verification_status', VerificationStatus::Verified)
            ->whereIn('item_status', [ItemStatus::Open->value, ItemStatus::InCustody->value, ItemStatus::ClaimPending->value])
            ->get();

        $totalMatches = 0;

        foreach ($reports as $report) {
            $totalMatches += $matchingService->matchForReport($report)->count();
        }

        $this->info("Scanned {$reports->count()} verified reports; evaluated {$totalMatches} candidate match links.");

        return self::SUCCESS;
    }
}
