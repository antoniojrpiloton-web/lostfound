<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ItemRecovery;
use App\Services\Claims\RecoveryService;
use App\Services\Reports\OfficialPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class AdminRecoveryController extends Controller
{
    public function __construct(
        public RecoveryService $recoveryService,
        public OfficialPdfService $pdfService,
    ) {}

    public function index(Request $request): View
    {

        $user = $request->user();

        $query = ItemRecovery::query()
            ->with(['itemReport', 'claim', 'organization', 'claimant', 'processor', 'confirmer'])
            ->latest('turnover_at');

        if (! $user->isPlatformSuperAdmin()) {
            $query->where('organization_id', $user->organizationId());
        }

        $recoveries = $query->paginate(15);

        return view('admin.recoveries.index', compact('recoveries'));
    }

    public function store(Request $request, Claim $claim): RedirectResponse
    {
        Gate::authorize('viewInstitutional', $claim);

        $actor = $request->user();

        if (! $actor->hasPermission('recovery.record')) {
            abort(403);
        }

        $validated = $request->validate([
            'received_by_name' => ['required', 'string', 'max:150'],
            'id_type' => ['required', 'string', 'max:60'],
            'id_last4' => ['required', 'string', 'max:10'],
            'turnover_at' => ['nullable', 'date'],
            'verification_outcome' => ['required', 'in:verified,partial'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'confirm_now' => ['sometimes', 'boolean'],
        ]);

        $confirmImmediately = (bool) ($validated['confirm_now'] ?? false) && $actor->hasPermission('recovery.confirm');

        try {
            $this->recoveryService->recordTurnover($claim, $actor, $validated, $confirmImmediately, $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.claims.show', $claim)->withErrors(['recovery' => $exception->getMessage()]);
        }

        $message = $confirmImmediately
            ? __('Physical item turnover recorded and confirmed.')
            : __('Turnover draft recorded. An Institution Administrator must confirm recovery.');

        return redirect()->route('admin.claims.show', $claim)->with('status', $message);
    }

    public function confirm(Request $request, ItemRecovery $recovery): RedirectResponse
    {

        $actor = $request->user();

        if (! $actor->hasPermission('recovery.confirm')) {
            abort(403);
        }

        if (! $actor->isPlatformSuperAdmin() && ! $actor->belongsToOrganization($recovery->organization_id)) {
            abort(403);
        }

        try {
            $this->recoveryService->confirm($recovery, $actor, $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.recoveries.index')->withErrors(['recovery' => $exception->getMessage()]);
        }

        return redirect()->route('admin.recoveries.index')->with('status', __('Item recovery confirmed and item marked as returned.'));
    }

    public function receiptPdf(Request $request, ItemRecovery $recovery): Response
    {

        $actor = $request->user();

        if (! $actor->hasPermission('recovery.record')) {
            abort(403);
        }

        if (! $actor->isPlatformSuperAdmin() && ! $actor->belongsToOrganization($recovery->organization_id)) {
            abort(403);
        }

        $recovery->load(['itemReport.category', 'itemReport.barangay', 'itemReport.location', 'claim', 'organization', 'claimant', 'processor', 'confirmer']);

        $bytes = $this->pdfService->buildTurnoverReceipt($recovery);
        $filename = 'turnover-receipt-'.($recovery->itemReport?->reference_no ?? $recovery->id).'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
