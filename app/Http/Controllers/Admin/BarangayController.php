<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBarangayRequest;
use App\Http\Requests\Admin\UpdateBarangayRequest;
use App\Models\Barangay;
use App\Services\Audit\AuditService;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BarangayController extends Controller
{
    public function __construct(
        public AuditService $auditService,
        public MasterDataCacheService $masterDataCache,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Barangay::class);

        $barangays = Barangay::query()
            ->withCount('locations')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.masterdata.barangays', compact('barangays'));
    }

    public function store(StoreBarangayRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $barangay = Barangay::query()->create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'municipality' => $validated['municipality'],
            'province' => $validated['province'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->masterDataCache->forgetBarangays();

        $this->auditService->record(
            action: 'masterdata.barangay_created',
            auditable: $barangay,
            newValues: $barangay->only(['code', 'name', 'municipality', 'province', 'is_active']),
            actor: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.barangays.index')->with('status', __('Barangay created.'));
    }

    public function update(UpdateBarangayRequest $request, Barangay $barangay): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = $barangay->only(['code', 'name', 'municipality', 'province', 'is_active']);

        $barangay->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'municipality' => $validated['municipality'],
            'province' => $validated['province'],
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $barangay->is_active,
        ]);

        $this->masterDataCache->forgetBarangays();

        $this->auditService->record(
            action: 'masterdata.barangay_updated',
            auditable: $barangay,
            oldValues: $oldValues,
            newValues: $barangay->only(['code', 'name', 'municipality', 'province', 'is_active']),
            actor: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.barangays.index')->with('status', __('Barangay updated.'));
    }
}
