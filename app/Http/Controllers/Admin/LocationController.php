<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Barangay;
use App\Models\Location;
use App\Models\Organization;
use App\Services\Audit\AuditService;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        public AuditService $auditService,
        public MasterDataCacheService $masterDataCache,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Location::class);

        $user = $request->user();
        $query = Location::query()->with(['barangay', 'organization'])->orderBy('name');

        if (! $user->isPlatformSuperAdmin()) {
            $query->where('organization_id', $user->organizationId());
        }

        $locations = $query->paginate(20);
        $barangays = Barangay::query()->where('is_active', true)->orderBy('name')->get();
        $organizations = $user->isPlatformSuperAdmin()
            ? Organization::query()->where('is_active', true)->orderBy('name')->get()
            : Organization::query()->whereKey($user->organizationId())->get();

        return view('admin.masterdata.locations', compact('locations', 'barangays', 'organizations'));
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {

        $user = $request->user();
        $validated = $request->validated();

        $organizationId = $user->isPlatformSuperAdmin()
            ? ($validated['organization_id'] ?? null)
            : $user->organizationId();

        $location = Location::query()->create([
            'barangay_id' => $validated['barangay_id'],
            'organization_id' => $organizationId,
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->masterDataCache->forgetLocations();

        $this->auditService->record(
            action: 'masterdata.location_created',
            auditable: $location,
            newValues: $location->only(['barangay_id', 'organization_id', 'name', 'type', 'is_active']),
            actor: $user,
            organizationId: $organizationId,
            request: $request,
        );

        return redirect()->route('admin.locations.index')->with('status', __('Location created.'));
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {

        $user = $request->user();
        $validated = $request->validated();
        $oldValues = $location->only(['barangay_id', 'organization_id', 'name', 'type', 'is_active']);

        $organizationId = $user->isPlatformSuperAdmin()
            ? ($validated['organization_id'] ?? null)
            : $user->organizationId();

        $location->update([
            'barangay_id' => $validated['barangay_id'],
            'organization_id' => $organizationId,
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $location->is_active,
        ]);

        $this->masterDataCache->forgetLocations();

        $this->auditService->record(
            action: 'masterdata.location_updated',
            auditable: $location,
            oldValues: $oldValues,
            newValues: $location->only(['barangay_id', 'organization_id', 'name', 'type', 'is_active']),
            actor: $user,
            organizationId: $organizationId,
            request: $request,
        );

        return redirect()->route('admin.locations.index')->with('status', __('Location updated.'));
    }
}
