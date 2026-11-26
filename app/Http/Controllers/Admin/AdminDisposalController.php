<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisposalStatus;
use App\Http\Controllers\Controller;
use App\Models\ItemDisposal;
use App\Models\ItemDisposalDocument;
use App\Services\Disposals\DisposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDisposalController extends Controller
{
    public function __construct(public DisposalService $disposalService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ItemDisposal::class);

        Cache::remember('blf:disposal_retention_autoscan', now()->addMinutes(10), function (): int {
            return $this->disposalService->flagEligibleItems();
        });

        $user = $request->user();
        $status = (string) $request->query('status', '');

        $query = ItemDisposal::query()
            ->with(['itemReport.category', 'organization', 'proposer', 'approver'])
            ->latest();

        if (! $user->isPlatformSuperAdmin()) {
            $query->where('organization_id', $user->organizationId());
        }

        if ($status !== '' && in_array($status, DisposalStatus::values(), true)) {
            $query->where('status', $status);
        }

        $disposals = $query->paginate(15)->withQueryString();

        return view('admin.disposals.index', [
            'disposals' => $disposals,
            'selectedStatus' => $status,
            'statuses' => DisposalStatus::cases(),
            'methods' => DisposalService::METHODS,
        ]);
    }

    public function show(ItemDisposal $disposal): View
    {
        Gate::authorize('view', $disposal);

        $disposal->load(['itemReport.category', 'itemReport.barangay', 'organization', 'proposer', 'approver', 'documents']);

        return view('admin.disposals.show', [
            'disposal' => $disposal,
            'methods' => DisposalService::METHODS,
        ]);
    }

    public function scan(Request $request): RedirectResponse
    {

        $user = $request->user();
        abort_unless($user->hasPermission('disposal.approve'), 403);

        $count = $this->disposalService->flagEligibleItems();

        return back()->with('status', __('Retention scan complete. :count item(s) flagged as eligible for disposal review.', ['count' => $count]));
    }

    public function propose(Request $request, ItemDisposal $disposal): RedirectResponse
    {
        Gate::authorize('propose', $disposal);

        $validated = $request->validate([
            'method' => ['required', 'string', Rule::in(array_keys(DisposalService::METHODS))],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $this->disposalService->propose($disposal, $user, $validated['method'], $validated['remarks'] ?? null);

        return redirect()
            ->route('admin.disposals.show', $disposal)
            ->with('status', __('Disposal proposal submitted for administrator approval.'));
    }

    public function approve(Request $request, ItemDisposal $disposal): RedirectResponse
    {
        Gate::authorize('approve', $disposal);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $this->disposalService->approve($disposal, $user, $validated['remarks'] ?? null);

        return redirect()
            ->route('admin.disposals.show', $disposal)
            ->with('status', __('Disposal approved. You may now record completion and upload supporting documents.'));
    }

    public function complete(Request $request, ItemDisposal $disposal): RedirectResponse
    {
        Gate::authorize('complete', $disposal);

        $validated = $request->validate([
            'disposed_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', Rule::in(array_keys(DisposalService::METHODS))],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $this->disposalService->complete(
            disposal: $disposal,
            actor: $user,
            disposedOn: $validated['disposed_on'],
            method: $validated['method'],
            remarks: $validated['remarks'] ?? null,
            documents: $request->file('documents', []),
        );

        return redirect()
            ->route('admin.disposals.show', $disposal)
            ->with('status', __('Disposal completed and custody record updated.'));
    }

    public function cancel(Request $request, ItemDisposal $disposal): RedirectResponse
    {
        Gate::authorize('cancel', $disposal);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $this->disposalService->cancel($disposal, $user, $validated['reason']);

        return redirect()
            ->route('admin.disposals.show', $disposal)
            ->with('status', __('Disposal cancelled and item returned to active custody.'));
    }

    public function downloadDocument(ItemDisposal $disposal, ItemDisposalDocument $document): StreamedResponse
    {
        Gate::authorize('view', $disposal);

        if ($document->item_disposal_id !== $disposal->id || ! Storage::disk('local')->exists($document->private_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($document->private_path, $document->original_name);
    }
}
