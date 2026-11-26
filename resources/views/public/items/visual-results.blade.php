@extends('layouts.public')

@section('title', __('Visual Search Results'))

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Visual Photo Search') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ __('Visually Similar Listings') }}</h1>
        </div>
        <a href="{{ route('public.items.index') }}" class="btn btn-sm btn-light bg-white border text-navy">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('Back to Browse') }}
        </a>
    </div>

    @if ($error)
        <div class="alert alert-warning small">{{ $error }}</div>
    @endif

    @if ($results->isEmpty())
        <div class="blf-panel-card">
            <x-empty-state icon="bi-camera" :title="__('No visually similar items matched')" :message="__('Try searching by keyword, category, or location instead.')">
                <a href="{{ route('public.items.index') }}" class="btn btn-sm btn-primary">{{ __('Use Text & Filter Search') }}</a>
            </x-empty-state>
        </div>
    @else
        <div id="visualFallbackGrid" class="row g-3">
            @foreach ($results as $idx => $entry)
                @php
                    $item = $entry['report'];
                    $pct = (int) round($entry['similarity'] * 100);
                    $coverImage = $item->images->firstWhere('is_primary', true) ?? $item->images->first();
                    $barClass = $pct >= 85 ? 'bg-success' : ($pct >= 70 ? 'bg-primary' : 'bg-secondary');
                @endphp
                <div class="col-md-6 col-xl-4 blf-match-card-enter" style="animation-delay: {{ min($idx * 70, 420) }}ms;">
                    <div class="blf-panel-card h-100 d-flex flex-column overflow-hidden">
                        <a href="{{ route('public.items.show', $item->reference_no) }}" class="d-block bg-light border-bottom position-relative" style="height: 180px;">
                            @if ($coverImage)
                                <img src="{{ route('public.items.image', [$item->reference_no, $coverImage]) }}" alt="{{ $item->item_name }}" class="w-100 h-100 object-fit-cover" loading="lazy">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                                    <i class="bi bi-image fs-2 opacity-50" aria-hidden="true"></i>
                                </div>
                            @endif
                            <span class="position-absolute top-0 end-0 m-2 badge bg-primary">
                                <span data-count-to="{{ $pct }}">0%</span> {{ __('Match') }}
                            </span>
                        </a>

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <code class="text-secondary">{{ $item->reference_no }}</code>
                                    <strong class="text-navy" data-count-to="{{ $pct }}">0%</strong>
                                </div>
                                <div class="blf-score-bar">
                                    <div class="blf-score-bar-fill {{ $barClass }}" data-bar-to="{{ $pct }}"></div>
                                </div>
                            </div>

                            <h2 class="h6 fw-semibold text-navy mb-1">
                                <a href="{{ route('public.items.show', $item->reference_no) }}" class="text-decoration-none text-navy">
                                    {{ $item->item_name }}
                                </a>
                            </h2>

                            <div class="small text-secondary mb-2">{{ $item->category?->label() }}</div>
                            <p class="small text-secondary mb-3 flex-grow-1">{{ Illuminate\Support\Str::limit($item->description, 105) }}</p>

                            <div class="pt-2 border-top d-flex align-items-center justify-content-between small text-secondary">
                                <span><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $item->location?->name ?: ($item->location_details ?: 'Boracay Island') }}</span>
                                <span>{{ $item->occurred_date?->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('visualFallbackGrid');
    if (grid && window.BLFScanner) {
        window.BLFScanner.animateMatchCounters(grid);
    }
});
</script>
@endPushOnce
