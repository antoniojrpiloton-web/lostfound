<?php

namespace Tests\Feature;

use App\Models\Barangay;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Select2LookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_categories_select2_endpoint_supports_server_side_search_and_pagination(): void
    {
        $response = $this->getJson(route('api.select2.categories', ['q' => 'phone']));

        $response->assertOk()
            ->assertJsonStructure(['results' => [['id', 'text']], 'pagination' => ['more']]);

        $texts = collect($response->json('results'))->pluck('text')->implode(' ');
        $this->assertStringContainsString('Mobile Phones', $texts);
    }

    public function test_barangays_locations_and_organizations_select2_endpoints_filter_correctly(): void
    {
        $balabag = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $this->getJson(route('api.select2.barangays', ['q' => 'Balabag']))
            ->assertOk()
            ->assertJsonCount(1, 'results');

        $locResponse = $this->getJson(route('api.select2.locations', ['barangay_id' => $balabag->id, 'q' => 'Station']));
        $locResponse->assertOk();
        $this->assertGreaterThanOrEqual(2, count($locResponse->json('results')));

        $this->getJson(route('api.select2.organizations', ['q' => 'Malay Municipal Police']))
            ->assertOk()
            ->assertJsonCount(1, 'results');
    }
}
