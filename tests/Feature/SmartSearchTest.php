<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\MasterData\MasterDataCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SmartSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_topbar_smart_search_supports_text_and_local_php_gd_photo_matching(): void
    {
        Storage::fake('local');

        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $found = ItemReport::factory()->found()->verified()->create([
            'item_name' => 'Acer Nitro Gaming Laptop',
            'search_text' => 'acer nitro gaming laptop station 2',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'item_status' => ItemStatus::InCustody,
            'is_public' => true,
            'is_confidential' => false,
        ]);

        $this->postJson(route('api.smart_search'), [
            'q' => 'Acer Nitro',
        ])
            ->assertOk()
            ->assertJsonPath('mode', 'text')
            ->assertJsonPath('items.0.reference_no', $found->reference_no);

        SystemSetting::query()->where('key', 'matching.visual_search_enabled')->update(['value' => ['value' => true]]);
        app(MasterDataCacheService::class)->forgetSettings();

        $photo = UploadedFile::fake()->image('acer.jpg', 300, 200);
        $rawBytes = (string) $photo->get();
        $path = 'item-images/private/'.$found->id.'/acer.jpg';
        Storage::disk('local')->put($path, $rawBytes);

        ItemImage::query()->create([
            'item_report_id' => $found->id,
            'private_path' => $path,
            'mime' => 'image/jpeg',
            'size' => strlen($rawBytes),
            'sha256' => hash('sha256', $rawBytes),
            'is_primary' => true,
        ]);

        $otherPhoto = UploadedFile::fake()->image('other.jpg', 200, 200);

        $this->post(route('api.smart_search'), [
            'photos' => [
                $otherPhoto,
                UploadedFile::fake()->createWithContent('acer.jpg', $rawBytes),
            ],
            'type' => ReportType::Found->value,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('mode', 'visual')
            ->assertJsonPath('items.0.reference_no', $found->reference_no)
            ->assertJsonPath('items.0.score', 100);
    }

    public function test_institutional_user_can_photo_search_own_org_pending_items_while_public_cannot(): void
    {
        Storage::fake('local');

        SystemSetting::query()->where('key', 'matching.visual_search_enabled')->update(['value' => ['value' => true]]);
        app(MasterDataCacheService::class)->forgetSettings();

        $org = Organization::query()->firstOrFail();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $admin = User::factory()->create();
        $role = Role::query()->where('key', 'institution_admin')->firstOrFail();
        OrganizationMembership::query()->create([
            'user_id' => $admin->id,
            'organization_id' => $org->id,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
        ]);

        $pendingFound = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'item_name' => 'Pending Desk Tablet',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
            'verification_status' => VerificationStatus::Pending,
            'item_status' => ItemStatus::InCustody,
            'is_public' => false,
        ]);

        $photo = UploadedFile::fake()->image('tablet.jpg', 260, 200);
        $rawBytes = (string) $photo->get();
        $path = 'item-images/private/'.$pendingFound->id.'/tablet.jpg';
        Storage::disk('local')->put($path, $rawBytes);

        ItemImage::query()->create([
            'item_report_id' => $pendingFound->id,
            'private_path' => $path,
            'mime' => 'image/jpeg',
            'size' => strlen($rawBytes),
            'sha256' => hash('sha256', $rawBytes),
            'is_primary' => true,
        ]);

        $this->post(route('api.smart_search'), [
            'photos' => [UploadedFile::fake()->createWithContent('tablet.jpg', $rawBytes)],
            'scope' => 'org',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonCount(0, 'items');

        $this->actingAs($admin)
            ->post(route('api.smart_search'), [
                'photos' => [UploadedFile::fake()->createWithContent('tablet.jpg', $rawBytes)],
                'scope' => 'org',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('items.0.reference_no', $pendingFound->reference_no)
            ->assertJsonPath('items.0.score', 100);
    }
}
