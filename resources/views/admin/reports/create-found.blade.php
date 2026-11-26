@extends('layouts.admin')

@section('eyebrow', __('Operations'))
@section('title', __('Register Found Item (Desk Custody)'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Register Found Item')]]" />
@endsection

@section('content')
    <div class="blf-panel-card p-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <span class="blf-status-pill blf-pill-info mb-1">{{ __('Step 1 · Smart Pre-Check') }}</span>
                <h2 class="h6 fw-semibold text-navy mb-0">{{ __('Upload Photo to Check for Similar Open Lost Items') }}</h2>
            </div>
            <button type="button" class="btn btn-sm btn-light border text-secondary" id="skipPrecheckBtn">
                {{ __('Skip to Recording Form') }}
            </button>
        </div>

        <form id="foundPrecheckForm" data-url="{{ route('admin.found.precheck') }}" enctype="multipart/form-data">
            <div class="mb-3">
                <x-dropzone
                    id="precheck_photo"
                    name="photos[]"
                    :label="__('Item Photos (up to 3)')"
                    :hint="__('Drag & drop 1 to 3 clear photos of the found item (JPG, PNG, WEBP, max 5MB each)')"
                    accept="image/jpeg,image/png,image/webp"
                    :multiple="true"
                    :max-files="3"
                    :required="true"
                    :compact="true"
                />
            </div>
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="precheck_keyword" class="form-label small fw-medium">{{ __('Quick Item Hint (Optional)') }}</label>
                    <input id="precheck_keyword" name="keyword" type="text" class="form-control form-control-sm" placeholder="{{ __('e.g. Blue iPhone, Wallet') }}">
                </div>
                <div class="col-md-4">
                    <label for="precheck_category" class="form-label small fw-medium">{{ __('Category (Optional)') }}</label>
                    <select id="precheck_category" name="category_id" class="form-select form-select-sm" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('Any category') }}">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" id="precheckSubmitBtn" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Check Now') }}
                    </button>
                </div>
            </div>
        </form>

        <div id="precheckResultBox" class="mt-3 d-none">
            <div class="d-flex flex-wrap align-items-start gap-3 p-3 rounded-3 bg-light border">
                <img id="precheckPreviewImg" src="" alt="" class="rounded border d-none" style="width: 88px; height: 88px; object-fit: cover;">
                <div class="flex-grow-1">
                    <div id="precheckStatusBanner" class="mb-2"></div>
                    <div id="precheckMatchesList"></div>
                    <div class="mt-2">
                        <button type="button" id="proceedRecordBtn" class="btn btn-sm btn-success">
                            <i class="bi bi-arrow-down-circle me-1" aria-hidden="true"></i>{{ __('Proceed to Record Found Item') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="deskRecordCard" class="blf-panel-card p-4 {{ $errors->any() ? '' : 'd-none' }}">
        <div class="mb-3">
            <span class="blf-status-pill blf-pill-primary mb-1">{{ __('Step 2 · Desk Custody Recording') }}</span>
            <h2 class="h6 fw-semibold text-navy mb-0">{{ __('Complete Found Item Record') }}</h2>
        </div>

        <form method="POST" action="{{ route('admin.found.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="item_name" class="form-label small fw-medium">{{ __('Item Name') }} <span class="text-danger">*</span></label>
                    <input id="item_name" name="item_name" type="text" value="{{ old('item_name') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label for="category_id" class="form-label small fw-medium">{{ __('Category') }} <span class="text-danger">*</span></label>
                    <select id="category_id" name="category_id" class="form-select form-select-sm" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('Search category...') }}" required>
                        <option value=""></option>
                    </select>
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
                <textarea id="description" name="description" rows="3" class="form-control form-control-sm" required>{{ old('description') }}</textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="occurred_date" class="form-label small fw-medium">{{ __('Date Found') }} <span class="text-danger">*</span></label>
                    <input id="occurred_date" name="occurred_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('occurred_date', now()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="location_id" class="form-label small fw-medium">{{ __('Landmark / Area (Optional)') }}</label>
                    <select id="location_id" name="location_id" class="form-select" data-select2-url="{{ route('api.select2.locations') }}" data-placeholder="{{ __('Search Station 1, D\'Mall, etc...') }}">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="custody_organization_id" class="form-label small fw-medium">{{ __('Custody Institution') }}</label>
                    <select id="custody_organization_id" name="custody_organization_id" class="form-select" data-select2-url="{{ route('api.select2.organizations') }}" data-placeholder="{{ __('Search institution...') }}">
                        <option value=""></option>
                        @if ($organizations->count() === 1)
                            <option value="{{ $organizations->first()->id }}" selected>{{ $organizations->first()->name }}</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="location_details" class="form-label small fw-medium">{{ __('Where Item Was Found (Specific Area / Spot)') }}</label>
                <input id="location_details" name="location_details" type="text" value="{{ old('location_details') }}" class="form-control" placeholder="{{ __('e.g. White Beach Station 2 beside lifeguard tower, hotel lobby, jetty waiting area...') }}">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="custody_stage" class="form-label small fw-medium">{{ __('Initial Surrender Location') }}</label>
                    <select id="custody_stage" name="custody_stage" class="form-select">
                        <option value="at_booth" @selected(old('custody_stage', 'at_booth') === 'at_booth')>{{ __('At Police Beach Booth / Tent Stand') }}</option>
                        <option value="at_station" @selected(old('custody_stage') === 'at_station')>{{ __('Directly at Police Station (Safekeeping)') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="booth_location" class="form-label small fw-medium">{{ __('Police Booth / Tent Stand Name (If at Booth)') }}</label>
                    <input id="booth_location" name="booth_location" type="text" value="{{ old('booth_location') }}" class="form-control" placeholder="{{ __('e.g. Station 2 Beachfront Police Tent, Station 1 Booth') }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="finder_name" class="form-label small fw-medium">{{ __('Finder / Surrenderer Name') }} <span class="text-danger">*</span></label>
                    <input id="finder_name" name="finder_name" type="text" value="{{ old('finder_name') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label for="finder_contact" class="form-label small fw-medium">{{ __('Finder Contact (Encrypted)') }}</label>
                    <input id="finder_contact" name="finder_contact" type="text" value="{{ old('finder_contact') }}" class="form-control form-control-sm">
                </div>
            </div>

            <div class="mb-3">
                <x-dropzone
                    id="photos"
                    name="photos[]"
                    :label="__('Item Photograph')"
                    :hint="__('Drag & drop up to 3 clear photos (JPG, PNG, or WEBP, max 5MB each)')"
                    accept="image/jpeg,image/png,image/webp"
                    :multiple="true"
                    :max-files="3"
                />
                <div class="form-text small text-primary" id="copiedPhotoHint"></div>
            </div>

            @if (auth()->user()?->hasPermission('report.photo_exception'))
                <div class="mb-4">
                    <label for="photo_exception_reason" class="form-label small fw-medium">{{ __('Admin Photo Exception Reason (Optional — only if no photo is attached)') }}</label>
                    <input id="photo_exception_reason" name="photo_exception_reason" type="text" value="{{ old('photo_exception_reason') }}" class="form-control form-control-sm" placeholder="{{ __('e.g. Sensitive government ID stored in sealed envelope') }}">
                </div>
            @endif

            <div class="text-end">
                <button type="submit" class="btn btn-sm btn-primary px-4">
                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>{{ __('Register in Custody') }}
                </button>
            </div>
        </form>
    </div>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('foundPrecheckForm');
    const photoInput = document.getElementById('precheck_photo');
    const keywordInput = document.getElementById('precheck_keyword');
    const submitBtn = document.getElementById('precheckSubmitBtn');
    const resultBox = document.getElementById('precheckResultBox');
    const previewImg = document.getElementById('precheckPreviewImg');
    const statusBanner = document.getElementById('precheckStatusBanner');
    const matchesList = document.getElementById('precheckMatchesList');
    const recordCard = document.getElementById('deskRecordCard');
    const proceedBtn = document.getElementById('proceedRecordBtn');
    const skipBtn = document.getElementById('skipPrecheckBtn');
    const mainPhotosInput = document.getElementById('photos');
    const itemNameInput = document.getElementById('item_name');
    const copiedHint = document.getElementById('copiedPhotoHint');

    const revealRecordingForm = () => {
        recordCard.classList.remove('d-none');
        if (keywordInput.value && !itemNameInput.value) {
            itemNameInput.value = keywordInput.value;
        }
        if (photoInput.files && photoInput.files.length > 0 && typeof DataTransfer !== 'undefined') {
            const dt = new DataTransfer();
            Array.from(photoInput.files).forEach((f) => dt.items.add(f));
            mainPhotosInput.files = dt.files;
            mainPhotosInput.dispatchEvent(new Event('blf:sync-files'));
            copiedHint.textContent = 'Pre-checked photo(s) attached automatically.';
        }
        recordCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    skipBtn?.addEventListener('click', revealRecordingForm);
    proceedBtn?.addEventListener('click', revealRecordingForm);

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!photoInput.files || photoInput.files.length === 0) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning...';
        resultBox.classList.remove('d-none');
        statusBanner.innerHTML = '';
        previewImg.classList.add('d-none');

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
        };
        reader.readAsDataURL(photoInput.files[0]);

        const scan = window.BLFScanner
            ? window.BLFScanner.startPhotoScan(matchesList, photoInput.files, { minDuration: 1500 })
            : null;

        try {
            const fd = new FormData(form);
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(form.dataset.url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const data = await res.json();
            if (scan) await scan.finish();
            previewImg.classList.remove('d-none');

            if (!res.ok) {
                statusBanner.innerHTML = `<div class="alert alert-danger py-2 px-3 small mb-0">${data.message || 'Unable to check photo.'}</div>`;
                matchesList.innerHTML = '';
                return;
            }

            if (data.image_check?.status === 'rejected') {
                const msg = (data.image_check.messages || ['Photo failed quality check.']).join(' ');
                statusBanner.innerHTML = `<div class="alert alert-danger py-2 px-3 small mb-0">${msg}</div>`;
                matchesList.innerHTML = '';
                return;
            }

            if (data.has_matches) {
                statusBanner.innerHTML = `<div class="alert alert-warning py-2 px-3 small mb-2"><strong>${data.matches.length} similar open lost item(s) found.</strong> Review below before recording a new found item.</div>`;
                const itemsHtml = data.matches.map((m, idx) => {
                    const hasScore = m.score !== null && m.score !== undefined;
                    const tier = hasScore && window.BLFScanner ? window.BLFScanner.scoreTier(m.score) : null;
                    return `
                    <div class="d-flex justify-content-between align-items-center bg-white border rounded p-2 mb-2 small gap-2 blf-match-card-enter" style="animation-delay: ${Math.min(idx * 70, 420)}ms;">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            ${m.image_url ? `<img src="${m.image_url}" alt="${m.item_name}" class="rounded border flex-shrink-0" style="width: 56px; height: 56px; object-fit: cover;">` : ''}
                            <div class="min-w-0">
                                <strong class="text-navy">${m.item_name}</strong>
                                <code class="ms-1 text-primary">${m.reference_no}</code>
                                <div class="text-secondary">${[m.category, m.location || m.location_details, m.occurred_date].filter(Boolean).join(' · ')}</div>
                                ${hasScore ? `
                                    <div class="d-flex align-items-center gap-2 mt-1" style="max-width: 220px;">
                                        <div class="blf-score-bar flex-grow-1"><div class="blf-score-bar-fill ${tier.barClass}" data-bar-to="${m.score}"></div></div>
                                        <span class="fw-semibold text-navy" style="font-size: 0.72rem;" data-count-to="${m.score}">0%</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            ${hasScore ? `<span class="badge ${tier.badgeClass}"><span data-count-to="${m.score}">0%</span> visual</span>` : '<span class="badge bg-light text-secondary border">Metadata</span>'}
                            <a href="${m.url}" target="_blank" class="btn btn-sm btn-outline-primary">Inspect</a>
                        </div>
                    </div>`;
                }).join('');
                matchesList.innerHTML = itemsHtml;
                window.BLFScanner?.animateMatchCounters(matchesList);
            } else {
                statusBanner.innerHTML = `<div class="alert alert-success py-2 px-3 small mb-0">No similar open lost items found. You may proceed to record this found item below.</div>`;
                matchesList.innerHTML = '';
                revealRecordingForm();
            }
        } catch (err) {
            if (scan) await scan.finish();
            statusBanner.innerHTML = `<div class="alert alert-danger py-2 px-3 small mb-0">Network error while checking similar items.</div>`;
            matchesList.innerHTML = '';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-search me-1" aria-hidden="true"></i>Check Now';
        }
    });
});
</script>
@endPushOnce
