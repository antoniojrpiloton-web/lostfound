@extends('layouts.admin')

@section('eyebrow', __('Intelligence'))
@section('title', __('Item Matching Queue'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Item Matches')]]" />
@endsection

@section('actions')
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-light bg-white border text-navy px-3" data-open-smart-search="photo">
            <i class="bi bi-camera me-1" aria-hidden="true"></i>{{ __('Browse by Photo') }}
        </button>
        <a href="{{ route('public.items.index') }}" target="_blank" class="btn btn-sm btn-light bg-white border text-navy px-3">
            <i class="bi bi-grid me-1" aria-hidden="true"></i>{{ __('Browse Items') }}
        </a>
        <form id="runMatchingScanForm" method="POST" action="{{ route('admin.matches.scan') }}" class="m-0">
            @csrf
            <button type="submit" id="runMatchingScanBtn" class="btn btn-sm btn-primary px-3">
                <i class="bi bi-cpu me-1" aria-hidden="true"></i>{{ __('Run Matching Scan') }}
            </button>
        </form>
    </div>
@endsection

@section('content')
    <div id="matchingScanFeedback" class="d-none mb-3"></div>

    <div class="blf-panel-card overflow-hidden">
        <form id="matchesFilterForm" method="GET" action="{{ route('admin.matches.index') }}" class="blf-table-toolbar">
            <div class="small text-secondary">
                <i class="bi bi-info-circle me-1 text-ocean" aria-hidden="true"></i>
                {{ __('Matches are ranked by weighted metadata & visual similarity. Ownership is never auto-confirmed.') }}
            </div>
            <div class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">{{ __('All Match Statuses') }}</option>
                    @foreach (App\Enums\MatchStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.matches') }}"
                data-datatable-filter="#matchesFilterForm"
                data-datatable-total="{{ $matches->total() }}"
                data-datatable-order-col="1"
                data-datatable-order-dir="desc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Score') }}</th>
                        <th>{{ __('Lost Report') }}</th>
                        <th>{{ __('Found Report') }}</th>
                        <th>{{ __('Matching Factors') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matches as $match)
                        @php
                            $pct = (int) round(((float) $match->score) * 100);
                            $scoreTone = $pct >= 75 ? 'blf-pill-success' : 'blf-pill-warning';
                            $lostImg = $match->lostReport?->images?->sortByDesc('is_primary')->first();
                            $foundImg = $match->foundReport?->images?->sortByDesc('is_primary')->first();
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                @can('review', $match)
                                    <form method="POST" action="{{ route('admin.matches.decide', $match) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="possible">
                                        <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Possible') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.matches.decide', $match) }}" class="d-inline" data-confirm="{{ __('Dismiss this suggested match?') }}" data-confirm-title="{{ __('Dismiss Match') }}" data-confirm-button="{{ __('Dismiss') }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="dismissed">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Dismiss') }}</button>
                                    </form>
                                @else
                                    <span class="small text-secondary">{{ __('Admin required') }}</span>
                                @endcan
                            </td>
                            <td>
                                <span class="blf-status-pill {{ $scoreTone }}">{{ $pct }}%</span>
                                <div class="text-secondary mt-1" style="font-size: 0.68rem;">{{ $match->algorithm_version }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($lostImg)
                                        <a href="#matchPhotoModal" data-bs-toggle="modal" data-preview-src="{{ route('admin.images.preview', $lostImg) }}" data-preview-title="{{ $match->lostReport?->item_name }} (Lost)" class="flex-shrink-0">
                                            <img src="{{ route('admin.images.preview', $lostImg) }}" alt="" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                        </a>
                                    @endif
                                    <div class="min-w-0">
                                        @if ($match->lostReport)
                                            <a href="{{ route('admin.reports.show', $match->lostReport) }}" class="fw-semibold text-navy text-decoration-none">{{ $match->lostReport->item_name }}</a>
                                            <div><code class="small text-primary">{{ $match->lostReport->reference_no }}</code></div>
                                            <div class="small text-secondary">{{ $match->lostReport->location?->name ?: ($match->lostReport->location_details ?: 'Boracay Island') }} · {{ $match->lostReport->occurred_date?->format('M d, Y') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($foundImg)
                                        <a href="#matchPhotoModal" data-bs-toggle="modal" data-preview-src="{{ route('admin.images.preview', $foundImg) }}" data-preview-title="{{ $match->foundReport?->item_name }} (Found)" class="flex-shrink-0">
                                            <img src="{{ route('admin.images.preview', $foundImg) }}" alt="" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                        </a>
                                    @endif
                                    <div class="min-w-0">
                                        @if ($match->foundReport)
                                            <a href="{{ route('admin.reports.show', $match->foundReport) }}" class="fw-semibold text-navy text-decoration-none">{{ $match->foundReport->item_name }}</a>
                                            <div><code class="small text-primary">{{ $match->foundReport->reference_no }}</code></div>
                                            <div class="small text-secondary">{{ $match->foundReport->location?->name ?: ($match->foundReport->location_details ?: 'Boracay Island') }} · {{ $match->foundReport->occurred_date?->format('M d, Y') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach (($match->factors ?? []) as $factor => $val)
                                        <span class="badge text-bg-light border text-secondary fw-normal" style="font-size: 0.7rem;">
                                            {{ $factor }}: {{ (int) round($val * 100) }}%
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td><x-status-badge :status="$match->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="matchCompareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy mb-0">
                        <i class="bi bi-layout-split text-primary me-1" aria-hidden="true"></i>{{ __('Side-by-Side Match Comparison') }}
                        <span id="compareScoreBadge" class="badge bg-primary-subtle text-primary ms-2"></span>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-3" id="matchCompareBody"></div>
                    <div class="mt-3 pt-3 border-top">
                        <div class="small fw-semibold text-navy mb-2">{{ __('Matching Score Factors') }}</div>
                        <div id="matchCompareFactors" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="matchPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy mb-0" id="matchPhotoModalTitle">{{ __('Photo Preview') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-2 text-center bg-dark">
                    <img id="matchPhotoModalImg" src="" alt="" class="img-fluid rounded" style="max-height: 75vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const compareModal = document.getElementById('matchCompareModal');
    const compareBody = document.getElementById('matchCompareBody');
    const compareFactors = document.getElementById('matchCompareFactors');
    const compareScoreBadge = document.getElementById('compareScoreBadge');

    compareModal?.addEventListener('show.bs.modal', (event) => {
        const raw = event.relatedTarget?.getAttribute('data-compare');
        if (!raw || !compareBody) return;
        try {
            const data = JSON.parse(raw);
            if (compareScoreBadge) {
                compareScoreBadge.textContent = `${data.score}% Match`;
            }
            const renderCard = (label, tone, item) => {
                if (!item) return '';
                return `
                <div class="col-md-6">
                    <div class="border rounded-3 bg-white h-100 overflow-hidden d-flex flex-column">
                        <div class="px-3 py-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <span class="badge ${tone}">${label}</span>
                            <code class="small text-primary">${item.ref}</code>
                        </div>
                        <div class="bg-light border-bottom text-center" style="height: 240px;">
                            ${item.photo
                                ? `<img src="${item.photo}" alt="${item.name}" class="w-100 h-100" style="object-fit: contain; background: #f8fafc;">`
                                : '<div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary small">No Photo</div>'}
                        </div>
                        <div class="p-3 small flex-grow-1 d-flex flex-column">
                            <h3 class="h6 fw-bold text-navy mb-2">${item.name}</h3>
                            <div class="text-secondary mb-1"><strong>Category:</strong> ${item.category}</div>
                            <div class="text-secondary mb-1"><strong>Brand / Model / Color:</strong> ${item.meta}</div>
                            <div class="text-secondary mb-1"><strong>Location:</strong> ${item.location}</div>
                            <div class="text-secondary mb-2"><strong>Date:</strong> ${item.date}</div>
                            <p class="text-secondary mb-3 flex-grow-1">${item.description || '—'}</p>
                            <a href="${item.url}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Open Full ${label} Report
                            </a>
                        </div>
                    </div>
                </div>`;
            };
            compareBody.innerHTML = renderCard('Lost Item', 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', data.lost)
                + renderCard('Found Item', 'bg-primary-subtle text-primary border border-primary-subtle', data.found);

            if (compareFactors) {
                compareFactors.innerHTML = Object.entries(data.factors || {}).map(([k, v]) =>
                    `<span class="badge bg-light text-navy border fw-normal px-2 py-1">${k}: <strong>${Math.round(Number(v) * 100)}%</strong></span>`
                ).join('');
            }
        } catch (_) {}
    });

    const modal = document.getElementById('matchPhotoModal');
    const img = document.getElementById('matchPhotoModalImg');
    const title = document.getElementById('matchPhotoModalTitle');
    modal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        const src = trigger?.getAttribute('data-preview-src');
        const label = trigger?.getAttribute('data-preview-title');
        if (src && img) img.src = src;
        if (label && title) title.textContent = label;
    });

    const scanForm = document.getElementById('runMatchingScanForm');
    const scanBtn = document.getElementById('runMatchingScanBtn');
    const feedback = document.getElementById('matchingScanFeedback');
    const filterForm = document.getElementById('matchesFilterForm');
    const scanPairs = @json($scanPreviewPairs ?? []);

    scanForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!scanBtn) return;

        scanBtn.disabled = true;
        scanBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning...';

        let cycleTimer = null;
        let rafId = null;
        const startedAt = performance.now();

        if (feedback) {
            feedback.classList.remove('d-none');
            const firstPair = scanPairs[0] || null;
            const firstLost = firstPair?.lost || null;
            const firstFound = firstPair?.found || null;

            feedback.innerHTML = `
                <div class="blf-scan-stage">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-auto d-flex align-items-center justify-content-center gap-3">
                            <div class="text-center" style="width: 136px;">
                                <div class="blf-scan-frame mx-auto" style="width: 132px; height: 132px;">
                                    ${firstLost ? `<img id="batchScanLostImg" src="${firstLost.url}" alt="${firstLost.name}" class="blf-scan-img">` : '<div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary"><i class="bi bi-image fs-3 opacity-50"></i></div>'}
                                    <div class="blf-scan-laser"></div>
                                    <span class="blf-scan-corner tl"></span><span class="blf-scan-corner tr"></span>
                                    <span class="blf-scan-corner bl"></span><span class="blf-scan-corner br"></span>
                                </div>
                                <div class="small fw-semibold text-navy text-truncate mt-1" id="batchScanLostName">${firstLost ? firstLost.name : 'Lost Items'}</div>
                                <div class="text-secondary" style="font-size: 0.7rem;" id="batchScanLostRef">${firstLost ? firstLost.ref : 'Open Lost'}</div>
                            </div>

                            <div class="text-center px-1">
                                <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-arrow-left-right text-primary"></i>
                                </div>
                                <div class="small fw-semibold text-primary mt-1" id="batchScanPairPct" style="font-variant-numeric: tabular-nums;">0%</div>
                            </div>

                            <div class="text-center" style="width: 136px;">
                                <div class="blf-scan-frame mx-auto" style="width: 132px; height: 132px;">
                                    ${firstFound ? `<img id="batchScanFoundImg" src="${firstFound.url}" alt="${firstFound.name}" class="blf-scan-img">` : '<div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary"><i class="bi bi-image fs-3 opacity-50"></i></div>'}
                                    <div class="blf-scan-laser" style="animation-delay: 350ms;"></div>
                                    <span class="blf-scan-corner tl"></span><span class="blf-scan-corner tr"></span>
                                    <span class="blf-scan-corner bl"></span><span class="blf-scan-corner br"></span>
                                </div>
                                <div class="small fw-semibold text-navy text-truncate mt-1" id="batchScanFoundName">${firstFound ? firstFound.name : 'Found Items'}</div>
                                <div class="text-secondary" style="font-size: 0.7rem;" id="batchScanFoundRef">${firstFound ? firstFound.ref : 'In Custody'}</div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <div class="fw-semibold text-navy" id="batchScanHeadline">Scanning verified Lost &amp; Found reports...</div>
                                <span class="fw-semibold text-primary" id="batchScanPct" style="font-variant-numeric: tabular-nums;">0%</span>
                            </div>
                            <div class="small text-secondary mb-2">Comparing metadata (category, brand, color, location, date) and OpenCLIP visual vectors</div>
                            <div class="progress mb-3" style="height: 5px; border-radius: 999px; background: #e2e8f0;">
                                <div class="progress-bar bg-primary" id="batchScanBar" style="width: 4%; transition: width 0.25s linear;"></div>
                            </div>
                            <div class="border-top pt-2">
                                <div class="blf-scan-step-row is-active" id="batchStep1">
                                    <span class="spinner-border spinner-border-sm text-primary" style="width: 0.8rem; height: 0.8rem;"></span>
                                    <span>Loading active verified Lost &amp; Found reports</span>
                                </div>
                                <div class="blf-scan-step-row" id="batchStep2">
                                    <i class="bi bi-circle text-secondary opacity-50"></i>
                                    <span>Scoring visual &amp; metadata similarity pairs</span>
                                </div>
                                <div class="blf-scan-step-row" id="batchStep3">
                                    <i class="bi bi-circle text-secondary opacity-50"></i>
                                    <span>Refreshing Item Matching Queue</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const lostEl = document.getElementById('batchScanLostImg');
            const lostNameEl = document.getElementById('batchScanLostName');
            const lostRefEl = document.getElementById('batchScanLostRef');
            const foundEl = document.getElementById('batchScanFoundImg');
            const foundNameEl = document.getElementById('batchScanFoundName');
            const foundRefEl = document.getElementById('batchScanFoundRef');
            const pctEl = document.getElementById('batchScanPct');
            const pairPctEl = document.getElementById('batchScanPairPct');
            const barEl = document.getElementById('batchScanBar');
            const headlineEl = document.getElementById('batchScanHeadline');
            const step1 = document.getElementById('batchStep1');
            const step2 = document.getElementById('batchStep2');
            const step3 = document.getElementById('batchStep3');

            let pairIdx = 0;
            if (scanPairs.length > 1) {
                cycleTimer = setInterval(() => {
                    pairIdx++;
                    const pair = scanPairs[pairIdx % scanPairs.length];
                    if (pair?.lost) {
                        if (lostEl) lostEl.src = pair.lost.url;
                        if (lostNameEl) lostNameEl.textContent = pair.lost.name;
                        if (lostRefEl) lostRefEl.textContent = pair.lost.ref;
                    }
                    if (pair?.found) {
                        if (foundEl) foundEl.src = pair.found.url;
                        if (foundNameEl) foundNameEl.textContent = pair.found.name;
                        if (foundRefEl) foundRefEl.textContent = pair.found.ref;
                    }
                }, 700);
            }

            let step2Marked = false;
            const tick = (now) => {
                const elapsed = now - startedAt;
                const pct = Math.min(92, Math.round((1 - Math.exp(-elapsed / 6500)) * 92));
                if (pctEl) pctEl.textContent = `${pct}%`;
                if (pairPctEl) pairPctEl.textContent = `${pct}%`;
                if (barEl) barEl.style.width = `${Math.max(5, pct)}%`;

                if (!step2Marked && elapsed > 1200) {
                    step2Marked = true;
                    if (step1) {
                        step1.classList.remove('is-active');
                        step1.classList.add('is-done');
                        step1.firstElementChild.outerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                    }
                    if (step2) {
                        step2.classList.add('is-active');
                        step2.firstElementChild.outerHTML = '<span class="spinner-border spinner-border-sm text-primary" style="width: 0.8rem; height: 0.8rem;"></span>';
                    }
                    if (headlineEl) headlineEl.textContent = 'Scoring candidate Lost & Found pairs...';
                }

                if (pct < 92) {
                    rafId = requestAnimationFrame(tick);
                }
            };
            rafId = requestAnimationFrame(tick);
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(scanForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();

            if (cycleTimer) clearInterval(cycleTimer);
            if (rafId) cancelAnimationFrame(rafId);

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Unable to run matching scan.');
            }

            const pctEl = document.getElementById('batchScanPct');
            const pairPctEl = document.getElementById('batchScanPairPct');
            const barEl = document.getElementById('batchScanBar');
            const step2 = document.getElementById('batchStep2');
            const step3 = document.getElementById('batchStep3');
            if (pctEl) pctEl.textContent = '100%';
            if (pairPctEl) pairPctEl.textContent = '100%';
            if (barEl) barEl.style.width = '100%';
            [step2, step3].forEach((el) => {
                if (el) {
                    el.classList.remove('is-active');
                    el.classList.add('is-done');
                    el.firstElementChild.outerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                }
            });

            filterForm?.dispatchEvent(new Event('submit', { cancelable: true }));

            setTimeout(() => {
                if (feedback) {
                    feedback.innerHTML = `<div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-0" role="alert"><i class="bi bi-check-circle-fill me-1"></i>${data.message}<button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                }
            }, 650);
        } catch (err) {
            if (cycleTimer) clearInterval(cycleTimer);
            if (rafId) cancelAnimationFrame(rafId);
            if (feedback) {
                feedback.innerHTML = `<div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-0" role="alert"><i class="bi bi-exclamation-octagon-fill me-1"></i>${err.message || 'Matching scan failed. Please try again.'}<button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
            }
        } finally {
            scanBtn.disabled = false;
            scanBtn.innerHTML = '<i class="bi bi-cpu me-1" aria-hidden="true"></i>Run Matching Scan';
        }
    });
});
</script>
@endPushOnce
