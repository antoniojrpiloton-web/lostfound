<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustodyAction;
use App\Enums\CustodyStage;
use App\Enums\ImageValidationStatus;
use App\Enums\ItemStatus;
use App\Enums\MatchStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\ImageEmbedding;
use App\Models\ItemCustodyRecord;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\MasterData\MasterDataCacheService;
use App\Services\Matching\ItemMatchingService;
use App\Services\Matching\VisualSearchService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reports\FacebookPosterService;
use App\Services\Reports\OfficialPdfService;
use App\Services\Reports\ReportVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class AdminReportController extends Controller
{
    public function __construct(
        public ReportVerificationService $verificationService,
        public MasterDataCacheService $masterData,
        public VisualSearchService $visualSearch,
        public ItemMatchingService $matchingService,
        public AuditService $auditService,
        public NotificationDispatcher $notifications,
        public OfficialPdfService $officialPdf,
        public FacebookPosterService $facebookPoster,
    ) {}

    public function index(Request $request): View
    {

        $user = $request->user();

        $query = ItemReport::query()
            ->visibleTo($user)
            ->with(['category', 'barangay', 'handlingOrganization', 'custodyOrganization', 'reporter'])
            ->latest();

        if ($request->filled('type') && in_array($request->string('type')->toString(), ReportType::values(), true)) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('verification_status') && in_array($request->string('verification_status')->toString(), VerificationStatus::values(), true)) {
            $query->where('verification_status', $request->string('verification_status')->toString());
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim()->toString().'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('reference_no', 'like', $term)
                    ->orWhere('item_name', 'like', $term)
                    ->orWhere('search_text', 'like', $term);
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        $boothCustodyCount = ItemReport::query()
            ->visibleTo($user)
            ->where('type', ReportType::Found->value)
            ->whereIn('item_status', [ItemStatus::InCustody->value, ItemStatus::ClaimPending->value])
            ->where('custody_stage', CustodyStage::AtBooth->value)
            ->count();

        $flaggedPhotosCount = ItemImage::query()
            ->where('validation_status', ImageValidationStatus::Review->value)
            ->whereHas('itemReport', fn ($q) => $q->visibleTo($user))
            ->count();

        return view('admin.reports.index', compact('reports', 'boothCustodyCount', 'flaggedPhotosCount'));
    }

    public function returnAllBoothToStation(Request $request): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->hasPermission('custody.manage')) {
            abort(403);
        }

        $boothReports = ItemReport::query()
            ->visibleTo($actor)
            ->where('type', ReportType::Found->value)
            ->whereIn('item_status', [ItemStatus::InCustody->value, ItemStatus::ClaimPending->value])
            ->where('custody_stage', CustodyStage::AtBooth->value)
            ->get();

        if ($boothReports->isEmpty()) {
            return redirect()
                ->route('admin.reports.index')
                ->with('status', __('No unclaimed items are currently logged at beach Police Booths.'));
        }

        $now = now();
        foreach ($boothReports as $report) {
            $fromBooth = $report->booth_location;

            $report->forceFill([
                'custody_stage' => CustodyStage::AtStation,
                'station_transferred_at' => $now,
            ])->save();

            $custodyOrgId = $report->custody_organization_id ?? $report->handling_organization_id ?? $actor->organizationId();
            if ($custodyOrgId !== null) {
                ItemCustodyRecord::query()->create([
                    'item_report_id' => $report->id,
                    'organization_id' => $custodyOrgId,
                    'from_organization_id' => $custodyOrgId,
                    'action' => CustodyAction::Transferred,
                    'received_from' => $fromBooth ?: __('Police Beach Booth / Tent'),
                    'recorded_by' => $actor->id,
                    'recorded_at' => $now,
                    'remarks' => __('End-of-shift bulk return: unclaimed booth item brought back to Police Station for safekeeping.'),
                ]);
            }

            $this->auditService->record(
                action: 'custody.returned_to_station',
                auditable: $report,
                oldValues: ['custody_stage' => CustodyStage::AtBooth->value, 'booth_location' => $fromBooth],
                newValues: ['custody_stage' => CustodyStage::AtStation->value, 'station_transferred_at' => $now->toIso8601String(), 'bulk' => true],
                actor: $actor,
                organizationId: $custodyOrgId,
                request: $request,
            );
        }

        return redirect()
            ->route('admin.reports.index')
            ->with('status', __('End of shift complete: :count unclaimed booth item(s) logged as returned to the Police Station for safekeeping.', ['count' => $boothReports->count()]));
    }

    public function show(ItemReport $report): View
    {
        Gate::authorize('viewInstitutional', $report);

        $report->load([
            'category.parent',
            'barangay',
            'location',
            'handlingOrganization',
            'custodyOrganization',
            'reporter',
            'encoder',
            'verifier',
            'images',
            'claims' => fn ($query) => $query->with('claimant')->latest(),
            'disposal',
            'recovery',
            'custodyRecords' => fn ($query) => $query->with(['organization', 'recorder'])->latest('recorded_at'),
            'statusHistories' => fn ($query) => $query->with('changer')->latest('created_at'),
        ]);

        $matchComparisons = $this->buildMatchComparisons($report);
        $matches = ($report->type === ReportType::Found ? $report->matchesAsFound() : $report->matchesAsLost())
            ->whereIn('status', [MatchStatus::Suggested->value, MatchStatus::Possible->value])
            ->orderByDesc('score')
            ->limit(6)
            ->get();

        $indexedImageIds = ImageEmbedding::query()
            ->where('item_report_id', $report->id)
            ->pluck('item_image_id')
            ->all();

        $retentionDays = max(1, (int) $this->masterData->setting('disposal.retention_days', 180));
        $custodyAgeDays = $report->occurred_date !== null ? (int) $report->occurred_date->diffInDays(now()) : 0;
        $daysUntilDisposal = max(0, $retentionDays - $custodyAgeDays);
        $transferOrganizations = Organization::query()
            ->where('is_active', true)
            ->when($report->custody_organization_id, fn ($q) => $q->whereKeyNot($report->custody_organization_id))
            ->orderBy('name')
            ->get();

        $fbShareUrl = $report->is_public
            ? route('public.items.show', $report->reference_no)
            : route('public.items.index', ['q' => $report->reference_no]);
        $fbCaption = $this->facebookPoster->buildCaption($report, $fbShareUrl);

        return view('admin.reports.show', compact(
            'report',
            'matches',
            'matchComparisons',
            'indexedImageIds',
            'custodyAgeDays',
            'daysUntilDisposal',
            'transferOrganizations',
            'fbShareUrl',
            'fbCaption',
        ));
    }

    public function verify(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('verify', $report);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $actor = $request->user();

        try {
            $this->verificationService->verify($report, $actor, $validated['remarks'] ?? null, $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['report' => $exception->getMessage()]);
        }

        return redirect()->route('admin.reports.show', $report)->with('status', __('Report verified and published.'));
    }

    public function reject(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('verify', $report);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $actor = $request->user();

        try {
            $this->verificationService->reject($report, $actor, $validated['rejection_reason'], $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['report' => $exception->getMessage()]);
        }

        return redirect()->route('admin.reports.show', $report)->with('status', __('Report rejected.'));
    }

    public function requestRevision(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('verify', $report);

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $actor = $request->user();

        try {
            $this->verificationService->requestRevision($report, $actor, $validated['remarks'], $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['report' => $exception->getMessage()]);
        }

        return redirect()->route('admin.reports.show', $report)->with('status', __('Revision requested from reporter.'));
    }

    public function confirmTurnover(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('viewInstitutional', $report);

        $actor = $request->user();

        if (! $actor->hasPermission('custody.manage')) {
            abort(403);
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->verificationService->confirmPhysicalTurnover($report, $actor, $validated['remarks'] ?? null, $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['report' => $exception->getMessage()]);
        }

        return redirect()->route('admin.reports.show', $report)->with('status', __('Physical custody turnover confirmed.'));
    }

    public function notifyFinder(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('viewInstitutional', $report);

        $actor = $request->user();

        if (! $actor->hasPermission('custody.manage') && ! $actor->hasPermission('report.verify')) {
            abort(403);
        }

        $validated = $request->validate([
            'booth_location' => ['required', 'string', 'max:180'],
            'instructions' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $report->loadMissing('reporter');

        if ($report->reporter instanceof User) {
            $this->notifications->send(
                recipient: $report->reporter,
                eventKey: 'finder.booth_return',
                title: __('Police Booth Supervised Return Requested (:ref)', ['ref' => $report->reference_no]),
                body: __('A possible owner match was identified for ":item". Please bring the item to :booth. Instructions: :notes', [
                    'item' => $report->item_name,
                    'booth' => $validated['booth_location'],
                    'notes' => $validated['instructions'],
                ]),
                actionUrl: route('reports.show_own', $report),
                meta: ['report_id' => $report->id, 'booth_location' => $validated['booth_location']],
            );
        }

        $this->auditService->record(
            action: 'finder.notified_for_booth_return',
            auditable: $report,
            oldValues: null,
            newValues: [
                'booth_location' => $validated['booth_location'],
                'instructions' => $validated['instructions'],
                'finder_user_id' => $report->reporter_user_id,
            ],
            actor: $actor,
            organizationId: $report->handling_organization_id,
            request: $request,
        );

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', __('Finder has been notified to bring the item to :booth for supervised return.', ['booth' => $validated['booth_location']]));
    }

    public function returnToStation(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('viewInstitutional', $report);

        $actor = $request->user();

        if (! $actor->hasPermission('custody.manage')) {
            abort(403);
        }

        if ($report->type !== ReportType::Found || ! in_array($report->item_status, [ItemStatus::InCustody, ItemStatus::ClaimPending], true)) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['report' => __('Only found items currently in custody can be returned to the Police Station.')]);
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $previousStage = $report->effectiveCustodyStage()?->value;
        $fromBooth = $report->booth_location;

        $report->forceFill([
            'custody_stage' => CustodyStage::AtStation,
            'station_transferred_at' => now(),
        ])->save();

        $custodyOrgId = $report->custody_organization_id ?? $report->handling_organization_id ?? $actor->organizationId();
        if ($custodyOrgId !== null) {
            ItemCustodyRecord::query()->create([
                'item_report_id' => $report->id,
                'organization_id' => $custodyOrgId,
                'from_organization_id' => $custodyOrgId,
                'action' => CustodyAction::Transferred,
                'received_from' => $fromBooth ?: __('Police Beach Booth / Tent'),
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
                'remarks' => $validated['remarks'] ?: __('Unclaimed at end of booth shift; brought back to Police Station for safekeeping.'),
            ]);
        }

        $this->auditService->record(
            action: 'custody.returned_to_station',
            auditable: $report,
            oldValues: ['custody_stage' => $previousStage, 'booth_location' => $fromBooth],
            newValues: ['custody_stage' => CustodyStage::AtStation->value, 'station_transferred_at' => now()->toIso8601String()],
            actor: $actor,
            organizationId: $custodyOrgId,
            request: $request,
        );

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', __('Item logged as returned to the Police Station for safekeeping. It will stay at the station unless an owner claims it.'));
    }

    public function scheduleBoothHandoff(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('viewInstitutional', $report);

        $actor = $request->user();

        if (! $actor->hasPermission('custody.manage')) {
            abort(403);
        }

        if ($report->type !== ReportType::Found || ! in_array($report->item_status, [ItemStatus::InCustody, ItemStatus::ClaimPending], true)) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['report' => __('Only found items currently in custody can be scheduled for booth handoff.')]);
        }

        $validated = $request->validate([
            'booth_location' => ['required', 'string', 'max:180'],
            'owner_contacted' => ['accepted'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ], [
            'owner_contacted.accepted' => __('Items cannot leave the Police Station unless the owner has been contacted and verified.'),
        ]);

        $previousStage = $report->effectiveCustodyStage()?->value;

        $report->forceFill([
            'custody_stage' => CustodyStage::ScheduledForBooth,
            'booth_location' => trim($validated['booth_location']),
        ])->save();

        $custodyOrgId = $report->custody_organization_id ?? $report->handling_organization_id ?? $actor->organizationId();
        if ($custodyOrgId !== null) {
            ItemCustodyRecord::query()->create([
                'item_report_id' => $report->id,
                'organization_id' => $custodyOrgId,
                'from_organization_id' => $custodyOrgId,
                'action' => CustodyAction::Transferred,
                'received_from' => __('Police Station Safekeeping'),
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
                'remarks' => trim(__('Scheduled for next-day bring-back to :booth (owner contacted).', ['booth' => $validated['booth_location']]).' '.($validated['remarks'] ?? '')),
            ]);
        }

        $this->auditService->record(
            action: 'custody.scheduled_booth_handoff',
            auditable: $report,
            oldValues: ['custody_stage' => $previousStage],
            newValues: [
                'custody_stage' => CustodyStage::ScheduledForBooth->value,
                'booth_location' => $validated['booth_location'],
                'remarks' => $validated['remarks'] ?? null,
            ],
            actor: $actor,
            organizationId: $custodyOrgId,
            request: $request,
        );

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', __('Scheduled for next-day bring-back to :booth.', ['booth' => $validated['booth_location']]));
    }

    public function transferCustody(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('viewInstitutional', $report);

        $actor = $request->user();

        if (! $actor->hasPermission('custody.manage')) {
            abort(403);
        }

        if ($report->type !== ReportType::Found || ! in_array($report->item_status, [ItemStatus::InCustody, ItemStatus::ClaimPending], true)) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['report' => __('Only found items currently in custody can be transferred.')]);
        }

        $validated = $request->validate([
            'to_organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $targetOrgId = (int) $validated['to_organization_id'];
        $fromOrgId = $report->custody_organization_id ?? $report->handling_organization_id;

        if ($targetOrgId === $fromOrgId) {
            return redirect()->route('admin.reports.show', $report)->withErrors(['to_organization_id' => __('Select a different receiving institution.')]);
        }

        $targetOrg = Organization::query()->findOrFail($targetOrgId);
        $oldValues = [
            'custody_organization_id' => $report->custody_organization_id,
            'handling_organization_id' => $report->handling_organization_id,
        ];

        $report->forceFill([
            'custody_organization_id' => $targetOrg->id,
            'handling_organization_id' => $targetOrg->id,
        ])->save();

        ItemCustodyRecord::query()->create([
            'item_report_id' => $report->id,
            'organization_id' => $targetOrg->id,
            'from_organization_id' => $fromOrgId,
            'action' => CustodyAction::Transferred,
            'received_from' => $actor->name,
            'recorded_by' => $actor->id,
            'recorded_at' => now(),
            'remarks' => $validated['remarks'] ?? __('Inter-agency physical custody transfer.'),
        ]);

        $this->auditService->record(
            action: 'custody.transferred',
            auditable: $report,
            oldValues: $oldValues,
            newValues: [
                'custody_organization_id' => $targetOrg->id,
                'target_organization' => $targetOrg->name,
                'remarks' => $validated['remarks'] ?? null,
            ],
            actor: $actor,
            request: $request,
        );

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', __('Physical custody transferred to :org.', ['org' => $targetOrg->name]));
    }

    public function referenceSlip(ItemReport $report): Response
    {
        Gate::authorize('viewInstitutional', $report);
        $report->load(['category', 'location', 'handlingOrganization', 'custodyOrganization']);

        $lookupUrl = $report->is_public
            ? route('public.items.show', $report->reference_no)
            : route('admin.reports.show', $report);

        $pdfBytes = $this->officialPdf->buildReferenceSlip($report, $lookupUrl);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reference-slip-'.$report->reference_no.'.pdf"',
        ]);
    }

    public function fbPoster(Request $request, ItemReport $report): Response
    {
        Gate::authorize('viewInstitutional', $report);
        $report->load(['category', 'location', 'handlingOrganization', 'custodyOrganization', 'images']);

        $pngBytes = $this->facebookPoster->buildPosterPng($report, allowPrivateImage: true);
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pngBytes, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => $disposition.'; filename="fb-poster-'.$report->reference_no.'.png"',
        ]);
    }

    public function scanMatches(Request $request, ItemReport $report): JsonResponse|RedirectResponse
    {
        Gate::authorize('viewInstitutional', $report);

        $actor = $request->user();
        abort_unless($actor->hasPermission('match.view'), 403);

        $stored = $this->matchingService->matchForReport($report, includePending: true);
        $report->loadMissing(['images', 'claims']);
        $matchComparisons = $this->buildMatchComparisons($report);

        $retentionDays = max(1, (int) $this->masterData->setting('disposal.retention_days', 180));
        $custodyAgeDays = $report->occurred_date !== null ? (int) $report->occurred_date->diffInDays(now()) : 0;
        $daysUntilDisposal = max(0, $retentionDays - $custodyAgeDays);
        $isCompleted = in_array($report->item_status, [
            ItemStatus::Returned,
            ItemStatus::Resolved,
            ItemStatus::Disposed,
            ItemStatus::Closed,
        ], true);

        $message = __('Match scan complete. :count candidate match(es) found.', ['count' => count($matchComparisons)]);

        if ($request->expectsJson()) {
            $html = view('admin.reports.partials.match-comparisons', compact(
                'report',
                'matchComparisons',
                'custodyAgeDays',
                'daysUntilDisposal',
                'isCompleted',
            ))->render();

            return response()->json([
                'count' => count($matchComparisons),
                'scored' => $stored->count(),
                'message' => $message,
                'html' => $html,
            ]);
        }

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', $message);
    }

    protected function buildMatchComparisons(ItemReport $report): array
    {
        $matches = ($report->type === ReportType::Found ? $report->matchesAsFound() : $report->matchesAsLost())
            ->whereIn('status', [MatchStatus::Suggested->value, MatchStatus::Possible->value])
            ->with([
                'lostReport.category',
                'lostReport.barangay',
                'lostReport.images',
                'foundReport.category',
                'foundReport.barangay',
                'foundReport.images',
            ])
            ->orderByDesc('score')
            ->limit(6)
            ->get();

        $matchComparisons = [];
        $seenCounterpartIds = [];

        foreach ($matches as $match) {
            $counterpart = $report->type === ReportType::Found ? $match->lostReport : $match->foundReport;
            if (! $counterpart instanceof ItemReport) {
                continue;
            }

            $seenCounterpartIds[$counterpart->id] = true;
            $visualComparison = $this->visualSearch->compareReports($report, $counterpart);

            $matchComparisons[] = [
                'counterpart' => $counterpart,
                'overall_score' => (int) round((float) $match->score * 100),
                'factors' => is_array($match->factors) ? $match->factors : [],
                'visual' => $visualComparison,
                'source' => 'match',
            ];
        }

        foreach ($this->visualSearch->findVisualCounterparts($report, 6) as $visEntry) {

            $counterpart = $visEntry['report'];
            if (isset($seenCounterpartIds[$counterpart->id])) {
                continue;
            }

            $seenCounterpartIds[$counterpart->id] = true;
            $matchComparisons[] = [
                'counterpart' => $counterpart,
                'overall_score' => (int) round($visEntry['similarity'] * 100),
                'factors' => [],
                'visual' => $visEntry['visual'],
                'source' => 'visual_live',
            ];
        }

        usort($matchComparisons, fn (array $a, array $b): int => $b['overall_score'] <=> $a['overall_score']);

        return array_slice($matchComparisons, 0, 6);
    }
}
