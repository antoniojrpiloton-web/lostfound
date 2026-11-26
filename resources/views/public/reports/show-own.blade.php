@extends('layouts.public')

@section('title', $report->reference_no)

@section('content')
    <section class="py-4">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <div class="blf-page-eyebrow">{{ __('Report Tracking') }}</div>
                    <h1 class="h4 text-navy fw-bold mb-0">{{ $report->item_name }} <code class="fs-6 ms-1">{{ $report->reference_no }}</code></h1>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if (in_array($report->verification_status, [App\Enums\VerificationStatus::Pending, App\Enums\VerificationStatus::NeedsRevision], true))
                        <form method="POST" action="{{ route('reports.withdraw', $report) }}" data-confirm="{{ __('Withdraw this report?') }}" data-confirm-title="{{ __('Withdraw Report') }}" data-confirm-button="{{ __('Withdraw') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Withdraw Report') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('reports.mine') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('Back to My Reports') }}
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header bg-white py-2 px-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <span class="small fw-semibold text-navy">{{ __('Report Information') }}</span>
                            <div class="d-flex gap-2">
                                <x-status-badge :status="$report->type" />
                                <x-status-badge :status="$report->verification_status" :label="$report->verification_status->label()" />
                                <x-status-badge :status="$report->item_status" />
                            </div>
                        </div>
                        <div class="card-body p-3 small">
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-secondary fw-normal">{{ __('Category') }}</dt>
                                <dd class="col-sm-8">{{ $report->category?->label() }}</dd>

                                <dt class="col-sm-4 text-secondary fw-normal">{{ __('Brand / Model / Color') }}</dt>
                                <dd class="col-sm-8">{{ implode(' · ', array_filter([$report->brand, $report->model, $report->color])) ?: '—' }}</dd>

                                <dt class="col-sm-4 text-secondary fw-normal">{{ __('Date & Time') }}</dt>
                                <dd class="col-sm-8">{{ $report->occurred_date?->format('F d, Y') }} {{ $report->occurred_time ? '· '.$report->occurred_time : '' }}</dd>

                                <dt class="col-sm-4 text-secondary fw-normal">{{ __('Location') }}</dt>
                                <dd class="col-sm-8">{{ implode(', ', array_filter([$report->location?->name, $report->location_details])) ?: '—' }}</dd>

                                <dt class="col-sm-4 text-secondary fw-normal">{{ __('Handling Institution') }}</dt>
                                <dd class="col-sm-8">{{ $report->handlingOrganization?->name ?? __('Pending Assignment') }}</dd>

                                <dt class="col-sm-4 text-secondary fw-normal">{{ __('Description') }}</dt>
                                <dd class="col-sm-8">{{ $report->description }}</dd>

                                @if ($report->distinguishing_features)
                                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Distinguishing Features') }}</dt>
                                    <dd class="col-sm-8">{{ $report->distinguishing_features }}</dd>
                                @endif

                                @if ($report->reward_offered)
                                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Reward') }}</dt>
                                    <dd class="col-sm-8">{{ $report->reward_details ?: __('Reward offered') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if ($report->images->isNotEmpty())
                        <div class="card">
                            <div class="card-header bg-white py-2 px-3">
                                <span class="small fw-semibold text-navy">{{ __('Uploaded Photographs') }}</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    @foreach ($report->images as $image)
                                        <div class="col-sm-4">
                                            <div class="border rounded p-2 text-center">
                                                <img src="{{ route('reports.image_own', [$report, $image]) }}" alt="{{ $report->item_name }}" class="img-fluid rounded mb-2" style="max-height: 150px; object-fit: cover;">
                                                <div class="mb-2"><x-status-badge :status="$image->validation_status" /></div>
                                                @if (in_array($image->validation_status, [App\Enums\ImageValidationStatus::Rejected, App\Enums\ImageValidationStatus::Review], true))
                                                    <form method="POST" action="{{ route('reports.image_replace', [$report, $image]) }}" enctype="multipart/form-data" class="text-start">
                                                        @csrf
                                                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm mb-1" required>
                                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">{{ __('Replace Photo') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-white py-2 px-3">
                            <span class="small fw-semibold text-navy">{{ __('Status History') }}</span>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($report->statusHistories as $history)
                                <div class="list-group-item px-3 py-2 small">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-medium text-navy text-capitalize">{{ $history->dimension->value }}</span>
                                        <x-status-badge :status="$history->to_status" />
                                    </div>
                                    @if ($history->remarks)
                                        <div class="text-secondary">{{ $history->remarks }}</div>
                                    @endif
                                    <div class="text-secondary mt-1" style="font-size: 0.72rem;">{{ $history->created_at?->format('M d, Y H:i') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
