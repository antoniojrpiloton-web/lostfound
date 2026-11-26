<?php

namespace App\Services\MasterData;

use App\Models\Barangay;
use App\Models\Category;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class MasterDataCacheService
{
    public const KEY_CATEGORIES = 'blf.master.categories.v2';

    public const KEY_BARANGAYS = 'blf.master.barangays.v2';

    public const KEY_LOCATIONS = 'blf.master.locations.v2';

    public const KEY_ORGANIZATIONS = 'blf.master.organizations.v2';

    public const KEY_SETTINGS = 'blf.master.settings.v2';

    private const TTL_SECONDS = 3600;

    public function categories(bool $refresh = false): Collection
    {
        if ($refresh) {
            $this->forgetCategories();
        }

        $rows = Cache::remember(self::KEY_CATEGORIES, self::TTL_SECONDS, function (): array {
            return Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('slug')
                ->get()
                ->map(fn (Category $category): array => $category->getAttributes())
                ->all();
        });

        $collection = Category::hydrate($rows);
        $byId = $collection->keyBy('id');

        foreach ($collection as $category) {
            $category->setRelation('parent', $category->parent_id !== null ? $byId->get($category->parent_id) : null);
        }

        return $collection;
    }

    public function barangays(bool $refresh = false): Collection
    {
        if ($refresh) {
            $this->forgetBarangays();
        }

        $rows = Cache::remember(self::KEY_BARANGAYS, self::TTL_SECONDS, function (): array {
            return Barangay::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Barangay $barangay): array => $barangay->getAttributes())
                ->all();
        });

        return Barangay::hydrate($rows);
    }

    public function locations(?int $barangayId = null, bool $refresh = false): Collection
    {
        if ($refresh) {
            $this->forgetLocations();
        }

        $rows = Cache::remember(self::KEY_LOCATIONS, self::TTL_SECONDS, function (): array {
            return Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Location $location): array => $location->getAttributes())
                ->all();
        });

        $collection = Location::hydrate($rows);
        $barangaysById = $this->barangays($refresh)->keyBy('id');
        $organizationsById = $this->organizations($refresh)->keyBy('id');

        foreach ($collection as $location) {
            $location->setRelation('barangay', $barangaysById->get($location->barangay_id));
            $location->setRelation('organization', $location->organization_id !== null ? $organizationsById->get($location->organization_id) : null);
        }

        if ($barangayId !== null) {
            return $collection->where('barangay_id', $barangayId)->values();
        }

        return $collection;
    }

    public function organizations(bool $refresh = false): Collection
    {
        if ($refresh) {
            $this->forgetOrganizations();
        }

        $rows = Cache::remember(self::KEY_ORGANIZATIONS, self::TTL_SECONDS, function (): array {
            return Organization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Organization $organization): array => $organization->getAttributes())
                ->all();
        });

        return Organization::hydrate($rows);
    }

    public function settings(bool $refresh = false): array
    {
        if ($refresh) {
            $this->forgetSettings();
        }

        return Cache::remember(self::KEY_SETTINGS, self::TTL_SECONDS, function (): array {
            return SystemSetting::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (SystemSetting $setting): array => [$setting->key => $setting->value])
                ->all();
        });
    }

    public function setting(string $key, mixed $default = null, bool $refresh = false): mixed
    {
        $settings = $this->settings($refresh);

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        $payload = $settings[$key];

        if (is_array($payload) && array_key_exists('value', $payload) && count($payload) === 1) {
            return $payload['value'];
        }

        return $payload;
    }

    public function forgetCategories(): void
    {
        Cache::forget(self::KEY_CATEGORIES);
    }

    public function forgetBarangays(): void
    {
        Cache::forget(self::KEY_BARANGAYS);
    }

    public function forgetLocations(): void
    {
        Cache::forget(self::KEY_LOCATIONS);
    }

    public function forgetOrganizations(): void
    {
        Cache::forget(self::KEY_ORGANIZATIONS);
    }

    public function forgetSettings(): void
    {
        Cache::forget(self::KEY_SETTINGS);
    }
}
