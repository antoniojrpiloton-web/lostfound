<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreClaimRequest;
use App\Models\Claim;
use App\Models\ClaimEvidence;
use App\Models\ItemReport;
use App\Services\Claims\ClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicClaimController extends Controller
{
    public function __construct(public ClaimService $claimService) {}

    public function create(Request $request, string $referenceNo): View
    {
        Gate::authorize('create', Claim::class);

        $report = ItemReport::query()
            ->publiclyVisible()
            ->with(['category', 'barangay', 'location', 'custodyOrganization'])
            ->where('reference_no', $referenceNo)
            ->firstOrFail();

        if (! $this->claimService->isReportClaimable($report)) {
            abort(403, __('This item is not currently eligible for ownership claims.'));
        }

        $user = $request->user();

        $myLostReports = ItemReport::query()
            ->where('reporter_user_id', $user->id)
            ->where('type', ReportType::Lost)
            ->latest()
            ->limit(25)
            ->get();

        return view('public.claims.create', compact('report', 'myLostReports'));
    }

    public function store(StoreClaimRequest $request, string $referenceNo): RedirectResponse
    {
        $report = ItemReport::query()
            ->publiclyVisible()
            ->where('reference_no', $referenceNo)
            ->firstOrFail();

        $user = $request->user();
        $files = $request->file('evidence', []);

        try {
            $claim = $this->claimService->submit(
                claimant: $user,
                foundReport: $report,
                data: $request->validated(),
                evidenceFiles: is_array($files) ? $files : [],
                request: $request,
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['claim' => $exception->getMessage()]);
        }

        return redirect()
            ->route('claims.show_own', $claim)
            ->with('status', __('Claim :ref submitted for institutional verification.', ['ref' => $claim->reference_no]));
    }

    public function mine(Request $request): View
    {

        $user = $request->user();

        $claims = Claim::query()
            ->with(['itemReport.barangay', 'itemReport.custodyOrganization'])
            ->where('claimant_user_id', $user->id)
            ->latest('submitted_at')
            ->paginate(12);

        return view('public.claims.mine', compact('claims'));
    }

    public function show(Claim $claim): View
    {
        Gate::authorize('viewOwn', $claim);

        $claim->load([
            'itemReport.category',
            'itemReport.barangay',
            'itemReport.custodyOrganization',
            'lostReport',
            'evidence',
            'histories' => fn ($query) => $query->with('changer')->latest('created_at'),
        ]);

        return view('public.claims.show', compact('claim'));
    }

    public function downloadEvidence(Claim $claim, ClaimEvidence $evidence): StreamedResponse
    {
        Gate::authorize('viewOwn', $claim);

        if ($evidence->claim_id !== $claim->id || ! Storage::disk('local')->exists($evidence->private_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($evidence->private_path, $evidence->original_name, [
            'Content-Type' => $evidence->mime,
            'Content-Disposition' => 'inline; filename="'.$evidence->original_name.'"',
        ]);
    }

    public function respond(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('viewOwn', $claim);

        $validated = $request->validate([
            'details' => ['required', 'string', 'min:5', 'max:1500'],
            'evidence' => ['nullable', 'array', 'max:3'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $user = $request->user();
        $files = $request->file('evidence', []);

        try {
            $this->claimService->respondToInfoRequest($claim, $user, $validated['details'], is_array($files) ? $files : [], $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('claims.show_own', $claim)->withErrors(['claim' => $exception->getMessage()]);
        }

        return redirect()->route('claims.show_own', $claim)->with('status', __('Additional details submitted for review.'));
    }

    public function withdraw(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('viewOwn', $claim);

        $user = $request->user();

        try {
            $this->claimService->withdraw($claim, $user, null, $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('claims.show_own', $claim)->withErrors(['claim' => $exception->getMessage()]);
        }

        return redirect()->route('claims.show_own', $claim)->with('status', __('Your claim has been withdrawn.'));
    }
}
