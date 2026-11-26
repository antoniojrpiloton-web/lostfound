@extends('layouts.public')

@section('title', __('Report Lost Item'))

@section('content')
    <section class="py-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="blf-page-eyebrow">{{ __('Lost Item Reporting') }}</div>
                            <h1 class="h4 text-navy fw-bold mb-0">{{ __('Report a Lost Item') }}</h1>
                        </div>
                        <a href="{{ route('reports.mine') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>{{ __('My Reports') }}
                        </a>
                    </div>

                    <div class="blf-panel-card p-4 mb-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div>
                                <span class="blf-status-pill blf-pill-info mb-1">{{ __('Step 1 · Smart Pre-Check') }}</span>
                                <h2 class="h6 fw-semibold text-navy mb-0">{{ __('Upload a Photo or Keyword First to Check Found Items') }}</h2>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border text-secondary" id="skipLostPrecheckBtn">
                                {{ __('Skip to Lost Report Form') }}
                            </button>
                        </div>

                        <p class="small text-secondary mb-3">
                            {{ __('Before filing a new lost report, check if your item has already been turned in to a Boracay Police Booth or Police Station.') }}
                        </p>

                        <form id="lostPrecheckForm" data-url="{{ route('reports.lost.precheck') }}" enctype="multipart/form-data">
                            <div class="mb-3">
                                <x-dropzone
                                    id="lost_precheck_photo"
                                    name="photos[]"
                                    :label="__('Item Photos (Optional, up to 3)')"
                                    :hint="__('JPG, PNG, or WEBP up to 5MB each — drag & drop 1 to 3 photos of your lost item')"
                                    accept="image/jpeg,image/png,image/webp"
                                    :multiple="true"
                                    :max-files="3"
                                    :compact="true"
                                />
                            </div>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="lost_precheck_keyword" class="form-label small fw-medium">{{ __('Item Name / Hint') }}</label>
                                    <input id="lost_precheck_keyword" name="keyword" type="text" class="form-control form-control-sm" placeholder="{{ __('e.g. Black Wallet, iPhone') }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="lost_precheck_category" class="form-label small fw-medium">{{ __('Category (Optional)') }}</label>
                                    <select id="lost_precheck_category" name="category_id" class="form-select form-select-sm" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('Any category') }}">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" id="lostPrecheckSubmitBtn" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Check Now') }}
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="lostPrecheckResultBox" class="mt-3 d-none">
                            <div class="d-flex flex-wrap align-items-start gap-3 p-3 rounded-3 bg-light border">
                                <img id="lostPrecheckPreviewImg" src="" alt="" class="rounded border d-none" style="width: 88px; height: 88px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div id="lostPrecheckStatusBanner" class="mb-2"></div>
                                    <div id="lostPrecheckMatchesList"></div>
                                    <div class="mt-2">
                                        <button type="button" id="proceedLostReportBtn" class="btn btn-sm btn-success">
                                            <i class="bi bi-arrow-down-circle me-1" aria-hidden="true"></i>{{ __('Not Listed — Proceed to Report Lost Item') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="lostReportFormCard" class="card {{ $errors->any() ? '' : 'd-none' }}">
                        <form method="POST" action="{{ route('reports.lost.store') }}" enctype="multipart/form-data" class="card-body p-4">
                            @csrf

                            <div class="mb-3">
                                <span class="blf-status-pill blf-pill-primary mb-1">{{ __('Step 2 · Lost Item Report') }}</span>
                                <h2 class="h6 text-navy border-bottom pb-2 mb-0">{{ __('Item Details') }}</h2>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label for="item_name" class="form-label small fw-medium">{{ __('Item Name') }} <span class="text-danger">*</span></label>
                                    <input id="item_name" name="item_name" type="text" value="{{ old('item_name') }}" class="form-control form-control-sm @error('item_name') is-invalid @enderror" placeholder="{{ __('e.g. Black Leather Wallet, iPhone 15 Pro') }}" required>
                                    @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-5">
                                    <label for="category_id" class="form-label small fw-medium">{{ __('Category') }} <span class="text-danger">*</span></label>
                                    <select id="category_id" name="category_id" class="form-select form-select-sm @error('category_id') is-invalid @enderror" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('Search category...') }}" required>
                                        <option value=""></option>
                                        @if (old('category_id'))
                                            @php $oldCat = $categories->firstWhere('id', (int) old('category_id')); @endphp
                                            @if ($oldCat)
                                                <option value="{{ $oldCat->id }}" selected>{{ $oldCat->parent ? $oldCat->parent->label().' — ' : '' }}{{ $oldCat->label() }}</option>
                                            @endif
                                        @endif
                                    </select>
                                    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="brand" class="form-label small fw-medium">{{ __('Brand') }}</label>
                                    <input id="brand" name="brand" type="text" value="{{ old('brand') }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label for="model" class="form-label small fw-medium">{{ __('Model') }}</label>
                                    <input id="model" name="model" type="text" value="{{ old('model') }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label for="color" class="form-label small fw-medium">{{ __('Color') }}</label>
                                    <input id="color" name="color" type="text" value="{{ old('color') }}" class="form-control form-control-sm">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label small fw-medium">{{ __('Description') }} <span class="text-danger">*</span></label>
                                <textarea id="description" name="description" rows="3" class="form-control form-control-sm @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <label for="distinguishing_features" class="form-label small fw-medium">{{ __('Distinguishing Characteristics') }}</label>
                                <textarea id="distinguishing_features" name="distinguishing_features" rows="2" class="form-control form-control-sm" placeholder="{{ __('Scratches, stickers, engravings, case color, or contents') }}">{{ old('distinguishing_features') }}</textarea>
                            </div>

                            <h2 class="h6 text-navy border-bottom pb-2 mb-3">{{ __('When & Where Lost') }}</h2>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="occurred_date" class="form-label small fw-medium">{{ __('Date Lost') }} <span class="text-danger">*</span></label>
                                    <input id="occurred_date" name="occurred_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('occurred_date', now()->toDateString()) }}" class="form-control @error('occurred_date') is-invalid @enderror" required>
                                    @error('occurred_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="occurred_time" class="form-label small fw-medium">{{ __('Approximate Time') }}</label>
                                    <input id="occurred_time" name="occurred_time" type="time" value="{{ old('occurred_time') }}" class="form-control">
                                </div>
                                <div class="col-md-5">
                                    <label for="location_id" class="form-label small fw-medium">{{ __('Landmark / Nearby Area (Optional)') }}</label>
                                    <select id="location_id" name="location_id" class="form-select" data-select2-url="{{ route('api.select2.locations') }}" data-placeholder="{{ __('Search Station 1, D\'Mall, Cagban, etc...') }}">
                                        <option value=""></option>
                                        @if (old('location_id'))
                                            @php $oldLoc = $locations->firstWhere('id', (int) old('location_id')); @endphp
                                            @if ($oldLoc)
                                                <option value="{{ $oldLoc->id }}" selected>{{ $oldLoc->name }}</option>
                                            @endif
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                    <label for="location_details" id="lostLocationDetailsLabel" class="form-label small fw-medium mb-0">
                                        {{ __('Where Lost / Last Remembered Location') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" id="notSureLocationToggle">
                                        <label class="form-check-label small text-secondary" for="notSureLocationToggle">
                                            {{ __('Not sure where lost (enter last remembered place)') }}
                                        </label>
                                    </div>
                                </div>
                                <input id="location_details" name="location_details" type="text" value="{{ old('location_details') }}" class="form-control @error('location_details') is-invalid @enderror" placeholder="{{ __('e.g. Near Station 2 beachfront, D\'Mall entrance, or last place you remember having it') }}" required>
                                @error('location_details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <h2 class="h6 text-navy border-bottom pb-2 mb-3">{{ __('Optional Photo, Reward & Contact Preferences') }}</h2>

                            <div class="mb-3">
                                <x-dropzone
                                    id="photos"
                                    name="photos[]"
                                    :label="__('Optional Photographs (up to 3, JPG/PNG/WEBP, max 5MB each)')"
                                    :hint="__('Drag & drop up to 3 photos, or submit without a photo if unavailable.')"
                                    accept="image/jpeg,image/png,image/webp"
                                    :multiple="true"
                                    :max-files="3"
                                />
                            </div>

                            <div class="row g-3 mb-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input id="reward_offered" name="reward_offered" type="checkbox" value="1" class="form-check-input" @checked(old('reward_offered'))>
                                        <label for="reward_offered" class="form-check-label small fw-medium">{{ __('Offer a Reward (Optional)') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <input id="reward_details" name="reward_details" type="text" value="{{ old('reward_details') }}" class="form-control form-control-sm" placeholder="{{ __('Reward note (no online payment is processed by this system)') }}">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="contact_phone" class="form-label small fw-medium">{{ __('Contact Number (Kept Private & Encrypted)') }}</label>
                                    <input id="contact_phone" name="contact_phone" type="text" value="{{ old('contact_phone') }}" class="form-control form-control-sm" placeholder="+63 9XX XXX XXXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium d-block">{{ __('Contact Preferences') }}</label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="prefer_in_app" id="prefer_in_app" value="1" @checked(old('prefer_in_app', true))>
                                            <label class="form-check-label small" for="prefer_in_app">{{ __('In-app') }}</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="prefer_email" id="prefer_email" value="1" @checked(old('prefer_email', true))>
                                            <label class="form-check-label small" for="prefer_email">{{ __('Email') }}</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="prefer_phone" id="prefer_phone" value="1" @checked(old('prefer_phone'))>
                                            <label class="form-check-label small" for="prefer_phone">{{ __('Phone') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="remarks" class="form-label small fw-medium">{{ __('Additional Remarks') }}</label>
                                <textarea id="remarks" name="remarks" rows="2" class="form-control form-control-sm">{{ old('remarks') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="small text-secondary">{{ __('Your report will be reviewed by the assigned institution before public listing.') }}</span>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-send me-1" aria-hidden="true"></i>{{ __('Submit Lost Report') }}
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
    const form = document.getElementById('lostPrecheckForm');
    const photoInput = document.getElementById('lost_precheck_photo');
    const keywordInput = document.getElementById('lost_precheck_keyword');
    const resultBox = document.getElementById('lostPrecheckResultBox');
    const previewImg = document.getElementById('lostPrecheckPreviewImg');
    const statusBanner = document.getElementById('lostPrecheckStatusBanner');
    const matchesList = document.getElementById('lostPrecheckMatchesList');
    const submitBtn = document.getElementById('lostPrecheckSubmitBtn');
    const recordCard = document.getElementById('lostReportFormCard');
    const skipBtn = document.getElementById('skipLostPrecheckBtn');
    const proceedBtn = document.getElementById('proceedLostReportBtn');
    const recordPhotos = document.getElementById('photos');
    const recordItemName = document.getElementById('item_name');

    const revealLostForm = () => {
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

    if (skipBtn) skipBtn.addEventListener('click', revealLostForm);
    if (proceedBtn) proceedBtn.addEventListener('click', revealLostForm);

    const notSureToggle = document.getElementById('notSureLocationToggle');
    const locationDetailsInput = document.getElementById('location_details');
    const locationDetailsLabel = document.getElementById('lostLocationDetailsLabel');
    notSureToggle?.addEventListener('change', () => {
        if (!locationDetailsInput || !locationDetailsLabel) return;
        if (notSureToggle.checked) {
            locationDetailsLabel.innerHTML = 'Last Remembered Location <span class="text-danger">*</span>';
            locationDetailsInput.placeholder = 'Last place you remember still having the item (e.g. at a Station 2 café, inside e-trike, hotel lobby)';
            if (locationDetailsInput.value.trim() === '') {
                locationDetailsInput.value = 'Last remembered: ';
            }
            locationDetailsInput.focus();
        } else {
            locationDetailsLabel.innerHTML = 'Where Lost / Last Remembered Location <span class="text-danger">*</span>';
            locationDetailsInput.placeholder = 'e.g. Near Station 2 beachfront, D\'Mall entrance, or last place you remember having it';
            if (locationDetailsInput.value.trim() === 'Last remembered:') {
                locationDetailsInput.value = '';
            }
        }
    });

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const hasPhoto = photoInput && photoInput.files && photoInput.files.length > 0;
            const hasKeyword = keywordInput && keywordInput.value.trim() !== '';
            const categoryEl = document.getElementById('lost_precheck_category');
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
                    bannerHtml += `<div class="small fw-semibold text-navy mb-2">We found ${data.matches.length} similar verified Found Item(s) already in custody:</div>`;
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
                                <a href="${item.claim_url}" class="btn btn-sm btn-primary py-0 px-2">Claim</a>
                            </div>
                        </div>`;
                    }).join('') + '</div>';
                    window.BLFScanner?.animateMatchCounters(matchesList);
                } else {
                    bannerHtml += '<div class="alert alert-success py-2 px-3 small mb-0">No matching Found Items in custody right now. You may proceed to file your Lost Item Report below.</div>';
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
