@extends('layouts.public')

@section('title', __('Submit Ownership Claim'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <div class="blf-page-eyebrow">{{ __('Ownership Claim') }}</div>
                    <h1 class="h4 text-navy fw-bold mb-0">{{ __('Claim Found Item') }} <code class="fs-6 ms-1">{{ $report->reference_no }}</code></h1>
                </div>
                <a href="{{ route('public.items.show', $report->reference_no) }}" class="btn btn-sm btn-light bg-white border text-navy">{{ __('Back to Item') }}</a>
            </div>

            <div class="blf-panel-card p-3 mb-3 small">
                <div class="fw-semibold text-navy">{{ $report->item_name }}</div>
                <div class="text-secondary">{{ $report->category?->label() }} · {{ $report->location?->name ?: ($report->location_details ?: 'Boracay Island') }} · {{ __('Custody:') }} {{ $report->custodyOrganization?->name }}</div>
            </div>

            <div class="blf-panel-card p-4">
                <form method="POST" action="{{ route('claims.store', $report->reference_no) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="ownership_description" class="form-label small fw-medium">{{ __('Proof of Ownership Description') }} <span class="text-danger">*</span></label>
                        <textarea id="ownership_description" name="ownership_description" rows="3" class="form-control form-control-sm @error('ownership_description') is-invalid @enderror" placeholder="{{ __('Describe when and where you lost this item and how you can prove it belongs to you.') }}" required>{{ old('ownership_description') }}</textarea>
                        @error('ownership_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="distinguishing_details" class="form-label small fw-medium">{{ __('Private Distinguishing Characteristics') }} <span class="text-danger">*</span></label>
                        <textarea id="distinguishing_details" name="distinguishing_details" rows="3" class="form-control form-control-sm @error('distinguishing_details') is-invalid @enderror" placeholder="{{ __('Hidden marks, serial numbers, wallpaper, contents, or details not shown publicly.') }}" required>{{ old('distinguishing_details') }}</textarea>
                        @error('distinguishing_details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if ($myLostReports->isNotEmpty())
                        <div class="mb-3">
                            <label for="lost_report_id" class="form-label small fw-medium">{{ __('Link Your Lost Report (Optional)') }}</label>
                            <select id="lost_report_id" name="lost_report_id" class="form-select form-select-sm" data-select2="local" data-placeholder="{{ __('Select your lost report (optional)') }}">
                                <option value=""></option>
                                @foreach ($myLostReports as $lost)
                                    <option value="{{ $lost->id }}" @selected((string) old('lost_report_id') === (string) $lost->id)>
                                        {{ $lost->reference_no }} — {{ $lost->item_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <x-dropzone
                            id="evidence"
                            name="evidence[]"
                            :label="__('Supporting Ownership Evidence (Private — JPG, PNG, WEBP, or PDF, up to 3 files)')"
                            :hint="__('Drag & drop receipts, older photos, or proof of purchase. Stored privately for the reviewing institution only.')"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            :multiple="true"
                            :max-files="3"
                        />
                    </div>

                    <div class="mb-4">
                        <label for="evidence_description" class="form-label small fw-medium">{{ __('Evidence Note (Optional)') }}</label>
                        <input id="evidence_description" name="evidence_description" type="text" value="{{ old('evidence_description') }}" class="form-control form-control-sm">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>{{ __('Submit Ownership Claim') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
