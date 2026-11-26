<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Enums\DisposalStatus;
use App\Enums\MatchStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ItemDisposal;
use App\Models\ItemMatch;
use App\Models\ItemRecovery;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Services\Disposals\DisposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class AdminDataTableController extends Controller
{
    public function reports(Request $request): JsonResponse
    {

        $user = $request->user();

        $baseQuery = ItemReport::query()->visibleTo($user);
        $recordsTotal = (clone $baseQuery)->count();

        $query = (clone $baseQuery)->with(['category', 'location']);

        if ($request->filled('type') && in_array($request->string('type')->toString(), ReportType::values(), true)) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('verification_status') && in_array($request->string('verification_status')->toString(), VerificationStatus::values(), true)) {
            $query->where('verification_status', $request->string('verification_status')->toString());
        }

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('reference_no', 'like', $like)
                    ->orWhere('item_name', 'like', $like)
                    ->orWhere('search_text', 'like', $like);
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumns = [
            1 => 'item_name',
            2 => 'type',
            3 => 'barangay_id',
            4 => 'verification_status',
            5 => 'item_status',
            6 => 'reference_no',
            7 => 'occurred_date',
        ];
        [$orderCol, $orderDir] = $this->resolveOrder($request, $orderColumns, 'id', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (ItemReport $report): array {
            $actions = '<a href="'.e(route('admin.reports.show', $report)).'" class="btn btn-sm btn-outline-primary" title="'.e(__('Review')).'"><i class="bi bi-eye me-1"></i>'.e(__('Review')).'</a>';

            return [
                $actions,
                '<div class="fw-medium text-navy">'.e($report->item_name).'</div><div class="small text-secondary">'.e((string) $report->category?->label()).'</div>',
                $this->badge($report->type),
                e((string) ($report->location?->name ?: ($report->location_details ?: '—'))),
                $this->badge($report->verification_status, $report->verification_status->label()),
                $this->badge($report->item_status),
                '<code class="small text-primary">'.e($report->reference_no).'</code>',
                '<span class="small text-secondary">'.e((string) $report->occurred_date?->format('M d, Y')).'</span>',
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function matches(Request $request): JsonResponse
    {

        $user = $request->user();

        $baseQuery = ItemMatch::query();
        if (! $user->isPlatformSuperAdmin()) {
            $orgId = $user->organizationId();
            $baseQuery->where(function ($builder) use ($orgId): void {
                $builder->whereHas('lostReport', fn ($q) => $q->where('handling_organization_id', $orgId))
                    ->orWhereHas('foundReport', fn ($q) => $q->where('handling_organization_id', $orgId)->orWhere('custody_organization_id', $orgId));
            });
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with([
            'lostReport.location',
            'lostReport.category',
            'lostReport.images',
            'foundReport.location',
            'foundReport.category',
            'foundReport.images',
        ]);

        if ($request->filled('status') && in_array($request->string('status')->toString(), MatchStatus::values(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->whereHas('lostReport', fn ($q) => $q->where('item_name', 'like', $like)->orWhere('reference_no', 'like', $like))
                    ->orWhereHas('foundReport', fn ($q) => $q->where('item_name', 'like', $like)->orWhere('reference_no', 'like', $like));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [1 => 'score', 5 => 'status'];
        [$orderCol, $orderDir] = $this->resolveOrder($request, $orderColumns, 'score', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();
        $csrf = csrf_token();

        $data = $rows->map(function (ItemMatch $match) use ($user, $csrf): array {
            $pct = (int) round(((float) $match->score) * 100);
            $scoreTone = $pct >= 75 ? 'blf-pill-success' : 'blf-pill-warning';
            $lostImg = $match->lostReport?->images?->sortByDesc('is_primary')->first();
            $foundImg = $match->foundReport?->images?->sortByDesc('is_primary')->first();

            $lostLoc = $match->lostReport?->location?->name ?: ($match->lostReport?->location_details ?: 'Boracay Island');
            $foundLoc = $match->foundReport?->location?->name ?: ($match->foundReport?->location_details ?: 'Boracay Island');

            $comparePayload = e((string) json_encode([
                'score' => $pct,
                'status' => $match->status->value,
                'factors' => $match->factors ?? [],
                'lost' => $match->lostReport ? [
                    'name' => $match->lostReport->item_name,
                    'ref' => $match->lostReport->reference_no,
                    'category' => $match->lostReport->category?->label() ?? '—',
                    'meta' => implode(' · ', array_filter([$match->lostReport->brand, $match->lostReport->model, $match->lostReport->color])) ?: '—',
                    'location' => $lostLoc,
                    'date' => $match->lostReport->occurred_date?->format('M d, Y') ?? '—',
                    'description' => $match->lostReport->description,
                    'photo' => $lostImg ? route('admin.images.preview', $lostImg) : null,
                    'url' => route('admin.reports.show', $match->lostReport),
                ] : null,
                'found' => $match->foundReport ? [
                    'name' => $match->foundReport->item_name,
                    'ref' => $match->foundReport->reference_no,
                    'category' => $match->foundReport->category?->label() ?? '—',
                    'meta' => implode(' · ', array_filter([$match->foundReport->brand, $match->foundReport->model, $match->foundReport->color])) ?: '—',
                    'location' => $foundLoc,
                    'date' => $match->foundReport->occurred_date?->format('M d, Y') ?? '—',
                    'description' => $match->foundReport->description,
                    'photo' => $foundImg ? route('admin.images.preview', $foundImg) : null,
                    'url' => route('admin.reports.show', $match->foundReport),
                ] : null,
            ]));

            $compareBtn = '<button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#matchCompareModal" data-compare="'.$comparePayload.'">'
                .'<i class="bi bi-eye me-1"></i>'.e(__('Compare')).'</button>';

            $actions = $compareBtn;
            if ($user->can('review', $match)) {
                $url = e(route('admin.matches.decide', $match));
                $actions .= '<form method="POST" action="'.$url.'" class="d-inline me-1">'
                    .'<input type="hidden" name="_token" value="'.$csrf.'">'
                    .'<input type="hidden" name="_method" value="PATCH">'
                    .'<input type="hidden" name="status" value="possible">'
                    .'<button type="submit" class="btn btn-sm btn-outline-success">'.e(__('Possible')).'</button></form>'
                    .'<form method="POST" action="'.$url.'" class="d-inline" data-confirm="'.e(__('Dismiss this suggested match?')).'" data-confirm-title="'.e(__('Dismiss Match')).'" data-confirm-button="'.e(__('Dismiss')).'">'
                    .'<input type="hidden" name="_token" value="'.$csrf.'">'
                    .'<input type="hidden" name="_method" value="PATCH">'
                    .'<input type="hidden" name="status" value="dismissed">'
                    .'<button type="submit" class="btn btn-sm btn-outline-danger">'.e(__('Dismiss')).'</button></form>';
            }

            $lostHtml = '';
            if ($match->lostReport) {
                $thumb = $lostImg ? '<a href="#matchPhotoModal" data-bs-toggle="modal" data-preview-src="'.e(route('admin.images.preview', $lostImg)).'" data-preview-title="'.e($match->lostReport->item_name.' (Lost)').'" class="flex-shrink-0"><img src="'.e(route('admin.images.preview', $lostImg)).'" alt="" class="rounded border" style="width:48px;height:48px;object-fit:cover;"></a>' : '';
                $lostHtml = '<div class="d-flex align-items-center gap-2">'.$thumb.'<div class="min-w-0"><a href="'.e(route('admin.reports.show', $match->lostReport)).'" class="fw-semibold text-navy text-decoration-none">'.e($match->lostReport->item_name).'</a><div><code class="small text-primary">'.e($match->lostReport->reference_no).'</code></div><div class="small text-secondary">'.e($lostLoc).' · '.e((string) $match->lostReport->occurred_date?->format('M d, Y')).'</div></div></div>';
            }

            $foundHtml = '';
            if ($match->foundReport) {
                $thumb = $foundImg ? '<a href="#matchPhotoModal" data-bs-toggle="modal" data-preview-src="'.e(route('admin.images.preview', $foundImg)).'" data-preview-title="'.e($match->foundReport->item_name.' (Found)').'" class="flex-shrink-0"><img src="'.e(route('admin.images.preview', $foundImg)).'" alt="" class="rounded border" style="width:48px;height:48px;object-fit:cover;"></a>' : '';
                $foundHtml = '<div class="d-flex align-items-center gap-2">'.$thumb.'<div class="min-w-0"><a href="'.e(route('admin.reports.show', $match->foundReport)).'" class="fw-semibold text-navy text-decoration-none">'.e($match->foundReport->item_name).'</a><div><code class="small text-primary">'.e($match->foundReport->reference_no).'</code></div><div class="small text-secondary">'.e($foundLoc).' · '.e((string) $match->foundReport->occurred_date?->format('M d, Y')).'</div></div></div>';
            }

            $factorsHtml = '<div class="d-flex flex-wrap gap-1">';
            foreach (($match->factors ?? []) as $factor => $val) {
                $factorsHtml .= '<span class="badge text-bg-light border text-secondary fw-normal" style="font-size:0.7rem;">'.e((string) $factor).': '.((int) round((float) $val * 100)).'%</span>';
            }
            $factorsHtml .= '</div>';

            return [
                $actions,
                '<span class="blf-status-pill '.$scoreTone.'">'.$pct.'%</span><div class="text-secondary mt-1" style="font-size:0.68rem;">'.e((string) $match->algorithm_version).'</div>',
                $lostHtml,
                $foundHtml,
                $factorsHtml,
                $this->badge($match->status),
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function claims(Request $request): JsonResponse
    {

        $user = $request->user();

        $baseQuery = Claim::query();
        if (! $user->isPlatformSuperAdmin()) {
            $orgId = $user->organizationId();
            $baseQuery->whereHas('itemReport', fn ($q) => $q->where('handling_organization_id', $orgId)->orWhere('custody_organization_id', $orgId));
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with(['claimant', 'itemReport']);

        if ($request->filled('status') && in_array($request->string('status')->toString(), ClaimStatus::values(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('reference_no', 'like', $like)
                    ->orWhereHas('claimant', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orWhereHas('itemReport', fn ($q) => $q->where('item_name', 'like', $like)->orWhere('reference_no', 'like', $like));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [3 => 'status', 4 => 'reference_no', 5 => 'submitted_at'];
        [$orderCol, $orderDir] = $this->resolveOrder($request, $orderColumns, 'submitted_at', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (Claim $claim): array {
            $actions = '<a href="'.e(route('admin.claims.show', $claim)).'" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>'.e(__('Review')).'</a>';

            return [
                $actions,
                '<div class="fw-medium text-navy">'.e((string) $claim->claimant?->name).'</div><div class="small text-secondary">'.e((string) $claim->claimant?->email).'</div>',
                '<div class="fw-medium text-navy">'.e((string) $claim->itemReport?->item_name).'</div><code class="small text-primary">'.e((string) $claim->itemReport?->reference_no).'</code>',
                $this->badge($claim->status),
                '<code class="small text-secondary">'.e($claim->reference_no).'</code>',
                '<span class="small text-secondary">'.e((string) $claim->submitted_at?->format('M d, Y H:i')).'</span>',
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function recoveries(Request $request): JsonResponse
    {

        $user = $request->user();

        $baseQuery = ItemRecovery::query();
        if (! $user->isPlatformSuperAdmin()) {
            $baseQuery->where('organization_id', $user->organizationId());
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with(['itemReport', 'claim', 'organization']);

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('received_by_name', 'like', $like)
                    ->orWhereHas('itemReport', fn ($q) => $q->where('item_name', 'like', $like)->orWhere('reference_no', 'like', $like));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [2 => 'received_by_name', 4 => 'turnover_at'];
        [$orderCol, $orderDir] = $this->resolveOrder($request, $orderColumns, 'turnover_at', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();
        $csrf = csrf_token();

        $data = $rows->map(function (ItemRecovery $recovery) use ($user, $csrf): array {
            $receiptUrl = e(route('admin.recoveries.receipt_pdf', $recovery));
            $actions = '<div class="d-inline-flex gap-1"><a href="'.$receiptUrl.'" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>'.e(__('Receipt PDF')).'</a>';
            if ($recovery->confirmed_by === null && $user->hasPermission('recovery.confirm')) {
                $url = e(route('admin.recoveries.confirm', $recovery));
                $actions .= '<form method="POST" action="'.$url.'" class="d-inline" data-confirm="'.e(__('Confirm physical recovery? This marks the item returned and closes competing claims.')).'" data-confirm-title="'.e(__('Confirm Recovery')).'" data-confirm-button="'.e(__('Confirm Recovery')).'" data-confirm-variant="btn-success">'
                    .'<input type="hidden" name="_token" value="'.$csrf.'">'
                    .'<input type="hidden" name="_method" value="PATCH">'
                    .'<button type="submit" class="btn btn-sm btn-success">'.e(__('Confirm')).'</button></form>';
            }
            $actions .= '</div>';

            $statusBadge = $recovery->confirmed_by !== null
                ? $this->badge('completed', __('Confirmed'))
                : $this->badge('pending', __('Awaiting Admin Confirmation'));

            return [
                $actions,
                '<div class="fw-medium text-navy">'.e((string) $recovery->itemReport?->item_name).'</div><code class="small text-secondary">'.e((string) $recovery->itemReport?->reference_no).' · '.e((string) $recovery->claim?->reference_no).'</code>',
                '<div class="fw-medium">'.e((string) $recovery->received_by_name).'</div><div class="small text-secondary">'.e((string) $recovery->organization?->name).'</div>',
                '<span class="small text-secondary">'.e((string) $recovery->id_type).' (****'.e((string) $recovery->id_last4).')</span>',
                '<span class="small text-secondary">'.e((string) $recovery->turnover_at?->format('M d, Y H:i')).'</span>',
                $statusBadge,
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function disposals(Request $request): JsonResponse
    {

        $user = $request->user();

        $baseQuery = ItemDisposal::query();
        if (! $user->isPlatformSuperAdmin()) {
            $baseQuery->where('organization_id', $user->organizationId());
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with(['itemReport.category', 'organization']);

        if ($request->filled('status') && in_array($request->string('status')->toString(), DisposalStatus::values(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->whereHas('itemReport', fn ($q) => $q->where('item_name', 'like', $like)->orWhere('reference_no', 'like', $like));
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [4 => 'eligible_on', 5 => 'method', 6 => 'status'];
        [$orderCol, $orderDir] = $this->resolveOrder($request, $orderColumns, 'id', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();
        $methods = DisposalService::METHODS;

        $data = $rows->map(function (ItemDisposal $disposal) use ($methods): array {
            $actions = '<a href="'.e(route('admin.disposals.show', $disposal)).'" class="btn btn-sm btn-outline-primary">'.e(__('Review')).'</a>';

            return [
                $actions,
                '<code class="small text-primary">'.e((string) $disposal->itemReport?->reference_no).'</code>',
                '<div class="fw-semibold text-navy">'.e((string) $disposal->itemReport?->item_name).'</div><div class="text-secondary small">'.e((string) $disposal->itemReport?->category?->label()).'</div>',
                e((string) $disposal->organization?->name),
                e((string) $disposal->eligible_on?->format('M d, Y')),
                e((string) ($methods[$disposal->method] ?? ($disposal->method ?: '—'))),
                $this->badge($disposal->status),
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function organizations(Request $request): JsonResponse
    {
        $baseQuery = Organization::query();
        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with('barangays');

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like));
        }

        $recordsFiltered = (clone $query)->count();
        [$orderCol, $orderDir] = $this->resolveOrder($request, [0 => 'code', 1 => 'name', 2 => 'type', 4 => 'is_active'], 'name', 'asc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (Organization $org): array {
            $badges = $org->barangays->map(fn (Barangay $b): string => '<span class="badge text-bg-light border text-secondary fw-normal me-1">'.e($b->name).'</span>')->implode('');

            return [
                '<span class="fw-semibold text-navy">'.e($org->code).'</span>',
                '<div class="fw-medium">'.e($org->name).'</div>'.($org->contact_email ? '<div class="small text-secondary">'.e($org->contact_email).'</div>' : ''),
                e($org->type->label()),
                $badges !== '' ? $badges : '<span class="text-secondary small">—</span>',
                $this->badge($org->is_active ? 'active' : 'suspended', $org->is_active ? __('Active') : __('Inactive')),
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function categories(Request $request): JsonResponse
    {
        $baseQuery = Category::query();
        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with('parent');

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(fn ($q) => $q->where('slug', 'like', $like)->orWhere('name_en', 'like', $like)->orWhere('name_fil', 'like', $like));
        }

        $recordsFiltered = (clone $query)->count();
        [$orderCol, $orderDir] = $this->resolveOrder($request, [0 => 'name_en', 1 => 'name_fil', 3 => 'slug', 4 => 'sort_order', 5 => 'is_active'], 'sort_order', 'asc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(fn (Category $cat): array => [
            '<span class="fw-medium text-navy">'.e($cat->label('en')).'</span>',
            e($cat->label('fil')),
            '<span class="text-secondary">'.e((string) ($cat->parent?->label('en') ?? '—')).'</span>',
            '<code class="small">'.e($cat->slug).'</code>',
            (string) $cat->sort_order,
            $this->badge($cat->is_active ? 'active' : 'suspended', $cat->is_active ? __('Active') : __('Inactive')),
        ])->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function barangays(Request $request): JsonResponse
    {
        $baseQuery = Barangay::query();
        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->withCount('locations');

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like));
        }

        $recordsFiltered = (clone $query)->count();
        [$orderCol, $orderDir] = $this->resolveOrder($request, [0 => 'code', 1 => 'name', 4 => 'is_active'], 'name', 'asc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(fn (Barangay $brgy): array => [
            '<span class="fw-semibold text-navy">'.e($brgy->code).'</span>',
            '<span class="fw-medium">'.e($brgy->name).'</span>',
            '<span class="text-secondary">'.e($brgy->municipality.', '.$brgy->province).'</span>',
            (string) $brgy->locations_count,
            $this->badge($brgy->is_active ? 'active' : 'suspended', $brgy->is_active ? __('Active') : __('Inactive')),
        ])->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function locations(Request $request): JsonResponse
    {
        $baseQuery = Location::query();
        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with(['barangay', 'organization']);

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('type', 'like', $like));
        }

        $recordsFiltered = (clone $query)->count();
        [$orderCol, $orderDir] = $this->resolveOrder($request, [0 => 'name', 3 => 'type', 4 => 'is_active'], 'name', 'asc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(fn (Location $loc): array => [
            '<span class="fw-medium text-navy">'.e($loc->name).'</span>',
            e((string) $loc->barangay?->name),
            '<span class="text-secondary">'.e((string) ($loc->organization?->name ?? '—')).'</span>',
            '<span class="text-capitalize">'.e((string) ($loc->type ?? '—')).'</span>',
            $this->badge($loc->is_active ? 'active' : 'suspended', $loc->is_active ? __('Active') : __('Inactive')),
        ])->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function memberships(Request $request): JsonResponse
    {

        $user = $request->user();
        $baseQuery = OrganizationMembership::query();
        if (! $user->isPlatformSuperAdmin()) {
            $baseQuery->where('organization_id', $user->organizationId());
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with(['user', 'organization', 'role']);

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        $recordsFiltered = (clone $query)->count();
        [$orderCol, $orderDir] = $this->resolveOrder($request, [4 => 'status'], 'id', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();
        $csrf = csrf_token();

        $data = $rows->map(function (OrganizationMembership $m) use ($user, $csrf): array {
            $actions = '<span class="small text-secondary">—</span>';
            $actions = '<span class="small text-secondary">—</span>';
            if ($m->role?->key === 'platform_super_admin') {
                $actions = '<span class="badge bg-light text-secondary border fw-normal">'.e(__('Protected')).'</span>';
            } elseif ($user->can('update', $m)) {
                $url = e(route('admin.memberships.update', $m));
                if ($m->status === MembershipStatus::Active) {
                    $actions = '<form method="POST" action="'.$url.'" class="d-inline" data-confirm="'.e(__("Suspend this member's institutional access?")).'" data-confirm-title="'.e(__('Suspend Member')).'" data-confirm-button="'.e(__('Suspend')).'">'
                        .'<input type="hidden" name="_token" value="'.$csrf.'">'
                        .'<input type="hidden" name="_method" value="PATCH">'
                        .'<input type="hidden" name="status" value="suspended">'
                        .'<button type="submit" class="btn btn-sm btn-outline-danger">'.e(__('Suspend')).'</button></form>';
                } else {
                    $actions = '<form method="POST" action="'.$url.'" class="d-inline">'
                        .'<input type="hidden" name="_token" value="'.$csrf.'">'
                        .'<input type="hidden" name="_method" value="PATCH">'
                        .'<input type="hidden" name="status" value="active">'
                        .'<button type="submit" class="btn btn-sm btn-outline-success">'.e(__('Activate')).'</button></form>';
                }
            }

            return [
                $actions,
                '<div class="fw-medium text-navy">'.e((string) $m->user?->name).'</div><div class="small text-secondary">'.e((string) $m->user?->email).'</div>',
                e((string) $m->role?->name),
                e((string) ($m->organization?->name ?? __('Platform-Wide'))),
                $this->badge($m->status),
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function invitations(Request $request): JsonResponse
    {

        $user = $request->user();
        $baseQuery = OrganizationInvitation::query();
        if (! $user->isPlatformSuperAdmin()) {
            $baseQuery->where('organization_id', $user->organizationId());
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with(['organization', 'role', 'inviter']);

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where('email', 'like', $like);
        }

        $recordsFiltered = (clone $query)->count();
        [$orderCol, $orderDir] = $this->resolveOrder($request, [1 => 'email', 4 => 'expires_at'], 'id', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();
        $csrf = csrf_token();

        $data = $rows->map(function (OrganizationInvitation $inv) use ($user, $csrf): array {
            $status = match (true) {
                $inv->accepted_at !== null => 'approved',
                $inv->revoked_at !== null => 'revoked',
                $inv->expires_at->isPast() => 'cancelled',
                default => 'pending',
            };
            $label = match ($status) {
                'approved' => __('Accepted'),
                'revoked' => __('Revoked'),
                'cancelled' => __('Expired'),
                default => __('Pending'),
            };

            $actions = '<span class="small text-secondary">—</span>';
            if ($status === 'pending' && $user->can('revoke', $inv)) {
                $url = e(route('admin.invitations.revoke', $inv));
                $actions = '<form method="POST" action="'.$url.'" class="d-inline" data-confirm="'.e(__('Revoke this pending invitation?')).'" data-confirm-title="'.e(__('Revoke Invitation')).'" data-confirm-button="'.e(__('Revoke')).'">'
                    .'<input type="hidden" name="_token" value="'.$csrf.'">'
                    .'<input type="hidden" name="_method" value="DELETE">'
                    .'<button type="submit" class="btn btn-sm btn-outline-danger">'.e(__('Revoke')).'</button></form>';
            }

            return [
                $actions,
                '<span class="fw-medium text-navy">'.e($inv->email).'</span>',
                e((string) $inv->role?->name),
                e((string) ($inv->organization?->name ?? __('Platform-Wide'))),
                '<span class="small text-secondary">'.e((string) $inv->expires_at?->format('M d, Y H:i')).'</span>',
                $this->badge($status, $label),
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    public function auditLogs(Request $request): JsonResponse
    {

        $user = $request->user();

        $baseQuery = AuditLog::query();
        if (! $user->isPlatformSuperAdmin()) {
            $baseQuery->where('organization_id', $user->organizationId());
        }

        $recordsTotal = (clone $baseQuery)->count();
        $query = (clone $baseQuery)->with(['user', 'organization']);

        if ($request->filled('action')) {
            $query->where('action', (string) $request->string('action'));
        }

        $search = $this->resolveSearch($request);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('action', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumns = [1 => 'action', 4 => 'ip_address', 5 => 'created_at'];
        [$orderCol, $orderDir] = $this->resolveOrder($request, $orderColumns, 'created_at', 'desc');
        $query->orderBy($orderCol, $orderDir);

        [$start, $length] = $this->resolvePagination($request);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (AuditLog $log): array {
            $oldJson = e((string) json_encode($log->old_values ?? (object) [], JSON_PRETTY_PRINT));
            $newJson = e((string) json_encode($log->new_values ?? (object) [], JSON_PRETTY_PRINT));
            $actor = e((string) ($log->user?->name ?? __('System')));
            $time = e((string) $log->created_at?->format('M d, Y H:i:s'));

            $actionBtn = '<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#auditDiffModal"'
                .' data-audit-action="'.e($log->action).'"'
                .' data-audit-actor="'.$actor.'"'
                .' data-audit-time="'.$time.'"'
                .' data-audit-old="'.$oldJson.'"'
                .' data-audit-new="'.$newJson.'">'
                .e(__('Inspect')).'</button>';

            $target = $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—';

            return [
                $actionBtn,
                '<span class="badge bg-light text-navy border fw-medium">'.e($log->action).'</span>',
                '<div class="fw-medium text-navy">'.$actor.'</div><div class="small text-secondary">'.e((string) ($log->organization?->name ?? '—')).'</div>',
                '<span class="small text-secondary">'.e($target).'</span>',
                '<code class="small text-secondary">'.e((string) ($log->ip_address ?: '—')).'</code>',
                '<span class="small text-secondary">'.e((string) $log->created_at?->format('M d, Y H:i')).'</span>',
            ];
        })->all();

        return $this->dtResponse($request, $recordsTotal, $recordsFiltered, $data);
    }

    protected function resolveSearch(Request $request): string
    {
        $dtSearch = trim((string) data_get($request->all(), 'search.value', ''));
        if ($dtSearch !== '') {
            return $dtSearch;
        }

        return trim((string) $request->query('q', ''));
    }

    protected function resolveOrder(Request $request, array $columnMap, string $defaultCol, string $defaultDir): array
    {
        $colIdx = (int) data_get($request->all(), 'order.0.column', -1);
        $dir = strtolower((string) data_get($request->all(), 'order.0.dir', $defaultDir));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : $defaultDir;
        $col = $columnMap[$colIdx] ?? $defaultCol;

        return [$col, $dir];
    }

    protected function resolvePagination(Request $request): array
    {
        $start = max(0, (int) $request->query('start', 0));
        $length = (int) $request->query('length', 15);
        $length = $length <= 0 ? 15 : min(100, $length);

        return [$start, $length];
    }

    protected function dtResponse(Request $request, int $total, int $filtered, array $data): JsonResponse
    {
        return response()->json([
            'draw' => (int) $request->query('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    protected function badge(mixed $status, ?string $label = null): string
    {
        return trim(Blade::render('<x-status-badge :status="$status" :label="$label" />', [
            'status' => $status,
            'label' => $label,
        ]));
    }
}
