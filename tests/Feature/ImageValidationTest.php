<?php

namespace Tests\Feature;

use App\Enums\ImageValidationStatus;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config()->set('services.image_validator.enabled', true);
        config()->set('services.image_validator.url', 'http://127.0.0.1:8001');
        config()->set('services.image_validator.token', 'test-token');
    }

    public function test_accepted_image_is_stored_with_passed_status_and_metrics(): void
    {
        Storage::fake('local');
        Http::fake([
            'http://127.0.0.1:8001/v1/validate' => Http::response([
                'verdict' => 'accepted',
                'messages' => [],
                'checks' => ['laplacian_variance' => 245.5, 'mean_luminance' => 128.0],
                'service_version' => '1.0.0',
                'model_version' => 'none',
            ], 200),
        ]);

        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();

        $this->actingAs($user)->post(route('reports.found.store'), [
            'item_name' => 'Clear Phone Photo',
            'category_id' => $category->id,
            'description' => 'Found near Station 1 beachfront.',
            'occurred_date' => now()->toDateString(),
            'barangay_id' => $barangay->id,
            'custody_organization_id' => $org->id,
            'photos' => [UploadedFile::fake()->image('clear.jpg', 640, 480)],
        ])->assertRedirect();

        $image = ItemImage::query()->firstOrFail();
        $this->assertSame(ImageValidationStatus::Passed, $image->validation_status);
        $this->assertSame(245.5, $image->validation_result['checks']['laplacian_variance']);
        Storage::disk('local')->assertExists($image->private_path);
    }

    public function test_rejected_blurry_image_blocks_submission_with_clear_message_and_saves_no_file(): void
    {
        Storage::fake('local');
        Http::fake([
            'http://127.0.0.1:8001/v1/validate' => Http::response([
                'verdict' => 'rejected',
                'messages' => ['The uploaded image is too blurry.'],
                'checks' => ['laplacian_variance' => 12.4],
                'service_version' => '1.0.0',
            ], 200),
        ]);

        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();

        $this->actingAs($user)
            ->post(route('reports.found.store'), [
                'item_name' => 'Blurry Item',
                'category_id' => $category->id,
                'description' => 'Found near Station 2.',
                'occurred_date' => now()->toDateString(),
                'barangay_id' => $barangay->id,
                'custody_organization_id' => $org->id,
                'photos' => [UploadedFile::fake()->image('blurry.jpg', 640, 480)],
            ])
            ->assertSessionHasErrors(['photos' => 'The uploaded image is too blurry.']);

        $this->assertSame(0, ItemReport::query()->count());
        $this->assertSame(0, ItemImage::query()->count());
    }

    public function test_service_unavailable_respects_fail_mode_review_and_reject(): void
    {
        Storage::fake('local');
        Http::fake([
            'http://127.0.0.1:8001/v1/validate' => Http::response([], 503),
        ]);

        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();

        $this->actingAs($user)->post(route('reports.found.store'), [
            'item_name' => 'Queued For Review',
            'category_id' => $category->id,
            'description' => 'Submitted while validator is offline.',
            'occurred_date' => now()->toDateString(),
            'barangay_id' => $barangay->id,
            'custody_organization_id' => $org->id,
            'photos' => [UploadedFile::fake()->image('offline.jpg', 640, 480)],
        ])->assertRedirect();

        $this->assertSame(ImageValidationStatus::Review, ItemImage::query()->firstOrFail()->validation_status);

        SystemSetting::query()->where('key', 'image_validation.fail_mode')->update(['value' => ['value' => 'reject']]);
        app(MasterDataCacheService::class)->forgetSettings();

        $this->actingAs($user)
            ->post(route('reports.found.store'), [
                'item_name' => 'Rejected While Offline',
                'category_id' => $category->id,
                'description' => 'Submitted while fail_mode is reject.',
                'occurred_date' => now()->toDateString(),
                'barangay_id' => $barangay->id,
                'custody_organization_id' => $org->id,
                'photos' => [UploadedFile::fake()->image('offline2.jpg', 640, 480)],
            ])
            ->assertSessionHasErrors('photos');
    }

    public function test_admin_manual_review_and_user_replacement_photo_flow(): void
    {
        Storage::fake('local');
        Http::fakeSequence('http://127.0.0.1:8001/v1/validate')
            ->push(['verdict' => 'review', 'messages' => ['Image sharpness is borderline.']], 200)
            ->push(['verdict' => 'accepted', 'messages' => []], 200);

        $reporter = User::factory()->create();
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $yapak = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();
        $balabagAdmin = $this->createUserWithRole('institution_admin', $balabag);
        $balabagStaff = $this->createUserWithRole('institution_staff', $balabag);
        $yapakAdmin = $this->createUserWithRole('institution_admin', $yapak);

        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $barangay = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();

        $this->actingAs($reporter)->post(route('reports.found.store'), [
            'item_name' => 'Borderline Photo Item',
            'category_id' => $category->id,
            'description' => 'Found near Station 2.',
            'occurred_date' => now()->toDateString(),
            'barangay_id' => $barangay->id,
            'custody_organization_id' => $balabag->id,
            'photos' => [UploadedFile::fake()->image('borderline.jpg', 640, 480)],
        ])->assertRedirect();

        $report = ItemReport::query()->firstOrFail();
        $image = $report->images->firstOrFail();

        $this->actingAs($balabagAdmin)->get(route('admin.images.reviews'))->assertOk()->assertSee($report->reference_no);
        $this->actingAs($balabagStaff)->patch(route('admin.images.decide', $image), ['decision' => 'overridden'])->assertForbidden();
        $this->actingAs($yapakAdmin)->patch(route('admin.images.decide', $image), ['decision' => 'overridden'])->assertForbidden();

        $this->actingAs($balabagAdmin)
            ->patch(route('admin.images.decide', $image), ['decision' => 'rejected', 'remarks' => 'Please upload a clearer photo.'])
            ->assertRedirect(route('admin.images.reviews'));

        $this->assertSame(ImageValidationStatus::Rejected, $image->fresh()->validation_status);
        $this->assertTrue(AuditLog::query()->where('action', 'image.manual_review_decided')->exists());

        $this->actingAs($reporter)
            ->post(route('reports.image_replace', [$report, $image]), [
                'photo' => UploadedFile::fake()->image('replacement.jpg', 640, 480),
            ])
            ->assertRedirect(route('reports.show_own', $report));

        $this->assertSame(ImageValidationStatus::Passed, $image->fresh()->validation_status);
    }

    public function test_live_image_precheck_endpoint_returns_blur_and_subject_verdict(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/v1/validate' => Http::sequence()
                ->push([
                    'verdict' => 'rejected',
                    'messages' => ['The uploaded image is too blurry.', 'No recognizable item was detected.'],
                    'checks' => [
                        'laplacian_variance' => 14.2,
                        'object_detection' => ['object_probability' => 0.18],
                    ],
                ], 200)
                ->push([
                    'verdict' => 'accepted',
                    'messages' => [],
                    'checks' => [
                        'laplacian_variance' => 280.4,
                        'object_detection' => ['object_probability' => 0.96],
                    ],
                ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.images.precheck'), [
                'image' => UploadedFile::fake()->image('blurry.jpg', 640, 480),
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'rejected',
                'messages' => ['The uploaded image is too blurry.', 'No recognizable item was detected.'],
                'metrics' => [
                    'sharpness' => 14.2,
                    'object_confidence' => 18,
                ],
            ]);

        $this->actingAs($user)
            ->postJson(route('api.images.precheck'), [
                'image' => UploadedFile::fake()->image('sharp.jpg', 640, 480),
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'passed',
                'messages' => [],
                'metrics' => [
                    'sharpness' => 280.4,
                    'object_confidence' => 96,
                ],
            ]);
    }

    protected function createUserWithRole(string $roleKey, ?Organization $organization = null): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('key', $roleKey)->firstOrFail();

        OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => $organization?->id,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
        ]);

        return $user;
    }
}
