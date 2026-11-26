@extends('layouts.public')

@section('title', $claim->reference_no)

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Claim Tracking') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ $claim->itemReport?->item_name }} <code class="fs-6 ms-1">{{ $claim->reference_no }}</code></h1>
        </div>
        <div class="d-flex gap-2">
            @if (in_array($claim->status, [App\Enums\ClaimStatus::Submitted, App\Enums\ClaimStatus::UnderReview, App\Enums\ClaimStatus::InfoRequested], true))
                <form method="POST" action="{{ route('claims.withdraw', $claim) }}" data-confirm="{{ __('Withdraw this ownership claim?') }}" data-confirm-title="{{ __('Withdraw Claim') }}" data-confirm-button="{{ __('Withdraw') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Withdraw Claim') }}</button>
                </form>
            @endif
            <a href="{{ route('claims.mine') }}" class="btn btn-sm btn-light bg-white border text-navy">{{ __('Back to My Claims') }}</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="blf-panel-card p-4 mb-3 small">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="fw-semibold text-navy">{{ __('Claim Details') }}</span>
                    <x-status-badge :status="$claim->status" />
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Found Item') }}</dt>
                    <dd class="col-sm-8">{{ $claim->itemReport?->item_name }} (<code>{{ $claim->itemReport?->reference_no }}</code>)</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Custody Institution') }}</dt>
                    <dd class="col-sm-8">{{ $claim->itemReport?->custodyOrganization?->name }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Ownership Proof') }}</dt>
                    <dd class="col-sm-8">{{ $claim->ownership_description }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Distinguishing Details') }}</dt>
                    <dd class="col-sm-8" style="white-space: pre-line;">{{ $claim->distinguishing_details }}</dd>

                    @if ($claim->turnover_scheduled_at)
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Scheduled Turnover') }}</dt>
                        <dd class="col-sm-8 fw-semibold text-success">{{ $claim->turnover_scheduled_at->format('F d, Y H:i') }}</dd>
                    @endif

                    @if ($claim->decision_remarks)
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Decision Remarks') }}</dt>
                        <dd class="col-sm-8">{{ $claim->decision_remarks }}</dd>
                    @endif
                </dl>
            </div>

            @if ($claim->status === App\Enums\ClaimStatus::InfoRequested)
                <div class="blf-panel-card p-4 mb-3">
                    <h2 class="h6 text-navy mb-2">{{ __('Respond to Information Request') }}</h2>
                    <form method="POST" action="{{ route('claims.respond', $claim) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <textarea name="details" rows="3" class="form-control form-control-sm" placeholder="{{ __('Provide the requested additional ownership proof...') }}" required></textarea>
                        </div>
                        <div class="mb-3">
                            <input type="file" name="evidence[]" accept="image/jpeg,image/png,image/webp,application/pdf" multiple class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Submit Response') }}</button>
                    </form>
                </div>
            @endif

            @if ($claim->evidence->isNotEmpty())
                <div class="blf-panel-card p-3">
                    <div class="small fw-semibold text-navy mb-2">{{ __('Private Supporting Evidence') }}</div>
                    <ul class="list-group list-group-flush small">
                        @foreach ($claim->evidence as $file)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-file-earmark-lock me-1 text-ocean" aria-hidden="true"></i>{{ $file->original_name }}</span>
                                <a href="{{ route('claims.evidence_own', [$claim, $file]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
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
    </div>
@endsection
