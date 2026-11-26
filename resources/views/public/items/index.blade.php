@extends('layouts.public')

@section('title', __('Browse Lost & Found Items'))

@section('content')
    @php
        $advancedFilterKeys = ['category_id', 'location_id', 'date_from', 'date_to', 'public_status'];
        $activeAdvancedCount = collect($advancedFilterKeys)->filter(fn (string $key): bool => ! empty($filters[$key]))->count();
        $activeType = (string) ($filters['type'] ?? '');
    @endphp

    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Verified Public Listings') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ __('Browse Lost & Found Items') }}</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('reports.lost.create') }}" class="btn btn-sm btn-primary">{{ __('Report Lost Item') }}</a>
            <a href="{{ route('reports.found.create') }}" class="btn btn-sm btn-light bg-white border text-navy">{{ __('Report Found Item') }}</a>
        </div>
    </div>

    @if ($visualSearchEnabled)
        <div class="collapse mb-3" id="inlineDeepSearchPanel">
            <div class="blf-panel-card p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold text-navy mb-0">{{ __('Search by Photo') }}</h2>
                        <p class="small text-secondary mb-0">{{ __('Upload 1 to 5 photos of your item to scan verified Lost & Found records.') }}</p>
                    </div>
                    <button type="button" class="btn-close small" data-bs-toggle="collapse" data-bs-target="#inlineDeepSearchPanel" aria-label="{{ __('Close') }}"></button>
                </div>

                <form id="inlineVisualSearchForm" method="POST" action="{{ route('public.items.visual_search') }}" data-ajax-url="{{ route('api.smart_search') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <x-dropzone
                            id="inline_deep_photo"
                            name="photos[]"
                            :label="__('Item Photo(s)')"
                            :hint="__('Drag & drop 1 to 5 images (JPG, PNG, WEBP, max 5MB each)')"
                            accept="image/jpeg,image/png,image/webp"
                            :multiple="true"
                            :max-files="5"
                            :required="true"
                            :compact="true"
                        />
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label for="inline_deep_type" class="form-label small text-secondary mb-1">{{ __('Search Scope') }}</label>
                            <select id="inline_deep_type" name="type" class="form-select">
                                <option value="" @selected($activeType === '')>{{ __('All Verified Lost & Found Items') }}</option>
                                <option value="found" @selected($activeType === 'found')>{{ __('Verified Found Items Only') }}</option>
                                <option value="lost" @selected($activeType === 'lost')>{{ __('Verified Lost Items Only') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" id="inlineDeepSubmitBtn" class="btn btn-primary w-100" style="min-height: 2.75rem;">
                                <i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Scan & Search') }}
                            </button>
                        </div>
                    </div>
                </form>

                <div id="inlineDeepResultsWrap" class="d-none mt-4 pt-3 border-top">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div id="inlineDeepStatus" class="small fw-semibold text-navy"></div>
                        <button type="button" id="inlineDeepClearBtn" class="btn btn-sm btn-light border">{{ __('Clear Photo Results') }}</button>
                    </div>
                    <div id="inlineDeepResultsGrid" class="row g-3"></div>
                </div>
            </div>
        </div>
    @endif

    <div class="blf-panel-card p-3 p-md-4 mb-3">
        <form method="GET" action="{{ route('public.items.index') }}">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Filter by type') }}">
                    <a href="{{ route('public.items.index', array_filter(array_merge($filters, ['type' => null]))) }}" class="btn {{ $activeType === '' ? 'btn-primary' : 'btn-light border text-navy' }}">
                        {{ __('All Listings') }}
                    </a>
                    <a href="{{ route('public.items.index', array_merge($filters, ['type' => 'found'])) }}" class="btn {{ $activeType === 'found' ? 'btn-primary' : 'btn-light border text-navy' }}">
                        {{ __('Found Items') }}
                    </a>
                    <a href="{{ route('public.items.index', array_merge($filters, ['type' => 'lost'])) }}" class="btn {{ $activeType === 'lost' ? 'btn-primary' : 'btn-light border text-navy' }}">
                        {{ __('Lost Items') }}
                    </a>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border text-navy" data-bs-toggle="collapse" data-bs-target="#publicAdvancedFilters" aria-expanded="{{ $activeAdvancedCount > 0 ? 'true' : 'false' }}" aria-controls="publicAdvancedFilters">
                        <i class="bi bi-sliders me-1" aria-hidden="true"></i>{{ __('Filters') }}
                        @if ($activeAdvancedCount > 0)
                            <span class="badge bg-primary ms-1">{{ $activeAdvancedCount }}</span>
                        @endif
                    </button>
                    @if (collect($filters)->filter()->isNotEmpty())
                        <a href="{{ route('public.items.index') }}" class="btn btn-sm btn-link text-secondary text-decoration-none px-1">{{ __('Reset') }}</a>
                    @endif
                </div>
            </div>

            @if ($activeType !== '')
                <input type="hidden" name="type" value="{{ $activeType }}">
            @endif

            <div class="row g-2 align-items-center">
                <div class="col-md-7">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y text-secondary" style="left: 0.9rem;" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control ps-5" placeholder="{{ __('Search by item name, brand, model, color, or reference no...') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select">
                        @if (! empty($filters['q']))
                            <option value="relevance" @selected(($filters['sort'] ?? 'relevance') === 'relevance')>{{ __('Sort: Most Relevant') }}</option>
                        @endif
                        <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>{{ __('Sort: Newest Verified') }}</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('Sort: Oldest') }}</option>
                        <option value="date_desc" @selected(($filters['sort'] ?? '') === 'date_desc')>{{ __('Sort: Date Occurred (Newest)') }}</option>
                        <option value="date_asc" @selected(($filters['sort'] ?? '') === 'date_asc')>{{ __('Sort: Date Occurred (Oldest)') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="min-height: 2.75rem;">
                        {{ __('Search') }}
                    </button>
                </div>
            </div>

            <div class="collapse {{ $activeAdvancedCount > 0 ? 'show' : '' }} mt-3 pt-3 border-top" id="publicAdvancedFilters">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_category_id" class="form-label small text-secondary mb-1">{{ __('Category') }}</label>
                        <select id="filter_category_id" name="category_id" class="form-select" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('All Categories') }}">
                            <option value=""></option>
                            @if ($selectedCategory)
                                <option value="{{ $selectedCategory->id }}" selected>{{ $selectedCategory->parent ? $selectedCategory->parent->label().' — ' : '' }}{{ $selectedCategory->label() }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_location_id" class="form-label small text-secondary mb-1">{{ __('Landmark / Area') }}</label>
                        <select id="filter_location_id" name="location_id" class="form-select" data-select2-url="{{ route('api.select2.locations') }}" data-placeholder="{{ __('All Landmarks & Areas') }}">
                            <option value=""></option>
                            @if ($selectedLocation)
                                <option value="{{ $selectedLocation->id }}" selected>{{ $selectedLocation->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_public_status" class="form-label small text-secondary mb-1">{{ __('Status') }}</label>
                        <select id="filter_public_status" name="public_status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="lost" @selected(($filters['public_status'] ?? '') === 'lost')>{{ __('Lost') }}</option>
                            <option value="found" @selected(($filters['public_status'] ?? '') === 'found')>{{ __('Found') }}</option>
                            <option value="claim_pending" @selected(($filters['public_status'] ?? '') === 'claim_pending')>{{ __('Claim Pending') }}</option>
                            <option value="claimed" @selected(($filters['public_status'] ?? '') === 'claimed')>{{ __('Claimed') }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="filter_date_from" class="form-label small text-secondary mb-1">{{ __('From Date') }}</label>
                        <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control px-2">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="filter_date_to" class="form-label small text-secondary mb-1">{{ __('To Date') }}</label>
                        <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control px-2">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 small text-secondary">
        <div>
            @if ($items->total() > 0)
                {{ __('Showing :from–:to of :total verified listings', ['from' => $items->firstItem(), 'to' => $items->lastItem(), 'total' => number_format($items->total())]) }}
            @else
                {{ __('0 listings found') }}
            @endif
        </div>
        @if (! empty($filters['q']))
            <div>{{ __('Keyword:') }} <strong class="text-navy">"{{ $filters['q'] }}"</strong></div>
        @endif
    </div>

    @if ($items->isEmpty())
        <div class="blf-panel-card">
            <x-empty-state icon="bi-search" :title="__('No matching public items found')" :message="__('Try adjusting your search keywords or clearing filters.')">
                <a href="{{ route('public.items.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Clear Filters') }}</a>
            </x-empty-state>
        </div>
    @else
        <div class="row g-3">
            @foreach ($items as $item)
                @php
                    $coverImage = $item->images->firstWhere('is_primary', true) ?? $item->images->first();
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="blf-panel-card h-100 d-flex flex-column overflow-hidden">
                        <a href="{{ route('public.items.show', $item->reference_no) }}" class="d-block overflow-hidden bg-light position-relative border-bottom" style="height: 184px;">
                            @if ($coverImage)
                                <img src="{{ route('public.items.image', [$item->reference_no, $coverImage]) }}" alt="{{ $item->item_name }}" class="w-100 h-100 object-fit-cover" loading="lazy">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                                    <i class="bi bi-image fs-2 opacity-50" aria-hidden="true"></i>
                                </div>
                            @endif
                            <span class="position-absolute top-0 start-0 m-2 badge {{ $item->type->value === 'found' ? 'text-bg-primary' : 'text-bg-dark' }}">
                                {{ $item->type->label() }}
                            </span>
                        </a>

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <code class="small text-secondary">{{ $item->reference_no }}</code>
                                <x-status-badge :status="$item->item_status" :label="$item->publicStatusLabel()" />
                            </div>

                            <h2 class="h6 fw-semibold text-navy mb-1">
                                <a href="{{ route('public.items.show', $item->reference_no) }}" class="text-decoration-none text-navy">
                                    {{ $item->item_name }}
                                </a>
                            </h2>

                            <div class="small text-secondary mb-2">
                                <i class="bi bi-tag me-1" aria-hidden="true"></i>{{ $item->category?->label() }}
                                @if ($item->color) · {{ $item->color }} @endif
                            </div>

                            <p class="small text-secondary mb-3 flex-grow-1">
                                {{ Illuminate\Support\Str::limit($item->description, 105) }}
                            </p>

                            <div class="pt-2 border-top d-flex align-items-center justify-content-between small text-secondary">
                                <span class="text-truncate me-2">
                                    <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $item->location?->name ?? Illuminate\Support\Str::limit((string) ($item->location_details ?: 'Boracay Island'), 28) }}
                                </span>
                                <span class="flex-shrink-0">{{ $item->occurred_date?->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    @endif
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('inlineVisualSearchForm');
    if (!form) return;

    const photoInput = document.getElementById('inline_deep_photo');
    const typeSelect = document.getElementById('inline_deep_type');
    const submitBtn = document.getElementById('inlineDeepSubmitBtn');
    const resultsWrap = document.getElementById('inlineDeepResultsWrap');
    const statusEl = document.getElementById('inlineDeepStatus');
    const gridEl = document.getElementById('inlineDeepResultsGrid');
    const clearBtn = document.getElementById('inlineDeepClearBtn');

    clearBtn?.addEventListener('click', () => {
        resultsWrap?.classList.add('d-none');
        if (gridEl) gridEl.innerHTML = '';
        if (statusEl) statusEl.textContent = '';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!photoInput?.files || photoInput.files.length === 0) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning...';
        resultsWrap?.classList.remove('d-none');
        if (statusEl) statusEl.textContent = '';

        const scan = window.BLFScanner
            ? window.BLFScanner.startPhotoScan(gridEl, photoInput.files, { minDuration: 1550 })
            : null;

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const fd = new FormData();
        Array.from(photoInput.files).forEach((f) => fd.append('photos[]', f));
        if (typeSelect?.value) fd.append('type', typeSelect.value);

        try {
            const response = await fetch(form.dataset.ajaxUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await response.json();
            if (scan) await scan.finish();

            const items = data.items || [];
            if (data.error) {
                if (statusEl) statusEl.textContent = data.error;
                if (gridEl) gridEl.innerHTML = '';
                return;
            }

            if (items.length === 0) {
                if (statusEl) statusEl.textContent = 'No visually similar verified items found.';
                if (gridEl) gridEl.innerHTML = '';
                return;
            }

            if (statusEl) statusEl.textContent = `${items.length} visually similar item(s) found — ranked by similarity`;
            const warnings = form.querySelector('[data-dropzone-root]')?.blfQualityWarnings || [];
            const warningHtml = warnings.length > 0
                ? `<div class="col-12"><div class="alert alert-warning d-flex align-items-start gap-2 mb-1 py-2 px-3 small"><i class="bi bi-exclamation-triangle-fill text-warning-emphasis flex-shrink-0 mt-1"></i><div><strong>Low Image Quality Notice:</strong> Uploaded photo appears blurry, too dark, overexposed, or unclear. Predicted matches below may be less accurate.</div></div></div>`
                : '';

            gridEl.innerHTML = warningHtml + items.map((item, idx) => {
                const hasScore = item.score !== null && item.score !== undefined;
                const tier = hasScore && window.BLFScanner ? window.BLFScanner.scoreTier(item.score) : null;
                return `
                <div class="col-md-6 col-xl-4 blf-match-card-enter" style="animation-delay: ${Math.min(idx * 70, 420)}ms;">
                    <div class="card h-100 border overflow-hidden">
                        <div class="position-relative bg-light border-bottom" style="height: 170px;">
                            ${item.image_url ? `<img src="${item.image_url}" alt="${item.item_name}" class="w-100 h-100 object-fit-cover">` : '<div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary"><i class="bi bi-image fs-2 opacity-50"></i></div>'}
                            <span class="position-absolute top-0 start-0 m-2 badge bg-white text-navy border">${item.status_label}</span>
                            ${hasScore ? `<span class="position-absolute top-0 end-0 m-2 badge bg-primary"><span data-count-to="${item.score}">0%</span> Match</span>` : ''}
                        </div>
                        <div class="card-body p-3 d-flex flex-column">
                            ${hasScore ? `
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.72rem;">
                                        <span class="badge ${tier.badgeClass}">${tier.label}</span>
                                        <strong class="text-navy" data-count-to="${item.score}">0%</strong>
                                    </div>
                                    <div class="blf-score-bar"><div class="blf-score-bar-fill ${tier.barClass}" data-bar-to="${item.score}"></div></div>
                                </div>
                            ` : ''}
                            <div class="fw-semibold text-navy text-truncate">${item.item_name}</div>
                            <code class="small text-secondary mb-1">${item.reference_no}</code>
                            <div class="small text-secondary mb-3 flex-grow-1">${[item.type_label, item.category, item.location, item.occurred_date].filter(Boolean).join(' · ')}</div>
                            <div class="d-flex gap-2 pt-2 border-top">
                                <a href="${item.url}" class="btn btn-sm btn-outline-primary flex-grow-1">View Details</a>
                                ${item.claim_url ? `<a href="${item.claim_url}" class="btn btn-sm btn-primary flex-grow-1">Claim</a>` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('');

            window.BLFScanner?.animateMatchCounters(gridEl);
        } catch (_) {
            if (scan) await scan.finish();
            if (statusEl) statusEl.textContent = 'Unable to run photo search right now. Please try again.';
            if (gridEl) gridEl.innerHTML = '';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-search me-1" aria-hidden="true"></i>Scan & Search';
        }
    });
});
</script>
@endPushOnce
