<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClaimEvidence;
use App\Services\Claims\ClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminClaimController extends Controller
{
    public function __construct(public ClaimService $claimService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyInstitutional', Claim::class);

        $user = $request->user();

        $query = Claim::query()
            ->with(['itemReport.barangay', 'itemReport.custodyOrganization', 'claimant'])
            ->latest('submitted_at');

        if (! $user->isPlatformSuperAdmin()) {
            $orgId = $user->organizationId();
            $query->whereHas('itemReport', function ($q) use ($orgId): void {
                $q->where('custody_organization_id', $orgId)
                    ->orWhere('handling_organization_id', $orgId);
            });
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), ClaimStatus::values(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        $claims = $query->paginate(15)->withQueryString();

        return view('admin.claims.index', compact('claims'));
    }

    public function show(Claim $claim): View
    {
        Gate::authorize('viewInstitutional', $claim);

        $claim->load([
            'itemReport.category',
            'itemReport.barangay',
            'itemReport.custodyOrganization',
            'lostReport',
            'claimant',
            'decider',
            'evidence',
            'histories' => fn ($query) => $query->with('changer')->latest('created_at'),
        ]);

        return view('admin.claims.show', compact('claim'));
    }

    public function downloadEvidence(Claim $claim, ClaimEvidence $evidence): StreamedResponse
    {
        Gate::authorize('viewInstitutional', $claim);

        if ($evidence->claim_id !== $claim->id || ! Storage::disk('local')->exists($evidence->private_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($evidence->private_path, $evidence->original_name, [
            'Content-Type' => $evidence->mime,
            'Content-Disposition' => 'inline; filename="'.$evidence->original_name.'"',
        ]);
    }

    public function requestInfo(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('requestInfo', $claim);

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $actor = $request->user();

        try {
            $this->claimService->requestAdditionalInfo($claim, $actor, $validated['remarks'], $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.claims.show', $claim)->withErrors(['claim' => $exception->getMessage()]);
        }

        return redirect()->route('admin.claims.show', $claim)->with('status', __('Additional information requested from claimant.'));
    }

    public function approve(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('decide', $claim);

        $validated = $request->validate([
            'turnover_scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
            'pickup_mode' => ['nullable', 'string', 'in:station_pickup,next_day_booth'],
            'pickup_location' => ['nullable', 'string', 'max:180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $actor = $request->user();

        try {
            $this->claimService->approve(
                claim: $claim,
                actor: $actor,
                turnoverScheduledAt: $validated['turnover_scheduled_at'] ?? null,
                remarks: $validated['remarks'] ?? null,
                request: $request,
                pickupMode: $validated['pickup_mode'] ?? 'station_pickup',
                pickupLocation: $validated['pickup_location'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.claims.show', $claim)->withErrors(['claim' => $exception->getMessage()]);
        }

        return redirect()->route('admin.claims.show', $claim)->with('status', __('Claim approved and ready for physical turnover confirmation.'));
    }

    public function reject(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('decide', $claim);

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $actor = $request->user();

        try {
            $this->claimService->reject($claim, $actor, $validated['remarks'], $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.claims.show', $claim)->withErrors(['claim' => $exception->getMessage()]);
        }

        return redirect()->route('admin.claims.show', $claim)->with('status', __('Claim rejected.'));
    }
}
