<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Barangay;
use App\Models\Organization;
use App\Services\Audit\AuditService;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct(
        public AuditService $auditService,
        public MasterDataCacheService $masterDataCache,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Organization::class);

        $organizations = Organization::query()
            ->with('barangays')
            ->orderBy('name')
            ->paginate(15);

        $barangays = Barangay::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.organizations.index', compact('organizations', 'barangays'));
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $organization = Organization::query()->create([
            'type' => $validated['type'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['barangay_ids'])) {
            $organization->barangays()->sync($validated['barangay_ids']);
        }

        $this->masterDataCache->forgetOrganizations();

        $this->auditService->record(
            action: 'organization.created',
            auditable: $organization,
            newValues: $organization->only(['type', 'code', 'name', 'is_active']),
            actor: $request->user(),
            organizationId: $organization->id,
            request: $request,
        );

        return redirect()->route('admin.organizations.index')->with('status', __('Organization created.'));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = $organization->only(['type', 'code', 'name', 'is_active']);

        $organization->update([
            'type' => $validated['type'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? $organization->is_active,
        ]);

        if (array_key_exists('barangay_ids', $validated)) {
            $organization->barangays()->sync($validated['barangay_ids'] ?? []);
        }

        $this->masterDataCache->forgetOrganizations();

        $this->auditService->record(
            action: 'organization.updated',
            auditable: $organization,
            oldValues: $oldValues,
            newValues: $organization->only(['type', 'code', 'name', 'is_active']),
            actor: $request->user(),
            organizationId: $organization->id,
            request: $request,
        );

        return redirect()->route('admin.organizations.index')->with('status', __('Organization updated.'));
    }
}
