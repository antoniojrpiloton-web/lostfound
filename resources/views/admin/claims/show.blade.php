@extends('layouts.admin')

@section('eyebrow', __('Claim Verification'))
@section('title', $claim->reference_no)

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => __('Workspace'), 'url' => route('admin.dashboard')],
        ['label' => __('Claims Queue'), 'url' => route('admin.claims.index')],
        ['label' => $claim->reference_no],
    ]" />
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="blf-panel-card p-4 mb-3 small">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-semibold text-navy">{{ __('Ownership Proof & Private Evidence') }}</span>
                    <x-status-badge :status="$claim->status" />
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Claimant') }}</dt>
                    <dd class="col-sm-8 fw-medium text-navy">{{ $claim->claimant?->name }} ({{ $claim->claimant?->email }})</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Found Item') }}</dt>
                    <dd class="col-sm-8">{{ $claim->itemReport?->item_name }} (<code>{{ $claim->itemReport?->reference_no }}</code>)</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Ownership Description') }}</dt>
                    <dd class="col-sm-8">{{ $claim->ownership_description }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Distinguishing Proof') }}</dt>
                    <dd class="col-sm-8" style="white-space: pre-line;">{{ $claim->distinguishing_details }}</dd>

                    @if ($claim->lostReport)
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Linked Lost Report') }}</dt>
                        <dd class="col-sm-8"><code>{{ $claim->lostReport->reference_no }}</code> — {{ $claim->lostReport->item_name }}</dd>
                    @endif

                    @if ($claim->pickup_mode)
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Pickup Arrangement') }}</dt>
                        <dd class="col-sm-8">
                            @if ($claim->pickup_mode === 'next_day_booth')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Next-Day Police Booth Bring-Back') }}</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ __('Owner Pickup at Police Station') }}</span>
                            @endif
                            @if ($claim->pickup_location)
                                <span class="ms-1 text-navy fw-medium">{{ $claim->pickup_location }}</span>
                            @endif
                            @if ($claim->turnover_scheduled_at)
                                <div class="text-secondary mt-1">{{ __('Scheduled:') }} {{ $claim->turnover_scheduled_at->format('M d, Y h:i A') }}</div>
                            @endif
                        </dd>
                    @endif
                </dl>
            </div>

            @if ($claim->evidence->isNotEmpty())
                <div class="blf-panel-card p-3 mb-3">
                    <div class="small fw-semibold text-navy mb-2">{{ __('Submitted Evidence Files') }}</div>
                    <ul class="list-group list-group-flush small">
                        @foreach ($claim->evidence as $file)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-file-earmark-lock me-1 text-ocean" aria-hidden="true"></i>{{ $file->original_name }}</span>
                                <a href="{{ route('admin.claims.evidence', [$claim, $file]) }}" target="_blank" class="btn btn-sm btn-outline-primary">{{ __('Inspect') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="blf-panel-card">
                <div class="px-3 py-2 border-bottom">
                    <span class="small fw-semibold text-navy">{{ __('Claim History') }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($claim->histories as $history)
                        <div class="list-group-item px-3 py-2 small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <x-status-badge :status="$history->to_status" />
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
            @can('requestInfo', $claim)
                @if (in_array($claim->status, [App\Enums\ClaimStatus::Submitted, App\Enums\ClaimStatus::UnderReview], true))
                    <div class="blf-panel-card p-3 mb-3">
                        <h2 class="h6 text-navy mb-2">{{ __('Request Additional Info') }}</h2>
                        <form method="POST" action="{{ route('admin.claims.request_info', $claim) }}">
                            @csrf
                            @method('PATCH')
                            <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('Specify what extra ownership proof is needed...') }}" required></textarea>
                            <button type="submit" class="btn btn-sm btn-outline-warning w-100">{{ __('Request Info') }}</button>
                        </form>
                    </div>
                @endif
            @endcan

            @can('decide', $claim)
                @if (in_array($claim->status, [App\Enums\ClaimStatus::Submitted, App\Enums\ClaimStatus::UnderReview, App\Enums\ClaimStatus::InfoRequested], true))
                    <div class="blf-panel-card p-3 mb-3">
                        <h2 class="h6 text-navy mb-3">{{ __('Admin Decision') }}</h2>

                        <form method="POST" action="{{ route('admin.claims.approve', $claim) }}" class="mb-3 pb-3 border-bottom">
                            @csrf
                            @method('PATCH')
                            <label class="form-label small fw-medium">{{ __('Pickup / Release Arrangement') }}</label>
                            <select name="pickup_mode" class="form-select form-select-sm mb-2">
                                <option value="station_pickup">{{ __('Owner Pickup at Police Station (Default)') }}</option>
                                <option value="next_day_booth">{{ __('Next-Day Police Booth Bring-Back (Owner Contacted)') }}</option>
                            </select>
                            <input type="text" name="pickup_location" class="form-control form-control-sm mb-2" value="{{ $claim->itemReport?->booth_location }}" placeholder="{{ __('Police Station or Target Beach Booth (e.g. Station 2 Tent)') }}">
                            <label class="form-label small fw-medium">{{ __('Schedule Turnover (Optional)') }}</label>
                            <input type="datetime-local" name="turnover_scheduled_at" class="form-control form-control-sm mb-2">
                            <input type="text" name="remarks" class="form-control form-control-sm mb-2" placeholder="{{ __('Approval remarks') }}">
                            <button type="submit" class="btn btn-sm btn-success w-100">{{ __('Approve Claim') }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.claims.reject', $claim) }}" data-confirm="{{ __('Reject this ownership claim?') }}" data-confirm-title="{{ __('Reject Claim') }}" data-confirm-button="{{ __('Reject') }}">
                            @csrf
                            @method('PATCH')
                            <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('Rejection reason (required)') }}" required></textarea>
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">{{ __('Reject Claim') }}</button>
                        </form>
                    </div>
                @endif
            @endcan

            @if ($claim->status === App\Enums\ClaimStatus::Approved && auth()->user()?->hasPermission('recovery.record') && ! $claim->recovery)
                <div class="blf-panel-card p-3">
                    <h2 class="h6 text-navy mb-2">{{ __('Record Physical Turnover') }}</h2>
                    <p class="small text-secondary mb-3">{{ __('Record ID type and last 4 digits only (data minimization).') }}</p>
                    <form method="POST" action="{{ route('admin.claims.recovery.store', $claim) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-medium">{{ __('Receiving Claimant Name') }}</label>
                            <input type="text" name="received_by_name" value="{{ $claim->claimant?->name }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <label class="form-label small fw-medium">{{ __('ID Type') }}</label>
                                <input type="text" name="id_type" class="form-control form-control-sm" placeholder="Passport / PhilSys" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-medium">{{ __('Last 4 Digits') }}</label>
                                <input type="text" name="id_last4" maxlength="10" class="form-control form-control-sm" placeholder="4821" required>
                            </div>
                        </div>
                        <input type="hidden" name="verification_outcome" value="verified">
                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('Turnover remarks (optional)') }}"></textarea>
                        @if (auth()->user()?->hasPermission('recovery.confirm'))
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="confirm_now" id="confirm_now" value="1" checked>
                                <label class="form-check-label small" for="confirm_now">{{ __('Confirm recovery immediately') }}</label>
                            </div>
                        @endif
                        <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('Save Recovery Record') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
