<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemReport;
use App\Models\User;
use App\Services\Reports\SearchTextBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class SearchAndFilteringTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_search_excludes_unpublished_rejected_confidential_and_disposed_records(): void
    {
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $builder = app(SearchTextBuilder::class);

        $publicReport = ItemReport::factory()->verified()->create([
            'item_name' => 'Published Emerald Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
        ]);
        $publicReport->forceFill(['search_text' => $builder->build($publicReport)])->save();

        $pending = ItemReport::factory()->create([
            'item_name' => 'Pending Secret Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'verification_status' => VerificationStatus::Pending,
            'is_public' => false,
        ]);
        $pending->forceFill(['search_text' => $builder->build($pending)])->save();

        $confidential = ItemReport::factory()->verified()->create([
            'item_name' => 'Confidential Evidence Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'is_confidential' => true,
            'is_public' => true,
        ]);
        $confidential->forceFill(['search_text' => $builder->build($confidential)])->save();

        $disposed = ItemReport::factory()->found()->verified()->create([
            'item_name' => 'Disposed Old Phone',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'item_status' => ItemStatus::Disposed,
        ]);
        $disposed->forceFill(['search_text' => $builder->build($disposed)])->save();

        $response = $this->get(route('public.items.index'));
        $response->assertOk()
            ->assertSee('Published Emerald Phone')
            ->assertDontSee('Pending Secret Phone')
            ->assertDontSee('Confidential Evidence Phone')
            ->assertDontSee('Disposed Old Phone');

        $this->get(route('public.items.show', $pending->reference_no))->assertNotFound();
        $this->get(route('public.items.show', $confidential->reference_no))->assertNotFound();
    }

    public function test_partial_case_insensitive_keyword_and_hierarchical_category_filters_work(): void
    {
        $electronics = Category::query()->where('slug', 'electronics')->firstOrFail();
        $phones = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $wallets = Category::query()->where('slug', 'wallets-purses')->firstOrFail();
        $balabag = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $yapak = Barangay::query()->where('code', 'BRGY-YAPAK')->firstOrFail();
        $builder = app(SearchTextBuilder::class);

        $iphone = ItemReport::factory()->verified()->create([
            'item_name' => 'Titanium Blue iPhone 15 Pro',
            'description' => 'Dropped near Station 2 with turtle sticker.',
            'category_id' => $phones->id,
            'barangay_id' => $balabag->id,
            'occurred_date' => '2026-11-20',
            'contact_phone_enc' => '+639170009999',
        ]);
        $iphone->forceFill(['search_text' => $builder->build($iphone)])->save();

        $wallet = ItemReport::factory()->found()->verified()->create([
            'item_name' => 'Brown Leather Wallet',
            'description' => 'Found near Puka Shell Beach.',
            'category_id' => $wallets->id,
            'barangay_id' => $yapak->id,
            'occurred_date' => '2026-11-10',
        ]);
        $wallet->forceFill(['search_text' => $builder->build($wallet)])->save();

        $this->get(route('public.items.index', ['q' => 'TURT']))
            ->assertOk()
            ->assertSee('Titanium Blue iPhone 15 Pro')
            ->assertDontSee('Brown Leather Wallet');

        $this->get(route('public.items.index', [
            'category_id' => $electronics->id,
            'barangay_id' => $balabag->id,
            'date_from' => '2026-11-15',
        ]))
            ->assertOk()
            ->assertSee('Titanium Blue iPhone 15 Pro')
            ->assertDontSee('Brown Leather Wallet');

        $this->getJson(route('api.search', ['q' => 'iph']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference_no', $iphone->reference_no);

        $reporter = User::query()->findOrFail($iphone->reporter_user_id);

        $this->get(route('public.items.show', $iphone->reference_no))
            ->assertOk()
            ->assertSee('Titanium Blue iPhone 15 Pro')
            ->assertDontSee('+639170009999')
            ->assertDontSee($reporter->email);
    }
}
