<?php

namespace App\Console\Commands;

use App\Services\Disposals\DisposalService;
use Illuminate\Console\Command;

class FlagDisposalEligibleCommand extends Command
{
    protected $signature = 'blf:flag-disposal-eligible';

    protected $description = 'Flag verified unclaimed found items past the retention period as eligible for disposal review.';

    public function handle(DisposalService $disposalService): int
    {
        $flagged = $disposalService->flagEligibleItems();
        $this->info("Flagged {$flagged} found item(s) as eligible for disposal review.");

        return self::SUCCESS;
    }
}
