@extends('layouts.public')

@section('title', __('Home'))
@section('full_bleed', '1')

@section('content')
    <section class="bg-white border-bottom py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 text-center">
                    <div class="blf-page-eyebrow mb-2">{{ __('Boracay Island · Malay, Aklan') }}</div>
                    <h1 class="display-6 fw-bold text-navy mb-2" style="letter-spacing: -0.025em;">
                        {{ __('Boracay Lost and Found System') }}
                    </h1>
                    <p class="text-secondary mb-4 mx-auto" style="max-width: 640px;">
                        {{ __('Search verified lost and found belongings across Boracay Island landmarks and stations using keywords or by uploading a photo of the item.') }}
                    </p>
                </div>

                <div class="col-lg-9">
                    <div class="card border shadow-sm">
                        <div class="card-body p-3 p-md-4">
                            <ul class="nav nav-pills gap-2 mb-3" id="homeSearchTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active small fw-medium px-3 py-2" id="homeTabText" data-bs-toggle="pill" data-bs-target="#homePaneText" type="button" role="tab" aria-selected="true">
                                        <i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Search by Keyword') }}
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="homePaneText" role="tabpanel">
                                    <form method="GET" action="{{ route('public.items.index') }}" role="search">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-7">
                                                <div class="position-relative">
                                                    <i class="bi bi-search position-absolute top-50 translate-middle-y text-secondary" style="left: 0.9rem;" aria-hidden="true"></i>
                                                    <input type="search" name="q" value="{{ request('q') }}" class="form-control ps-5" placeholder="{{ __('e.g. Black Leather Wallet, iPhone 15, Station 2, D\'Mall...') }}" aria-label="{{ __('Search lost and found items') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <select name="type" class="form-select" aria-label="{{ __('Listing type') }}">
                                                    <option value="">{{ __('All Listings') }}</option>
                                                    <option value="found">{{ __('Found Items Only') }}</option>
                                                    <option value="lost">{{ __('Lost Items Only') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100" style="min-height: 2.75rem;">
                                                    {{ __('Search') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="homePanePhoto" role="tabpanel">
                                    <form id="homePhotoSearchForm" data-ajax-url="{{ route('api.smart_search') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <x-dropzone
                                                id="home_search_photo"
                                                name="photos[]"
                                                :hint="__('Drag & drop 1 to 5 photos of the item (JPG, PNG, WEBP, max 5MB each)')"
                                                accept="image/jpeg,image/png,image/webp"
                                                :multiple="true"
                                                :max-files="5"
                                                :compact="true"
                                            />
                                        </div>
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-8">
                                                <select id="homePhotoScopeType" name="type" class="form-select" aria-label="{{ __('Search scope') }}">
                                                    <option value="">{{ __('Search All Verified Lost & Found Items') }}</option>
                                                    <option value="found">{{ __('Search Verified Found Items Only') }}</option>
                                                    <option value="lost">{{ __('Search Verified Lost Items Only') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" id="homePhotoSubmitBtn" class="btn btn-primary w-100" style="min-height: 2.75rem;">
                                                    <i class="bi bi-camera me-1" aria-hidden="true"></i>{{ __('Scan & Find Matches') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <div id="homePhotoResultsWrap" class="d-none mt-4 pt-3 border-top">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                            <div id="homePhotoStatus" class="small fw-semibold text-navy"></div>
                                            <button type="button" id="homePhotoClearBtn" class="btn btn-sm btn-light border">{{ __('Clear Results') }}</button>
                                        </div>
                                        <div id="homePhotoResultsGrid" class="row g-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        <a href="{{ route('public.items.index') }}" class="btn btn-light bg-white border text-navy fw-medium px-3">
                            <i class="bi bi-grid me-1" aria-hidden="true"></i>{{ __('Browse All Listings') }}
                        </a>
                        <a href="{{ route('reports.lost.create') }}" class="btn btn-primary fw-medium px-3">
                            <i class="bi bi-megaphone me-1" aria-hidden="true"></i>{{ __('Report Lost Item') }}
                        </a>
                        <a href="{{ route('reports.found.create') }}" class="btn btn-light bg-white border text-navy fw-medium px-3">
                            <i class="bi bi-box-seam me-1" aria-hidden="true"></i>{{ __('Report Found Item') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <div class="blf-page-eyebrow mb-1">{{ __('Recovery Process') }}</div>
                <h2 class="h4 fw-bold text-navy mb-1">{{ __('How It Works') }}</h2>
                <p class="small text-secondary mb-0">{{ __('Coordinated with PNP Boracay Police Stations and beachfront booths for verified item custody.') }}</p>
            </div>

            <div class="row g-3 mb-5">
                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body p-4">
                            <span class="blf-step-badge mb-3">1</span>
                            <h3 class="h6 fw-semibold text-navy">{{ __('Submit & Verify') }}</h3>
                            <p class="small text-secondary mb-0">{{ __('Sign in with Google to submit a lost or found report with photos. Police personnel verify records before publication.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body p-4">
                            <span class="blf-step-badge mb-3">2</span>
                            <h3 class="h6 fw-semibold text-navy">{{ __('Search, Match & Claim') }}</h3>
                            <p class="small text-secondary mb-0">{{ __('Search verified listings by keyword or photo, or receive notifications when a match is found. Submit private proof of ownership to file a claim.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body p-4">
                            <span class="blf-step-badge mb-3">3</span>
                            <h3 class="h6 fw-semibold text-navy">{{ __('Verify & Recover') }}</h3>
                            <p class="small text-secondary mb-0">{{ __('Once approved by the custody institution, present valid identification in person to receive your item and an official turnover receipt.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="blf-page-eyebrow mb-1">{{ __('Island-Wide Custody Network') }}</div>
                            <h3 class="h6 fw-semibold text-navy mb-1">{{ __('Covering Boracay Island Stations, Landmarks & Police Desks') }}</h3>
                            <p class="small text-secondary mb-0">{{ __('Station 1 · Station 2 · Station 3 · D\'Mall · Cagban Jetty Port · Boracay Tourist Police Station. All personal contact details and claim evidence are protected under RA 10173.') }}</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="{{ route('public.help') }}" class="btn btn-outline-primary btn-sm px-3">
                                {{ __('Read Help & Privacy Policy') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('homePhotoSearchForm');
    if (!form) return;

    const photoInput = document.getElementById('home_search_photo');
    const typeSelect = document.getElementById('homePhotoScopeType');
    const submitBtn = document.getElementById('homePhotoSubmitBtn');
    const resultsWrap = document.getElementById('homePhotoResultsWrap');
    const statusEl = document.getElementById('homePhotoStatus');
    const gridEl = document.getElementById('homePhotoResultsGrid');
    const clearBtn = document.getElementById('homePhotoClearBtn');

    clearBtn?.addEventListener('click', () => {
        resultsWrap?.classList.add('d-none');
        if (gridEl) gridEl.innerHTML = '';
        if (statusEl) statusEl.textContent = '';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!photoInput?.files || photoInput.files.length === 0) {
            resultsWrap?.classList.remove('d-none');
            if (statusEl) statusEl.textContent = 'Please upload or drop at least one photo first.';
            if (gridEl) gridEl.innerHTML = '';
            return;
        }

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
                <div class="col-md-6 col-lg-4 blf-match-card-enter" style="animation-delay: ${Math.min(idx * 70, 420)}ms;">
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
            submitBtn.innerHTML = '<i class="bi bi-camera me-1" aria-hidden="true"></i>Scan & Find Matches';
        }
    });
});
</script>
@endPushOnce
