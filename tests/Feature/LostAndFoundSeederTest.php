<?php

namespace Tests\Feature;

use App\Enums\ReportType;
use App\Models\ItemReport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LostAndFoundSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_reports_command_adds_exact_requested_lost_and_found_counts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('blf:seed-reports', ['--lost' => 4, '--found' => 6])
            ->expectsOutputToContain('Added 4 lost reports and 6 found reports.')
            ->assertSuccessful();

        $this->assertSame(4, ItemReport::query()->where('type', ReportType::Lost)->count());
        $this->assertSame(6, ItemReport::query()->where('type', ReportType::Found)->count());
        $this->assertSame(10, ItemReport::query()->whereNotNull('search_text')->count());
    }
}
