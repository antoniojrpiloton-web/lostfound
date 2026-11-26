@extends('layouts.admin')

@section('eyebrow', __('Smart Report Inspector'))
@section('title', $report->translated('item_name'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => __('Workspace'), 'url' => route('admin.dashboard')],
        ['label' => __('Reports Queue'), 'url' => route('admin.reports.index')],
        ['label' => $report->reference_no],
    ]" />
@endsection

@section('actions')
    <div class="d-flex flex-wrap gap-2">
        @if ($report->is_public)
            <a href="{{ route('public.items.show', $report->reference_no) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>{{ __('View Public Listing') }}
            </a>
        @endif
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-light bg-white border text-navy">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('Back to Queue') }}
        </a>
    </div>
@endsection

@section('content')
    @php
        $primaryImage = $report->images->sortByDesc('is_primary')->first();
        $isVerified = $report->verification_status === App\Enums\VerificationStatus::Verified;
        $hasActiveClaims = $report->claims->whereIn('status', [
            App\Enums\ClaimStatus::Submitted,
            App\Enums\ClaimStatus::UnderReview,
            App\Enums\ClaimStatus::InfoRequested,
            App\Enums\ClaimStatus::Approved,
        ])->isNotEmpty();
        $isCompleted = in_array($report->item_status, [
            App\Enums\ItemStatus::Returned,
            App\Enums\ItemStatus::Resolved,
            App\Enums\ItemStatus::Disposed,
            App\Enums\ItemStatus::Closed,
        ], true);
        $topMatch = $matches->first();
    @endphp

    <div class="blf-panel-card p-3 p-md-4 mb-3" style="background: linear-gradient(135deg, #ffffff 60%, #eff6ff 100%);">
        <div class="d-flex flex-wrap align-items-start gap-3">
            <div class="rounded-3 overflow-hidden border bg-white flex-shrink-0" style="width: 96px; height: 96px;">
                @if ($primaryImage)
                    <a href="#reportImageModal" data-bs-toggle="modal" data-preview-src="{{ route('admin.images.preview', $primaryImage) }}" class="d-block w-100 h-100">
                        <img src="{{ route('admin.images.preview', $primaryImage) }}" alt="{{ $report->item_name }}" class="w-100 h-100" style="object-fit: cover;">
                    </a>
                @else
                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-primary opacity-50" style="background: #eff6ff;">
                        <i class="bi bi-box-seam fs-2" aria-hidden="true"></i>
                    </div>
                @endif
            </div>

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <code class="px-2 py-1 rounded bg-white border text-primary fw-semibold">{{ $report->reference_no }}</code>
                    <x-status-badge :status="$report->type" />
                    <x-status-badge :status="$report->verification_status" :label="$report->verification_status->label()" />
                    <x-status-badge :status="$report->item_status" />
                    @if ($report->is_public)
                        <span class="blf-status-pill blf-pill-info"><i class="bi bi-globe2" aria-hidden="true"></i>{{ __('Public') }}</span>
                    @else
                        <span class="blf-status-pill blf-pill-neutral"><i class="bi bi-lock" aria-hidden="true"></i>{{ __('Internal Only') }}</span>
                    @endif
                </div>
                <h2 class="h5 fw-bold text-navy mb-1">{{ $report->translated('item_name') }}</h2>
                <div class="small text-secondary d-flex flex-wrap gap-3">
                    <span><i class="bi bi-tag text-primary me-1" aria-hidden="true"></i>{{ $report->category?->parent ? $report->category->parent->label().' — ' : '' }}{{ $report->category?->label() }}</span>
                    <span><i class="bi bi-geo-alt text-primary me-1" aria-hidden="true"></i>{{ implode(', ', array_filter([$report->location?->name, $report->location_details ? $report->translated('location_details') : null])) ?: '—' }}</span>
                    <span><i class="bi bi-calendar3 text-primary me-1" aria-hidden="true"></i>{{ $report->occurred_date?->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        <div class="row g-2 mt-3 pt-3 border-top small">
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-primary">1</span>
                    <span class="fw-semibold text-navy">{{ __('Registered') }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill {{ $isVerified ? 'bg-primary' : 'bg-secondary bg-opacity-25 text-secondary' }}">2</span>
                    <span class="{{ $isVerified ? 'fw-semibold text-navy' : 'text-secondary' }}">{{ __('Verified & Published') }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill {{ ($matches->isNotEmpty() || $hasActiveClaims) ? 'bg-primary' : 'bg-secondary bg-opacity-25 text-secondary' }}">3</span>
                    <span class="{{ ($matches->isNotEmpty() || $hasActiveClaims) ? 'fw-semibold text-navy' : 'text-secondary' }}">{{ __('Matched / Claimed') }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill {{ $isCompleted ? 'bg-success' : 'bg-secondary bg-opacity-25 text-secondary' }}">4</span>
                    <span class="{{ $isCompleted ? 'fw-semibold text-success' : 'text-secondary' }}">{{ __('Recovered / Closed') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="blf-panel-card mb-3">
                <div class="px-3 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="small fw-semibold text-navy">
                        <i class="bi bi-stars text-primary me-1" aria-hidden="true"></i>{{ __('Smart Insights & Suggested Matches') }}
                    </span>
                    @if (auth()->user()?->hasPermission('match.view'))
                        <div class="d-flex align-items-center gap-2">
                            <form id="scanMatchesForm" method="POST" action="{{ route('admin.reports.scan_matches', $report) }}" class="m-0">
                                @csrf
                                <button type="submit" id="scanMatchesBtn" class="btn btn-sm btn-outline-primary py-0 px-2">
                                    <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>{{ __('Scan Matches Now') }}
                                </button>
                            </form>
                            <a href="{{ route('admin.matches.index') }}" class="small text-decoration-none fw-medium text-primary">
                                {{ __('Open Match Queue') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endif
                </div>
                <div class="p-3">
                    <div id="scanMatchesFeedback" class="d-none mb-2"></div>
                    <div id="smartMatchesContainer">
                        @include('admin.reports.partials.match-comparisons')
                    </div>
                </div>
            </div>

            <div class="blf-panel-card mb-3">
                <div class="px-3 py-3 border-bottom">
                    <span class="small fw-semibold text-navy">{{ __('Item Specification & Custody Metadata') }}</span>
                </div>
                <div class="p-3 small">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="p-2 rounded-3 bg-light border h-100">
                                <div class="text-secondary" style="font-size: 0.72rem;">{{ __('Brand / Model / Color') }}</div>
                                <div class="fw-medium text-navy">{{ implode(' · ', array_filter([$report->brand, $report->model, $report->color])) ?: '—' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-3 bg-light border h-100">
                                <div class="text-secondary" style="font-size: 0.72rem;">{{ __('Date & Time') }}</div>
                                <div class="fw-medium text-navy">{{ $report->occurred_date?->format('F d, Y') }} {{ $report->occurred_time ? '· '.$report->occurred_time : '' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-3 bg-light border h-100">
                                <div class="text-secondary" style="font-size: 0.72rem;">{{ __('Handling / Custody Org') }}</div>
                                <div class="fw-medium text-navy">{{ $report->custodyOrganization?->name ?? $report->handlingOrganization?->name ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-secondary fw-normal">{{ __('Location Details') }}</dt>
                        <dd class="col-sm-9">{{ implode(', ', array_filter([$report->location?->name, $report->location_details ? $report->translated('location_details') : null])) ?: '—' }}</dd>

                        <dt class="col-sm-3 text-secondary fw-normal">{{ __('Description') }}</dt>
                        <dd class="col-sm-9">{{ $report->translated('description') }}</dd>

                        @if ($report->distinguishing_features)
                            <dt class="col-sm-3 text-secondary fw-normal">{{ __('Distinguishing Features') }}</dt>
                            <dd class="col-sm-9">{{ $report->translated('distinguishing_features') }}</dd>
                        @endif

                        @if ($report->finder_name)
                            <dt class="col-sm-3 text-secondary fw-normal">{{ __('Finder / Surrenderer') }}</dt>
                            <dd class="col-sm-9">{{ $report->finder_name }}</dd>
                        @endif

                        @if ($report->reporter)
                            <dt class="col-sm-3 text-secondary fw-normal">{{ __('Submitted By') }}</dt>
                            <dd class="col-sm-9">{{ $report->reporter->name }} <span class="text-secondary">({{ $report->reporter->email }})</span></dd>
                        @endif

                        @if ($report->verifier)
                            <dt class="col-sm-3 text-secondary fw-normal">{{ __('Verified By') }}</dt>
                            <dd class="col-sm-9">{{ $report->verifier->name }} <span class="text-secondary">({{ $report->verified_at?->format('M d, Y H:i') }})</span></dd>
                        @endif

                        @if ($report->rejection_reason)
                            <dt class="col-sm-3 text-danger fw-medium">{{ __('Rejection Reason') }}</dt>
                            <dd class="col-sm-9 text-danger">{{ $report->rejection_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if ($report->images->isNotEmpty())
                <div class="blf-panel-card mb-3">
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold text-navy">{{ __('Uploaded Photographs') }} ({{ $report->images->count() }})</span>
                        <span class="small text-secondary">{{ __('Click any photo to enlarge') }}</span>
                    </div>
                    <div class="p-3">
                        <div class="row g-3">
                            @foreach ($report->images as $image)
                                @php $isIndexed = in_array($image->id, $indexedImageIds, true); @endphp
                                <div class="col-sm-4">
                                    <div class="border rounded-3 overflow-hidden bg-white h-100">
                                        <a href="#reportImageModal" data-bs-toggle="modal" data-preview-src="{{ route('admin.images.preview', $image) }}" class="d-block bg-light" style="height: 150px;">
                                            <img src="{{ route('admin.images.preview', $image) }}" alt="" class="w-100 h-100" style="object-fit: cover;">
                                        </a>
                                        <div class="p-2 small">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-1">
                                                <x-status-badge :status="$image->validation_status" />
                                                @if ($isIndexed)
                                                    <span class="badge bg-primary-subtle text-primary" style="font-size: 0.66rem;">Visual Indexed ✓</span>
                                                @endif
                                            </div>
                                            <div class="text-secondary" style="font-size: 0.72rem;">
                                                {{ $image->width && $image->height ? $image->width.'×'.$image->height.'px · ' : '' }}{{ number_format(($image->size ?? 0) / 1024, 1) }} KB
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="blf-panel-card">
                <div class="px-3 py-2 border-bottom">
                    <span class="small fw-semibold text-navy">{{ __('Status & Custody Timeline') }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($report->statusHistories as $history)
                        <div class="list-group-item px-3 py-2 small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <span class="fw-medium text-navy text-capitalize">{{ $history->dimension->value }}</span>
                                    <span class="text-secondary ms-1">{{ $history->from_status ? $history->from_status.' → ' : '' }}</span>
                                    <x-status-badge :status="$history->to_status" />
                                </div>
                                <span class="text-secondary" style="font-size: 0.72rem;">{{ $history->created_at?->format('M d, Y H:i') }}</span>
                            </div>
                            @if ($history->remarks)
                                <div class="text-secondary">{{ $history->remarks }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if ($report->type === App\Enums\ReportType::Found && $report->item_status === App\Enums\ItemStatus::AwaitingTurnover && auth()->user()?->hasPermission('custody.manage'))
                <div class="blf-panel-card p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h2 class="h6 text-navy mb-0">{{ __('Held by Finder · Booth Supervision') }}</h2>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ __('With Finder') }}</span>
                    </div>
                    <p class="small text-secondary mb-2">
                        {{ __('This item is currently held by the finder. When a match is found, contact or notify the finder to bring the item to the Police Booth for supervised return.') }}
                    </p>
                    <div class="p-2 rounded bg-light border small mb-3">
                        <div><span class="text-secondary">{{ __('Finder:') }}</span> <strong class="text-navy">{{ $report->finder_name ?: ($report->reporter?->name ?? '—') }}</strong></div>
                        @if ($report->finder_contact_enc)
                            <div><span class="text-secondary">{{ __('Contact:') }}</span> <strong class="text-navy">{{ $report->finder_contact_enc }}</strong></div>
                        @endif
                        @if ($report->reporter?->email)
                            <div><span class="text-secondary">{{ __('Email:') }}</span> <span class="text-navy">{{ $report->reporter->email }}</span></div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.reports.notify_finder', $report) }}" class="mb-3 pb-3 border-bottom">
                        @csrf
                        <div class="small fw-semibold text-navy mb-2">{{ __('Notify Finder for Supervised Booth Return') }}</div>
                        <input type="text" name="booth_location" class="form-control form-control-sm mb-2" value="{{ $report->custodyOrganization?->name ?? $report->handlingOrganization?->name ?? 'Boracay Tourist Police Booth' }}" placeholder="{{ __('Police Booth / Meetup Location') }}" required>
                        <textarea name="instructions" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('e.g. A matching lost report was found. Please bring the item to the Police Booth tomorrow at 10 AM for supervised return.') }}" required></textarea>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-bell me-1" aria-hidden="true"></i>{{ __('Notify Finder for Booth Return') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.reports.confirm_turnover', $report) }}">
                        @csrf
                        @method('PATCH')
                        <div class="small fw-semibold text-navy mb-2">{{ __('Confirm Physical Receipt at Booth') }}</div>
                        <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('Turnover remarks (optional)') }}">
                        <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('Confirm Item Received at Booth') }}</button>
                    </form>
                </div>
            @endif

            @can('verify', $report)
                @if (in_array($report->verification_status, [App\Enums\VerificationStatus::Pending, App\Enums\VerificationStatus::NeedsRevision], true))
                    <div class="blf-panel-card p-3 mb-3">
                        <h2 class="h6 text-navy mb-3">{{ __('Verification Actions') }}</h2>

                        <form method="POST" action="{{ route('admin.reports.verify', $report) }}" class="mb-3 pb-3 border-bottom">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('Verification remarks (optional)') }}">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Verify & Publish') }}
                            </button>
                        </form>

                        @if ($report->verification_status === App\Enums\VerificationStatus::Pending)
                            <form method="POST" action="{{ route('admin.reports.request_revision', $report) }}" class="mb-3 pb-3 border-bottom">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('What needs to be revised?') }}" required>
                                <button type="submit" class="btn btn-sm btn-outline-warning w-100">{{ __('Request Revision') }}</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.reports.reject', $report) }}" data-confirm="{{ __('Reject this report? It will remain unpublished.') }}" data-confirm-title="{{ __('Reject Report') }}" data-confirm-button="{{ __('Reject') }}">
                            @csrf
                            @method('PATCH')
                            <textarea name="rejection_reason" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('Rejection reason (required)') }}" required></textarea>
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">{{ __('Reject Report') }}</button>
                        </form>
                    </div>
                @endif
            @endcan

            @if ($report->claims->isNotEmpty())
                <div class="blf-panel-card p-3 mb-3">
                    <h2 class="h6 text-navy mb-2">{{ __('Related Claims') }}</h2>
                    <div class="list-group list-group-flush small">
                        @foreach ($report->claims as $claim)
                            <a href="{{ route('admin.claims.show', $claim) }}" class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold text-navy">{{ $claim->reference_no }}</div>
                                    <div class="text-secondary" style="font-size: 0.74rem;">{{ $claim->claimant?->name }}</div>
                                </div>
                                <x-status-badge :status="$claim->status" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($report->disposal)
                <div class="blf-panel-card p-3 mb-3">
                    <h2 class="h6 text-navy mb-2">{{ __('Disposal Record') }}</h2>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span class="text-secondary">{{ __('Status') }}</span>
                        <x-status-badge :status="$report->disposal->status" />
                    </div>
                    <a href="{{ route('admin.disposals.show', $report->disposal) }}" class="btn btn-sm btn-outline-primary w-100">
                        {{ __('Open Disposal Review') }}
                    </a>
                </div>
            @endif

            @if ($report->type === App\Enums\ReportType::Found && in_array($report->item_status, [App\Enums\ItemStatus::InCustody, App\Enums\ItemStatus::ClaimPending], true) && auth()->user()?->hasPermission('custody.manage'))
                @php
                    $stage = $report->effectiveCustodyStage();
                @endphp
                <div class="blf-panel-card p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <h2 class="h6 text-navy mb-0">{{ __('Police Booth & Station Custody') }}</h2>
                        @if ($stage)
                            <span class="badge {{ $stage->badgeClass() }}">{{ $stage->label() }}</span>
                        @endif
                    </div>

                    <div class="p-2 rounded bg-light border small mb-3">
                        @if ($report->booth_location)
                            <div><span class="text-secondary">{{ __('Booth / Tent:') }}</span> <strong class="text-navy">{{ $report->booth_location }}</strong></div>
                        @endif
                        @if ($report->station_transferred_at)
                            <div><span class="text-secondary">{{ __('Brought to Station:') }}</span> <strong class="text-navy">{{ $report->station_transferred_at->format('M d, Y h:i A') }}</strong></div>
                        @endif
                        <div class="text-secondary mt-1" style="font-size: 0.75rem;">
                            @if ($stage === App\Enums\CustodyStage::AtBooth)
                                {{ __('Currently held at the beach Police Booth / Tent. If unclaimed by end of shift, log its return to the Police Station.') }}
                            @elseif ($stage === App\Enums\CustodyStage::ScheduledForBooth)
                                {{ __('Owner contacted. Scheduled to be brought out to the designated beach booth on the next duty shift.') }}
                            @else
                                {{ __('Secured at the Police Station. Policy: items do not leave the station unless an owner is contacted/verified for next-day booth handoff; otherwise, owner claims directly at the station.') }}
                            @endif
                        </div>
                    </div>

                    @if ($stage === App\Enums\CustodyStage::AtBooth)
                        <form method="POST" action="{{ route('admin.reports.return_to_station', $report) }}">
                            @csrf
                            @method('PATCH')
                            <div class="small fw-semibold text-navy mb-2">{{ __('End of Shift · Return Unclaimed Item to Station') }}</div>
                            <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('e.g. Unclaimed after 6 PM shift, brought to Boracay Tourist Police Station') }}">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-building-lock me-1" aria-hidden="true"></i>{{ __('Log Return to Police Station') }}
                            </button>
                        </form>
                    @elseif ($stage === App\Enums\CustodyStage::AtStation)
                        <form method="POST" action="{{ route('admin.reports.schedule_booth_handoff', $report) }}">
                            @csrf
                            @method('PATCH')
                            <div class="small fw-semibold text-navy mb-2">{{ __('Schedule Next-Day Beach Booth Bring-Back') }}</div>
                            <input type="text" name="booth_location" class="form-control form-control-sm mb-2" value="{{ $report->booth_location }}" placeholder="{{ __('Target Police Booth / Beach Area (e.g. Station 2 Tent)') }}" required>
                            <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('e.g. Owner confirmed pickup tomorrow 9 AM') }}">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="owner_contacted" id="owner_contacted" value="1" required>
                                <label class="form-check-label small text-secondary" for="owner_contacted">
                                    {{ __('Owner has been contacted/verified (required before item leaves station)') }}
                                </label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>{{ __('Schedule Next-Day Booth Handoff') }}
                            </button>
                        </form>
                    @elseif ($stage === App\Enums\CustodyStage::ScheduledForBooth)
                        <form method="POST" action="{{ route('admin.reports.return_to_station', $report) }}">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('Reason if owner was a no-show (optional)') }}">
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="bi bi-arrow-return-left me-1" aria-hidden="true"></i>{{ __('Return Back to Police Station') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            @if ($report->type === App\Enums\ReportType::Found && in_array($report->item_status, [App\Enums\ItemStatus::InCustody, App\Enums\ItemStatus::ClaimPending], true) && auth()->user()?->hasPermission('custody.manage') && isset($transferOrganizations) && $transferOrganizations->isNotEmpty())
                <div class="blf-panel-card p-3 mb-3">
                    <h2 class="h6 text-navy mb-2">{{ __('Inter-Agency Custody Transfer') }}</h2>
                    <p class="small text-secondary mb-2">{{ __('Transfer physical custody to another Boracay Police Station or Partner Desk.') }}</p>
                    <form method="POST" action="{{ route('admin.reports.transfer_custody', $report) }}" data-confirm="{{ __('Transfer physical custody of this item to the selected institution?') }}" data-confirm-title="{{ __('Confirm Custody Transfer') }}" data-confirm-button="{{ __('Transfer Custody') }}">
                        @csrf
                        @method('PATCH')
                        <select name="to_organization_id" class="form-select form-select-sm mb-2" required>
                            <option value="">{{ __('Select receiving institution...') }}</option>
                            @foreach ($transferOrganizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('Transfer remarks / receiving officer (optional)') }}">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>{{ __('Transfer Custody') }}
                        </button>
                    </form>
                </div>
            @endif

            @if ($report->custodyRecords->isNotEmpty())
                <div class="blf-panel-card p-3">
                    <h2 class="h6 text-navy mb-2">{{ __('Custody Chain') }}</h2>
                    <div class="d-flex flex-column gap-2 small">
                        @foreach ($report->custodyRecords as $custody)
                            <div class="p-2 rounded bg-light border">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-navy text-capitalize">{{ $custody->action->value }}</span>
                                    <span class="text-secondary" style="font-size: 0.7rem;">{{ $custody->recorded_at?->format('M d, Y') }}</span>
                                </div>
                                <div class="text-secondary" style="font-size: 0.74rem;">{{ $custody->organization?->name }}</div>
                                @if ($custody->remarks)
                                    <div class="text-secondary mt-1" style="font-size: 0.74rem;">{{ $custody->remarks }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="fbPublishModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy mb-0">
                        <i class="bi bi-facebook text-primary me-1" aria-hidden="true"></i>{{ __('Publish to Facebook — Shareable Poster & Caption') }}
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-6 text-center">
                            <div class="border rounded-3 overflow-hidden bg-light mb-2">
                                <img
                                    src="{{ route('admin.reports.fb_poster', $report) }}"
                                    alt="{{ __('Facebook Poster for :ref', ['ref' => $report->reference_no]) }}"
                                    class="img-fluid w-100"
                                    loading="lazy"
                                >
                            </div>
                            <a href="{{ route('admin.reports.fb_poster', [$report, 'download' => 1]) }}" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Download FB Poster (PNG)') }}
                            </a>
                        </div>
                        <div class="col-md-6">
                            <label for="fbCaptionTextarea" class="form-label small fw-semibold text-navy">{{ __('Ready-to-Post Facebook Caption') }}</label>
                            <textarea id="fbCaptionTextarea" rows="11" class="form-control form-control-sm mb-2" readonly>{{ $fbCaption }}</textarea>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" id="copyFbCaptionBtn" class="btn btn-sm btn-outline-primary flex-grow-1">
                                    <i class="bi bi-clipboard me-1" aria-hidden="true"></i>{{ __('Copy Caption') }}
                                </button>
                                <a
                                    href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($fbShareUrl) }}&quote={{ urlencode($fbCaption) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-primary flex-grow-1"
                                >
                                    <i class="bi bi-facebook me-1" aria-hidden="true"></i>{{ __('Share on Facebook') }}
                                </a>
                            </div>
                            <div class="form-text small text-secondary mt-2">
                                {{ __('Tip: Download the PNG poster (with uploaded item photo) and paste the copied caption directly onto your official Facebook Page or Group.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reportImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy mb-0">{{ $report->item_name }} — {{ __('Photo Preview') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-2 text-center bg-dark">
                    <img id="reportImageModalImg" src="" alt="{{ $report->item_name }}" class="img-fluid rounded" style="max-height: 75vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('reportImageModal');
    const modalImg = document.getElementById('reportImageModalImg');
    modal?.addEventListener('show.bs.modal', (event) => {
        const src = event.relatedTarget?.getAttribute('data-preview-src');
        if (src && modalImg) {
            modalImg.src = src;
        }
    });

    const copyBtn = document.getElementById('copyFbCaptionBtn');
    const captionEl = document.getElementById('fbCaptionTextarea');
    copyBtn?.addEventListener('click', async () => {
        if (!captionEl) return;
        try {
            await navigator.clipboard.writeText(captionEl.value);
            copyBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy Caption';
            }, 1800);
        } catch (_) {
            captionEl.select();
            document.execCommand('copy');
        }
    });

    const scanForm = document.getElementById('scanMatchesForm');
    const scanBtn = document.getElementById('scanMatchesBtn');
    const scanFeedback = document.getElementById('scanMatchesFeedback');
    const matchesContainer = document.getElementById('smartMatchesContainer');

    scanForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!scanBtn) return;

        scanBtn.disabled = true;
        scanBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning...';

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(scanForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (response.ok && data.html && matchesContainer) {
                matchesContainer.innerHTML = data.html;
                if (scanFeedback) {
                    scanFeedback.className = 'alert alert-primary py-2 px-3 small mb-2';
                    scanFeedback.textContent = data.message || 'Match scan complete.';
                }
            } else if (scanFeedback) {
                scanFeedback.className = 'alert alert-danger py-2 px-3 small mb-2';
                scanFeedback.textContent = data.message || 'Unable to scan matches right now.';
            }
        } catch (_) {
            if (scanFeedback) {
                scanFeedback.className = 'alert alert-danger py-2 px-3 small mb-2';
                scanFeedback.textContent = 'Match scan failed. Please try again.';
            }
        } finally {
            scanBtn.disabled = false;
            scanBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Scan Matches Now';
        }
    });
});
</script>
@endPushOnce
