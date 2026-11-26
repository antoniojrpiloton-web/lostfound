@extends('layouts.public')

@section('title', __('Report Found Item'))

@section('content')
    <section class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="blf-page-eyebrow">{{ __('Found Item Registration') }}</div>
                            <h1 class="h4 text-navy fw-bold mb-0">{{ __('Register a Found Item') }}</h1>
                        </div>
                        <a href="{{ route('reports.mine') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>{{ __('My Reports') }}
                        </a>
                    </div>

                    <div class="card mb-4 border-primary border-opacity-25">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary mb-1">{{ __('Step 1 of 2 · Smart Pre-Check') }}</span>
                                    <h2 class="h6 text-navy fw-bold mb-0">{{ __('Upload a photo to check if someone already reported it lost') }}</h2>
                                    <p class="small text-secondary mb-0">{{ __('We will scan verified open lost reports so you can see if the owner is already looking for this item.') }}</p>
                                </div>
                                <button type="button" id="skipFoundPrecheckBtn" class="btn btn-sm btn-outline-secondary">
                                    {{ __('Skip to Form') }}
                                </button>
                            </div>

                            <form id="foundPrecheckForm" data-url="{{ route('reports.found.precheck') }}" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <x-dropzone
                                        id="found_precheck_photo"
                                        name="photos[]"
                                        :label="__('Photo(s) of the Found Item')"
                                        :hint="__('JPG, PNG, or WEBP up to 5MB each — drag & drop 1 to 3 clear photos')"
                                        accept="image/jpeg,image/png,image/webp"
                                        :multiple="true"
                                        :max-files="3"
                                        :compact="true"
                                    />
                                </div>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-5">
                                        <label for="found_precheck_keyword" class="form-label small fw-medium">{{ __('Item Name / Hint') }}</label>
                                        <input id="found_precheck_keyword" name="keyword" type="text" class="form-control" placeholder="{{ __('e.g. Black Wallet, iPhone') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="found_precheck_category" class="form-label small fw-medium">{{ __('Category (Optional)') }}</label>
                                        <select id="found_precheck_category" name="category_id" class="form-select" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('Any category') }}">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" id="foundPrecheckSubmitBtn" class="btn btn-primary w-100" style="min-height: 2.75rem;">
                                            <i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Check Now') }}
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div id="foundPrecheckResultBox" class="d-none mt-3 pt-3 border-top">
                                <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                    <img id="foundPrecheckPreviewImg" src="" alt="{{ __('Uploaded photo preview') }}" class="rounded border d-none flex-shrink-0" style="width: 96px; height: 96px; object-fit: cover;">
                                    <div class="flex-grow-1 w-100">
                                        <div id="foundPrecheckStatusBanner"></div>
                                        <div id="foundPrecheckMatchesList" class="mt-2"></div>
                                        <div class="mt-3">
                                            <button type="button" id="proceedFoundReportBtn" class="btn btn-sm btn-primary">
                                                <i class="bi bi-arrow-down-circle me-1" aria-hidden="true"></i>{{ __('Proceed to Register Found Item') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="foundReportFormCard" class="card {{ $errors->any() || old('item_name') ? '' : 'd-none' }}">
                        <form method="POST" action="{{ route('reports.found.store') }}" enctype="multipart/form-data" class="card-body p-4">
                            @csrf

                            <div class="mb-3">
                                <span class="badge bg-primary-subtle text-primary mb-1">{{ __('Step 2 of 2 · Found Item Details') }}</span>
                                <h2 class="h6 text-navy border-bottom pb-2 mb-0">{{ __('Found Item Details') }}</h2>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label for="item_name" class="form-label small fw-medium">{{ __('Item Name') }} <span class="text-danger">*</span></label>
                                    <input id="item_name" name="item_name" type="text" value="{{ old('item_name') }}" class="form-control @error('item_name') is-invalid @enderror" required>
                                    @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-5">
                                    <label for="category_id" class="form-label small fw-medium">{{ __('Category') }} <span class="text-danger">*</span></label>
                                    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('Search category...') }}" required>
                                        <option value=""></option>
                                    </select>
                                    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="brand" class="form-label small fw-medium">{{ __('Brand') }}</label>
                                    <input id="brand" name="brand" type="text" value="{{ old('brand') }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="model" class="form-label small fw-medium">{{ __('Model') }}</label>
                                    <input id="model" name="model" type="text" value="{{ old('model') }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="color" class="form-label small fw-medium">{{ __('Color') }}</label>
                                    <input id="color" name="color" type="text" value="{{ old('color') }}" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label small fw-medium">{{ __('Description') }} <span class="text-danger">*</span></label>
                                <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="distinguishing_features" class="form-label small fw-medium">{{ __('Distinguishing Features') }}</label>
                                <textarea id="distinguishing_features" name="distinguishing_features" rows="2" class="form-control">{{ old('distinguishing_features') }}</textarea>
                            </div>

                            <h2 class="h6 text-navy border-bottom pb-2 mb-3">{{ __('Where Found & Custody Institution') }}</h2>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="occurred_date" class="form-label small fw-medium">{{ __('Date Found') }} <span class="text-danger">*</span></label>
                                    <input id="occurred_date" name="occurred_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('occurred_date', now()->toDateString()) }}" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="occurred_time" class="form-label small fw-medium">{{ __('Time Found') }}</label>
                                    <input id="occurred_time" name="occurred_time" type="time" value="{{ old('occurred_time') }}" class="form-control">
                                </div>
                                <div class="col-md-5">
                                    <label for="location_id" class="form-label small fw-medium">{{ __('Landmark / Nearby Area (Optional)') }}</label>
                                    <select id="location_id" name="location_id" class="form-select" data-select2-url="{{ route('api.select2.locations') }}" data-placeholder="{{ __('Search Station 1, D\'Mall, Cagban, etc...') }}">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="location_details" class="form-label small fw-medium">{{ __('Where Item Was Found (Specific Area / Spot)') }} <span class="text-danger">*</span></label>
                                    <input id="location_details" name="location_details" type="text" value="{{ old('location_details') }}" class="form-control @error('location_details') is-invalid @enderror" placeholder="{{ __('e.g. Beside Station 2 lifeguard tower, restaurant bench, jetty port...') }}" required>
                                    @error('location_details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="custody_organization_id" class="form-label small fw-medium">{{ __('Preferred Police Booth / Supervision Desk (Optional)') }}</label>
                                    <select id="custody_organization_id" name="custody_organization_id" class="form-select @error('custody_organization_id') is-invalid @enderror" data-select2-url="{{ route('api.select2.organizations') }}" data-placeholder="{{ __('Select Police Booth or leave blank for default...') }}">
                                        <option value=""></option>
                                    </select>
                                    @error('custody_organization_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="alert alert-light border small d-flex align-items-start gap-2 mb-3">
                                <i class="bi bi-shield-check text-primary fs-6 flex-shrink-0 mt-1" aria-hidden="true"></i>
                                <div>
                                    <div class="fw-semibold text-navy">{{ __('Keep Item Safely Until Supervised Police Booth Return') }}</div>
                                    <div class="text-secondary">{{ __('You may hold onto the found item for now. Once Police/Admin identifies a matching lost report, they will contact you so the handover happens safely at the Police Booth under officer supervision.') }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <x-dropzone
                                    id="photos"
                                    name="photos[]"
                                    :label="__('Item Photograph')"
                                    :hint="__('At least 1 clear photo is required (JPG, PNG, or WEBP, max 5MB each, up to 3 photos).')"
                                    accept="image/jpeg,image/png,image/webp"
                                    :multiple="true"
                                    :required="true"
                                    :max-files="3"
                                />
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="finder_name" class="form-label small fw-medium">{{ __('Finder Name') }}</label>
                                    <input id="finder_name" name="finder_name" type="text" value="{{ old('finder_name', auth()->user()->name) }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label for="finder_contact" class="form-label small fw-medium">{{ __('Finder Contact Number (Private · For Police Booth Coordination)') }} <span class="text-danger">*</span></label>
                                    <input id="finder_contact" name="finder_contact" type="text" value="{{ old('finder_contact') }}" class="form-control" placeholder="{{ __('e.g. 0917-123-4567') }}" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="small text-secondary">{{ __('Police/Admin will coordinate the supervised booth return once a match is identified.') }}</span>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-box-seam me-1" aria-hidden="true"></i>{{ __('Register Found Item') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('foundPrecheckForm');
    const photoInput = document.getElementById('found_precheck_photo');
    const keywordInput = document.getElementById('found_precheck_keyword');
    const resultBox = document.getElementById('foundPrecheckResultBox');
    const previewImg = document.getElementById('foundPrecheckPreviewImg');
    const statusBanner = document.getElementById('foundPrecheckStatusBanner');
    const matchesList = document.getElementById('foundPrecheckMatchesList');
    const submitBtn = document.getElementById('foundPrecheckSubmitBtn');
    const recordCard = document.getElementById('foundReportFormCard');
    const skipBtn = document.getElementById('skipFoundPrecheckBtn');
    const proceedBtn = document.getElementById('proceedFoundReportBtn');
    const recordPhotos = document.getElementById('photos');
    const recordItemName = document.getElementById('item_name');

    const revealFoundForm = () => {
        if (!recordCard) return;
        recordCard.classList.remove('d-none');
        if (photoInput && photoInput.files && photoInput.files.length > 0 && recordPhotos) {
            try {
                const dt = new DataTransfer();
                Array.from(photoInput.files).forEach((f) => dt.items.add(f));
                recordPhotos.files = dt.files;
                recordPhotos.dispatchEvent(new Event('blf:sync-files'));
            } catch (_) {}
        }
        if (keywordInput && keywordInput.value.trim() !== '' && recordItemName && recordItemName.value.trim() === '') {
            recordItemName.value = keywordInput.value.trim();
        }
        recordCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    if (skipBtn) skipBtn.addEventListener('click', revealFoundForm);
    if (proceedBtn) proceedBtn.addEventListener('click', revealFoundForm);

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const hasPhoto = photoInput && photoInput.files && photoInput.files.length > 0;
            const hasKeyword = keywordInput && keywordInput.value.trim() !== '';
            const categoryEl = document.getElementById('found_precheck_category');
            const hasCategory = categoryEl && categoryEl.value !== '';

            if (!hasPhoto && !hasKeyword && !hasCategory) {
                resultBox.classList.remove('d-none');
                statusBanner.innerHTML = '<div class="alert alert-warning py-2 px-3 small mb-0">Please upload a photo, enter a keyword, or pick a category first.</div>';
                matchesList.innerHTML = '';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning...';
            resultBox.classList.remove('d-none');
            statusBanner.innerHTML = '';

            if (hasPhoto && previewImg) {
                previewImg.src = URL.createObjectURL(photoInput.files[0]);
                previewImg.classList.add('d-none');
            } else if (previewImg) {
                previewImg.classList.add('d-none');
            }

            const scan = (hasPhoto && window.BLFScanner)
                ? window.BLFScanner.startPhotoScan(matchesList, photoInput.files, { minDuration: 1500 })
                : null;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(form.dataset.url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: new FormData(form),
                });

                const data = await response.json();
                if (scan) await scan.finish();
                if (hasPhoto && previewImg) previewImg.classList.remove('d-none');
                resultBox.classList.remove('d-none');

                if (!response.ok) {
                    statusBanner.innerHTML = `<div class="alert alert-danger py-2 px-3 small mb-0">${data.message || 'Unable to run pre-check.'}</div>`;
                    matchesList.innerHTML = '';
                    return;
                }

                let bannerHtml = '';
                if (data.image_check && data.image_check.status === 'rejected') {
                    bannerHtml += `<div class="alert alert-warning py-2 px-3 small mb-2"><strong>Photo Clarity Notice:</strong> ${(data.image_check.messages || []).join(' ')} Visual matches below may be less accurate.</div>`;
                }
                if (hasPhoto && data.visual_search_enabled === false) {
                    bannerHtml += '<div class="alert alert-info py-2 px-3 small mb-2"><strong>Visual Photo Search is OFF:</strong> Enable <code>matching.visual_search_enabled</code> in Admin Settings to match items by photo.</div>';
                }

                if (data.has_matches) {
                    bannerHtml += `<div class="small fw-semibold text-navy mb-2">We found ${data.matches.length} similar open Lost Item report(s) that may match what you found:</div>`;
                    matchesList.innerHTML = '<div class="list-group list-group-flush small border rounded bg-white">' + data.matches.map((item, idx) => {
                        const hasScore = item.score !== null && item.score !== undefined;
                        const tier = hasScore && window.BLFScanner ? window.BLFScanner.scoreTier(item.score) : null;
                        return `
                        <div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 blf-match-card-enter" style="animation-delay: ${Math.min(idx * 70, 420)}ms;">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                ${item.image_url ? `<img src="${item.image_url}" alt="${item.item_name}" class="rounded border flex-shrink-0" style="width: 54px; height: 54px; object-fit: cover;">` : ''}
                                <div class="min-w-0">
                                    <div class="fw-semibold text-navy">${item.item_name} <code class="ms-1 text-primary">${item.reference_no}</code></div>
                                    <div class="text-secondary">${[item.category, item.location || item.location_details, item.occurred_date].filter(Boolean).join(' · ')}</div>
                                    ${hasScore ? `
                                        <div class="d-flex align-items-center gap-2 mt-1" style="max-width: 220px;">
                                            <div class="blf-score-bar flex-grow-1"><div class="blf-score-bar-fill ${tier.barClass}" data-bar-to="${item.score}"></div></div>
                                            <span class="fw-semibold text-navy" style="font-size: 0.72rem;" data-count-to="${item.score}">0%</span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                ${hasScore ? `<span class="badge ${tier.badgeClass}"><span data-count-to="${item.score}">0%</span> visual match</span>` : '<span class="badge bg-secondary-subtle text-secondary-emphasis">Metadata match</span>'}
                                <a href="${item.url}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2">View</a>
                            </div>
                        </div>`;
                    }).join('') + '</div>';
                    window.BLFScanner?.animateMatchCounters(matchesList);
                } else {
                    bannerHtml += '<div class="alert alert-success py-2 px-3 small mb-0">No similar open Lost Item reports found right now. Please proceed to register the found item below so the owner can locate it.</div>';
                    matchesList.innerHTML = '';
                }

                statusBanner.innerHTML = bannerHtml;
            } catch (_) {
                if (scan) await scan.finish();
                resultBox.classList.remove('d-none');
                statusBanner.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">Pre-check failed. You may still proceed to the report form below.</div>';
                matchesList.innerHTML = '';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-search me-1" aria-hidden="true"></i>Check Now';
            }
        });
    }
});
</script>
@endPushOnce
