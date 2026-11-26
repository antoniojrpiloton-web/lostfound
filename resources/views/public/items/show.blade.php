@extends('layouts.public')

@section('title', $report->item_name)

@push('meta')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ strtoupper($report->type->value) }}: {{ $report->item_name }} ({{ $report->reference_no }})">
    <meta property="og:description" content="{{ Illuminate\Support\Str::limit($report->description, 160) }}">
    <meta property="og:url" content="{{ route('public.items.show', $report->reference_no) }}">
    <meta property="og:image" content="{{ route('public.items.fb_poster', $report->reference_no) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="1200">
    <meta name="twitter:card" content="summary_large_image">
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Verified Public Record') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ $report->item_name }} <code class="fs-6 ms-1">{{ $report->reference_no }}</code></h1>
        </div>
        <a href="{{ route('public.items.index') }}" class="btn btn-sm btn-light bg-white border text-navy">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('Back to Listings') }}
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="blf-panel-card p-4 mb-3">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <x-status-badge status="verified" :label="__('Verified')" />
                    <x-status-badge :status="$report->type" />
                    <x-status-badge :status="$report->item_status" :label="$report->publicStatusLabel()" />
                </div>

                <dl class="row small mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Category') }}</dt>
                    <dd class="col-sm-8">{{ $report->category?->label() }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Brand / Model / Color') }}</dt>
                    <dd class="col-sm-8">{{ implode(' · ', array_filter([$report->brand, $report->model, $report->color])) ?: '—' }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Date') }}</dt>
                    <dd class="col-sm-8">{{ $report->occurred_date?->format('F d, Y') }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Location / Area') }}</dt>
                    <dd class="col-sm-8">{{ implode(', ', array_filter([$report->location?->name, $report->location_details])) ?: '—' }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Description') }}</dt>
                    <dd class="col-sm-8">
                        <div>{{ $descriptionTranslation['text'] ?? $report->description }}</div>
                        @if (! empty($descriptionTranslation['translated']))
                            <div class="mt-2 p-2 rounded bg-light border small text-secondary">
                                <span class="badge bg-info-subtle text-info-emphasis me-1">{{ __('Auto-translated') }}</span>
                                <span class="fw-medium">{{ __('Original:') }}</span> {{ $report->description }}
                            </div>
                        @endif
                    </dd>

                    @if ($report->reward_offered)
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Reward Note') }}</dt>
                        <dd class="col-sm-8">{{ $report->reward_details ?: __('Reward offered by owner') }}</dd>
                    @endif
                </dl>
            </div>

            @if ($publicImages->isNotEmpty())
                <div class="blf-panel-card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small fw-semibold text-navy">{{ __('Verified Photographs') }}</div>
                        <span class="small text-secondary">{{ __('Click photo to enlarge') }}</span>
                    </div>
                    <div class="row g-2">
                        @foreach ($publicImages as $image)
                            <div class="col-sm-4">
                                <a href="#publicImageLightbox" data-bs-toggle="modal" data-preview-src="{{ route('public.items.image', [$report->reference_no, $image]) }}" class="d-block">
                                    <img src="{{ route('public.items.image', [$report->reference_no, $image]) }}" alt="{{ $report->item_name }}" class="img-fluid rounded border w-100" style="height: 170px; object-fit: cover;">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($similarItems->isNotEmpty())
                <div class="blf-panel-card p-3">
                    <div class="small fw-semibold text-navy mb-2">{{ __('Related Verified Listings') }}</div>
                    <div class="row g-2">
                        @foreach ($similarItems as $sim)
                            @php $simThumb = $sim->images->filter(fn ($i): bool => $i->public_path !== null)->sortByDesc('is_primary')->first(); @endphp
                            <div class="col-sm-4">
                                <a href="{{ route('public.items.show', $sim->reference_no) }}" class="text-decoration-none d-block rounded-3 border bg-white overflow-hidden h-100">
                                    @if ($simThumb)
                                        <img src="{{ route('public.items.image', [$sim->reference_no, $simThumb]) }}" alt="{{ $sim->item_name }}" class="w-100" style="height: 110px; object-fit: cover;">
                                    @endif
                                    <div class="p-2">
                                        <div class="small fw-semibold text-navy text-truncate">{{ $sim->item_name }}</div>
                                        <div class="text-primary" style="font-size: 0.72rem;"><code>{{ $sim->reference_no }}</code></div>
                                        <div class="text-secondary" style="font-size: 0.72rem;">{{ $sim->location?->name ?: ($sim->location_details ?: 'Boracay Island') }} · {{ $sim->occurred_date?->format('M d, Y') }}</div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="blf-panel-card p-3">
                <h2 class="h6 text-navy mb-2">{{ __('Handling Institution') }}</h2>
                <p class="small fw-medium text-navy mb-2">
                    {{ $report->custodyOrganization?->name ?? $report->handlingOrganization?->name ?? __('Boracay Lost and Found Desk') }}
                </p>
                @if ($report->type === App\Enums\ReportType::Found && ($custodyStage = $report->effectiveCustodyStage()))
                    <div class="p-2 rounded bg-light border small mb-3">
                        <div class="mb-1">
                            <span class="badge {{ $custodyStage->badgeClass() }}">{{ $custodyStage->label() }}</span>
                        </div>
                        @if ($custodyStage === App\Enums\CustodyStage::AtBooth)
                            <div class="text-secondary" style="font-size: 0.78rem;">
                                {{ __('Currently at :booth. Unclaimed items are brought back to the Police Station at the end of the shift.', ['booth' => $report->booth_location ?: __('the beach Police Booth / Tent')]) }}
                            </div>
                        @elseif ($custodyStage === App\Enums\CustodyStage::AtStation)
                            <div class="text-secondary" style="font-size: 0.78rem;">
                                {{ __('Safekept at the Police Station. Items remain at the station unless an approved owner arranges a next-day Police Booth handoff.') }}
                            </div>
                        @elseif ($custodyStage === App\Enums\CustodyStage::ScheduledForBooth)
                            <div class="text-secondary" style="font-size: 0.78rem;">
                                {{ __('Scheduled for next-day Police Booth handoff at :booth.', ['booth' => $report->booth_location ?: __('designated beach booth')]) }}
                            </div>
                        @elseif ($custodyStage === App\Enums\CustodyStage::WithFinder)
                            <div class="text-secondary" style="font-size: 0.78rem;">
                                {{ __('Currently held by the finder. Once matched, police will coordinate a supervised return at the Police Booth.') }}
                            </div>
                        @endif
                    </div>
                @endif
                <p class="small text-secondary mb-3">
                    {{ __('For privacy protection under RA 10173, personal contact details and ownership verification proofs are never published.') }}
                </p>

                @if ($report->type === App\Enums\ReportType::Found && in_array($report->item_status, [App\Enums\ItemStatus::AwaitingTurnover, App\Enums\ItemStatus::InCustody, App\Enums\ItemStatus::ClaimPending], true))
                    @auth
                        @can('create', App\Models\Claim::class)
                            <a href="{{ route('claims.create', $report->reference_no) }}" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-patch-check me-1" aria-hidden="true"></i>{{ __('Claim This Found Item') }}
                            </a>
                        @endcan
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-google me-1" aria-hidden="true"></i>{{ __('Sign In to Claim This Item') }}
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="publicFbShareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy mb-0">
                        <i class="bi bi-facebook text-primary me-1" aria-hidden="true"></i>{{ __('Share on Facebook') }}
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-6 text-center">
                            <div class="border rounded-3 overflow-hidden bg-light mb-2">
                                <img
                                    src="{{ route('public.items.fb_poster', $report->reference_no) }}"
                                    alt="{{ __('Facebook Poster for :ref', ['ref' => $report->reference_no]) }}"
                                    class="img-fluid w-100"
                                    loading="lazy"
                                >
                            </div>
                            <a href="{{ route('public.items.fb_poster', [$report->reference_no, 'download' => 1]) }}" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Download FB Poster (PNG)') }}
                            </a>
                        </div>
                        <div class="col-md-6">
                            <label for="publicFbCaption" class="form-label small fw-semibold text-navy">{{ __('Ready-to-Post Caption') }}</label>
                            <textarea id="publicFbCaption" rows="10" class="form-control form-control-sm mb-2" readonly>{{ $fbCaption }}</textarea>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" id="copyPublicFbCaptionBtn" class="btn btn-sm btn-outline-primary flex-grow-1">
                                    <i class="bi bi-clipboard me-1" aria-hidden="true"></i>{{ __('Copy Caption') }}
                                </button>
                                <a
                                    href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($fbShareUrl) }}&quote={{ urlencode($fbCaption) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-primary flex-grow-1"
                                >
                                    <i class="bi bi-facebook me-1" aria-hidden="true"></i>{{ __('Open Facebook') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="publicImageLightbox" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy mb-0">{{ $report->item_name }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-2 text-center bg-dark">
                    <img id="publicImageLightboxImg" src="" alt="{{ $report->item_name }}" class="img-fluid rounded" style="max-height: 75vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('publicImageLightbox');
    const img = document.getElementById('publicImageLightboxImg');
    modal?.addEventListener('show.bs.modal', (event) => {
        const src = event.relatedTarget?.getAttribute('data-preview-src');
        if (src && img) img.src = src;
    });

    const copyBtn = document.getElementById('copyPublicFbCaptionBtn');
    const captionEl = document.getElementById('publicFbCaption');
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
});
</script>
@endPushOnce
