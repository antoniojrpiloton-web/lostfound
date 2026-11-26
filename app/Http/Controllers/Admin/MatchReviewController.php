<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Enums\MatchStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\ItemMatch;
use App\Models\ItemReport;
use App\Services\Audit\AuditService;
use App\Services\Matching\ItemMatchingService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MatchReviewController extends Controller
{
    public function __construct(
        public ItemMatchingService $matchingService,
        public AuditService $auditService,
        public NotificationDispatcher $notifications,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ItemMatch::class);

        $user = $request->user();

        $query = ItemMatch::query()
            ->with([
                'lostReport.category',
                'lostReport.barangay',
                'lostReport.handlingOrganization',
                'lostReport.images',
                'foundReport.category',
                'foundReport.barangay',
                'foundReport.custodyOrganization',
                'foundReport.images',
                'reviewer',
            ])
            ->orderByDesc('score');

        if (! $user->isPlatformSuperAdmin()) {
            $orgId = $user->organizationId();
            $query->where(function ($builder) use ($orgId): void {
                $builder->whereHas('lostReport', fn ($q) => $q->where('handling_organization_id', $orgId))
                    ->orWhereHas('foundReport', fn ($q) => $q->where('handling_organization_id', $orgId)->orWhere('custody_organization_id', $orgId));
            });
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), MatchStatus::values(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        $matches = $query->paginate(15)->withQueryString();

        $lostCandidates = ItemReport::query()
            ->with('images')
            ->where('type', ReportType::Lost)
            ->where('verification_status', VerificationStatus::Verified)
            ->where('item_status', ItemStatus::Open->value)
            ->latest('updated_at')
            ->limit(25)
            ->get();

        $foundByCategory = ItemReport::query()
            ->with('images')
            ->where('type', ReportType::Found)
            ->where('verification_status', VerificationStatus::Verified)
            ->whereIn('item_status', [ItemStatus::InCustody->value, ItemStatus::ClaimPending->value])
            ->whereIn('category_id', $lostCandidates->pluck('category_id')->unique()->filter()->all())
            ->latest('updated_at')
            ->limit(60)
            ->get()
            ->groupBy('category_id');

        $scanPreviewPairs = [];
        foreach ($lostCandidates as $lostItem) {
            $lostImg = $lostItem->images->sortByDesc('is_primary')->first();
            $foundPool = $foundByCategory->get($lostItem->category_id);
            if (! $lostImg || ! $foundPool || $foundPool->isEmpty()) {
                continue;
            }

            $foundItem = $foundPool->first(fn (ItemReport $f): bool => $f->images->isNotEmpty());
            if (! $foundItem) {
                continue;
            }

            $foundImg = $foundItem->images->sortByDesc('is_primary')->first();
            if (! $foundImg) {
                continue;
            }

            $scanPreviewPairs[] = [
                'lost' => [
                    'name' => $lostItem->item_name,
                    'ref' => $lostItem->reference_no,
                    'url' => route('admin.images.preview', $lostImg),
                ],
                'found' => [
                    'name' => $foundItem->item_name,
                    'ref' => $foundItem->reference_no,
                    'url' => route('admin.images.preview', $foundImg),
                ],
            ];

            if (count($scanPreviewPairs) >= 12) {
                break;
            }
        }

        return view('admin.matches.index', compact('matches', 'scanPreviewPairs'));
    }

    public function decide(Request $request, ItemMatch $match): RedirectResponse
    {
        Gate::authorize('review', $match);

        $validated = $request->validate([
            'status' => ['required', Rule::in([MatchStatus::Possible->value, MatchStatus::Dismissed->value])],
            'review_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $match->status->value;
        $newStatus = MatchStatus::from($validated['status']);

        $match->forceFill([
            'status' => $newStatus,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_remarks' => $validated['review_remarks'] ?? null,
        ])->save();

        $this->auditService->record(
            action: 'match.reviewed',
            auditable: $match,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus->value, 'score' => $match->score, 'review_remarks' => $validated['review_remarks'] ?? null],
            actor: $request->user(),
            request: $request,
        );

        if ($newStatus === MatchStatus::Possible) {
            $match->loadMissing(['lostReport.reporter', 'foundReport']);
            $this->notifications->send(
                recipient: $match->lostReport?->reporter,
                eventKey: 'match.identified',
                title: __('Possible Match Found (:ref)', ['ref' => $match->lostReport?->reference_no]),
                body: __('A possible match was identified for your lost item ":item". Review the found item and submit an ownership claim if it is yours.', ['item' => $match->lostReport?->item_name]),
                actionUrl: $match->foundReport ? route('public.items.show', $match->foundReport->reference_no) : null,
                meta: ['match_id' => $match->id],
            );
        }

        return redirect()
            ->route('admin.matches.index')
            ->with('status', __('Match review saved. Ownership still requires verified claim approval.'));
    }

    public function scan(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('viewAny', ItemMatch::class);

        @set_time_limit(120);

        $reports = ItemReport::query()
            ->with(['category', 'barangay', 'location'])
            ->where('type', ReportType::Lost)
            ->where('verification_status', VerificationStatus::Verified)
            ->where('item_status', ItemStatus::Open->value)
            ->latest('updated_at')
            ->limit(60)
            ->get();

        $count = 0;
        foreach ($reports as $report) {
            $count += $this->matchingService->matchForReport($report)->count();
        }

        $message = __('Matching scan completed across :reports open lost reports (:matches candidate matches evaluated).', [
            'reports' => $reports->count(),
            'matches' => $count,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'reports_scanned' => $reports->count(),
                'matches_evaluated' => $count,
            ]);
        }

        return redirect()
            ->route('admin.matches.index')
            ->with('status', $message);
    }
}
