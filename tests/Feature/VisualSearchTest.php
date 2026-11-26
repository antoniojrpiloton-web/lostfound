<?php

namespace Tests\Feature;

use App\Enums\ImageValidationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ImageEmbedding;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\SystemSetting;
use App\Services\MasterData\MasterDataCacheService;
use App\Services\Matching\ItemMatchingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisualSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_visual_search_is_hidden_and_blocked_when_setting_is_disabled(): void
    {
        $this->get(route('public.items.index'))
            ->assertOk()
            ->assertDontSee('Search by Photo');

        $this->post(route('public.items.visual_search'), [
            'photo' => UploadedFile::fake()->image('query.jpg', 400, 300),
        ])->assertForbidden();
    }

    public function test_visual_search_ranks_similar_verified_found_items_when_enabled(): void
    {
        SystemSetting::query()->where('key', 'matching.visual_search_enabled')->update(['value' => ['value' => true]]);
        app(MasterDataCacheService::class)->forgetSettings();

        Http::fake([
            '*/v1/embed' => Http::response([
                'supported' => true,
                'model_version' => 'ViT-B-32:test',
                'dimensions' => 3,
                'vector' => [1.0, 0.0, 0.0],
            ], 200),
        ]);

        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $matchingFound = ItemReport::factory()->found()->verified()->create([
            'item_name' => 'Matching Visual Found Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
        ]);

        $otherFound = ItemReport::factory()->found()->verified()->create([
            'item_name' => 'Dissimilar Found Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
        ]);

        $img1 = ItemImage::query()->create([
            'item_report_id' => $matchingFound->id,
            'private_path' => 'private/a.jpg',
            'mime' => 'image/jpeg',
            'size' => 1000,
            'sha256' => hash('sha256', 'a'),
            'validation_status' => ImageValidationStatus::Passed,
            'is_primary' => true,
        ]);

        $img2 = ItemImage::query()->create([
            'item_report_id' => $otherFound->id,
            'private_path' => 'private/b.jpg',
            'mime' => 'image/jpeg',
            'size' => 1000,
            'sha256' => hash('sha256', 'b'),
            'validation_status' => ImageValidationStatus::Passed,
            'is_primary' => true,
        ]);

        ImageEmbedding::query()->create([
            'item_image_id' => $img1->id,
            'item_report_id' => $matchingFound->id,
            'model_version' => 'ViT-B-32:test',
            'dimensions' => 3,
            'vector' => [0.98, 0.2, 0.0],
        ]);

        ImageEmbedding::query()->create([
            'item_image_id' => $img2->id,
            'item_report_id' => $otherFound->id,
            'model_version' => 'ViT-B-32:test',
            'dimensions' => 3,
            'vector' => [0.0, 1.0, 0.0],
        ]);

        $this->get(route('public.items.index'))
            ->assertOk()
            ->assertSee('Search by Photo');

        $this->post(route('public.items.visual_search'), [
            'photo' => UploadedFile::fake()->image('query.jpg', 400, 300),
        ])
            ->assertOk()
            ->assertSee('Matching Visual Found Phone')
            ->assertDontSee('Dissimilar Found Phone');
    }

    public function test_image_matching_blends_clip_similarity_when_enabled(): void
    {
        SystemSetting::query()->where('key', 'matching.image_enabled')->update(['value' => ['value' => true]]);
        app(MasterDataCacheService::class)->forgetSettings();

        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $lost = ItemReport::factory()->verified()->create([
            'item_name' => 'Blue Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'occurred_date' => now()->toDateString(),
        ]);

        $found = ItemReport::factory()->found()->verified()->create([
            'item_name' => 'Blue Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'occurred_date' => now()->toDateString(),
        ]);

        foreach ([$lost, $found] as $idx => $rep) {
            $img = ItemImage::query()->create([
                'item_report_id' => $rep->id,
                'private_path' => "private/{$idx}.jpg",
                'mime' => 'image/jpeg',
                'size' => 1000,
                'sha256' => hash('sha256', (string) $idx),
                'validation_status' => ImageValidationStatus::Passed,
                'is_primary' => true,
            ]);

            ImageEmbedding::query()->create([
                'item_image_id' => $img->id,
                'item_report_id' => $rep->id,
                'model_version' => 'ViT-B-32:test',
                'dimensions' => 3,
                'vector' => [1.0, 0.0, 0.0],
            ]);
        }

        $result = app(ItemMatchingService::class)->scorePair($lost, $found);

        $this->assertArrayHasKey('image', $result['factors']);
        $this->assertSame(1.0, $result['factors']['image']);
        $this->assertStringContainsString('+img:ViT-B-32:test', $result['algorithm_version']);
    }
}
