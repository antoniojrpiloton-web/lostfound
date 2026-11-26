<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\MasterData\MasterDataCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_can_manage_categories_and_invalidates_cache(): void
    {
        $superAdmin = $this->createUserWithRole('platform_super_admin');
        $cache = app(MasterDataCacheService::class);

        $initialCount = $cache->categories()->count();
        $this->assertTrue(Cache::has(MasterDataCacheService::KEY_CATEGORIES));

        $this->actingAs($superAdmin)
            ->post(route('admin.categories.store'), [
                'slug' => 'diving-gear',
                'label_en' => 'Diving Gear',
                'label_fil' => 'Gamit sa Diving',
                'sort_order' => 60,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertFalse(Cache::has(MasterDataCacheService::KEY_CATEGORIES));
        $this->assertSame($initialCount + 1, $cache->categories()->count());

        $category = Category::query()->where('slug', 'diving-gear')->firstOrFail();

        $this->actingAs($superAdmin)
            ->put(route('admin.categories.update', $category), [
                'slug' => 'diving-gear',
                'label_en' => 'Scuba & Diving Gear',
                'label_fil' => 'Gamit sa Scuba at Diving',
                'sort_order' => 61,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame('Scuba & Diving Gear', $category->fresh()->label('en'));
        $this->assertTrue(AuditLog::query()->where('action', 'masterdata.category_updated')->where('auditable_id', $category->id)->exists());
    }

    public function test_super_admin_can_manage_barangays_and_invalidates_cache(): void
    {
        $superAdmin = $this->createUserWithRole('platform_super_admin');
        $cache = app(MasterDataCacheService::class);

        $cache->barangays();
        $this->assertTrue(Cache::has(MasterDataCacheService::KEY_BARANGAYS));

        $this->actingAs($superAdmin)
            ->post(route('admin.barangays.store'), [
                'code' => 'BRGY-TEST',
                'name' => 'Caticlan Annex',
                'municipality' => 'Malay',
                'province' => 'Aklan',
            ])
            ->assertRedirect(route('admin.barangays.index'));

        $this->assertFalse(Cache::has(MasterDataCacheService::KEY_BARANGAYS));
        $this->assertSame(4, $cache->barangays()->count());
    }

    public function test_institution_admin_can_manage_only_own_org_locations_and_not_global_master_data(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $yapak = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();
        $balabagBrgy = Barangay::query()->where('code', 'BRGY-BALABAG')->firstOrFail();
        $yapakBrgy = Barangay::query()->where('code', 'BRGY-YAPAK')->firstOrFail();

        $balabagAdmin = $this->createUserWithRole('institution_admin', $balabag);

        $this->actingAs($balabagAdmin)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($balabagAdmin)->get(route('admin.barangays.index'))->assertForbidden();
        $this->actingAs($balabagAdmin)->get(route('admin.settings.index'))->assertForbidden();

        $this->actingAs($balabagAdmin)->get(route('admin.locations.index'))->assertOk();

        $this->actingAs($balabagAdmin)
            ->post(route('admin.locations.store'), [
                'barangay_id' => $balabagBrgy->id,
                'organization_id' => $yapak->id,
                'name' => 'Balabag Pier Desk',
                'type' => 'landmark',
            ])
            ->assertRedirect(route('admin.locations.index'));

        $created = Location::query()->where('name', 'Balabag Pier Desk')->firstOrFail();
        $this->assertSame($balabag->id, $created->organization_id);

        $yapakLocation = Location::query()->where('organization_id', $yapak->id)->firstOrFail();

        $this->actingAs($balabagAdmin)
            ->put(route('admin.locations.update', $yapakLocation), [
                'barangay_id' => $yapakBrgy->id,
                'name' => 'Unauthorized Rename',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_update_system_settings_and_refresh_bypasses_stale_cache(): void
    {
        $superAdmin = $this->createUserWithRole('platform_super_admin');
        $cache = app(MasterDataCacheService::class);

        $this->assertSame(180, $cache->setting('disposal.retention_days'));

        $setting = SystemSetting::query()->where('key', 'disposal.retention_days')->firstOrFail();

        $this->actingAs($superAdmin)
            ->put(route('admin.settings.update', $setting), [
                'value' => '210',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame(210, $cache->setting('disposal.retention_days'));

        DB::table('system_settings')
            ->where('key', 'disposal.retention_days')
            ->update(['value' => json_encode(['value' => 240])]);

        $this->assertSame(210, $cache->setting('disposal.retention_days'));
        $this->assertSame(240, $cache->setting('disposal.retention_days', null, refresh: true));
    }

    public function test_institution_staff_cannot_access_master_data_management(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $staff = $this->createUserWithRole('institution_staff', $balabag);

        $this->actingAs($staff)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.barangays.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.locations.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_master_data_cache_works_with_database_store_when_serializable_classes_disabled(): void
    {
        config()->set('cache.default', 'database');
        config()->set('cache.serializable_classes', false);
        Cache::setDefaultDriver('database');

        $cache = app(MasterDataCacheService::class);
        $cache->forgetCategories();
        $cache->forgetBarangays();
        $cache->forgetLocations();
        $cache->forgetOrganizations();

        $this->assertSame(19, $cache->categories()->count());
        $this->assertSame(19, $cache->categories()->count());
        $this->assertSame(3, $cache->barangays()->count());
        $this->assertSame(3, $cache->barangays()->count());
        $this->assertSame(13, $cache->locations()->count());
        $this->assertSame(13, $cache->locations()->count());
        $this->assertSame(4, $cache->organizations()->count());
        $this->assertSame(4, $cache->organizations()->count());
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
