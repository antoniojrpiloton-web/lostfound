<?php

namespace App\Console\Commands;

use Database\Seeders\LostAndFoundSeeder;
use Illuminate\Console\Command;

class SeedLostAndFoundCommand extends Command
{
    protected $signature = 'blf:seed-reports
        {--lost= : Number of lost item reports to add}
        {--found= : Number of found item reports to add}';

    protected $description = 'Seed a custom number of realistic Boracay lost and found item reports';

    public function handle(LostAndFoundSeeder $seeder): int
    {
        $lostOption = $this->option('lost');
        $foundOption = $this->option('found');

        $lostCount = $lostOption !== null
            ? (int) $lostOption
            : (int) $this->ask('How many LOST item reports would you like to add?', '10');

        $foundCount = $foundOption !== null
            ? (int) $foundOption
            : (int) $this->ask('How many FOUND item reports would you like to add?', '10');

        if ($lostCount < 0 || $foundCount < 0 || $lostCount > 500 || $foundCount > 500) {
            $this->error('Please enter numbers between 0 and 500.');

            return self::FAILURE;
        }

        $result = $seeder->seedReports($lostCount, $foundCount);

        $this->info("Added {$result['lost']} lost reports and {$result['found']} found reports.");

        return self::SUCCESS;
    }
}
