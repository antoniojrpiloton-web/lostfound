import { startPhotoScan, animateMatchCounters, scoreTier } from './scanner';

export function initSmartSearch() {
    const modalEl = document.getElementById('blfSmartSearchModal');
    if (!modalEl || !window.bootstrap) {
        return;
    }

    const endpoint = modalEl.getAttribute('data-smart-search-url');
    const dialogEl = document.getElementById('blfSmartSearchDialog');
    const inputView = document.getElementById('smartSearchInputView');
    const resultsHeader = document.getElementById('smartResultsHeader');
    const resultsHeadline = document.getElementById('smartResultsHeadline');
    const backBtn = document.getElementById('smartBackToSearchBtn');
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    const textInput = document.getElementById('smartSearchInput');
    const typeSelect = document.getElementById('smartSearchType');
    const scopeSelect = document.getElementById('smartSearchScope');
    const statusEl = document.getElementById('smartSearchStatus');
    const resultsEl = document.getElementById('smartSearchResults');
    const photoForm = document.getElementById('smartPhotoForm');
    const photoInput = document.getElementById('smart_search_photo');
    const photoType = document.getElementById('smartPhotoType');
    const photoSubmitBtn = document.getElementById('smartPhotoSubmitBtn');

    const enterExpandedResultsMode = (count) => {
        dialogEl?.classList.remove('modal-lg');
        dialogEl?.classList.add('modal-xl');
        inputView?.classList.add('d-none');
        resultsHeader?.classList.remove('d-none');
        if (resultsHeadline) {
            resultsHeadline.textContent = `${count} similar item(s) found — ranked by visual similarity`;
        }
        if (statusEl) {
            statusEl.textContent = '';
        }
    };

    const exitExpandedResultsMode = () => {
        dialogEl?.classList.remove('modal-xl');
        dialogEl?.classList.add('modal-lg');
        inputView?.classList.remove('d-none');
        resultsHeader?.classList.add('d-none');
        resultsEl?.classList.add('d-none');
        if (statusEl) {
            statusEl.textContent = '';
        }
    };

    backBtn?.addEventListener('click', exitExpandedResultsMode);
    modalEl.addEventListener('hidden.bs.modal', exitExpandedResultsMode);

    document.querySelectorAll('[data-open-smart-search]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const mode = trigger.getAttribute('data-open-smart-search');
            modal.show();
            if (mode === 'photo') {
                const photoTab = document.getElementById('smartTabPhoto');
                if (photoTab) {
                    window.bootstrap.Tab.getOrCreateInstance(photoTab).show();
                }
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            modal.show();
        }
    });

    modalEl.addEventListener('shown.bs.modal', () => {
        if (textInput && document.getElementById('smartPaneText')?.classList.contains('active')) {
            textInput.focus();
        }
    });

    const renderResults = (items, emptyText, expandMode = false) => {
        if (!resultsEl || !statusEl) return;

        if (!items || items.length === 0) {
            inputView?.classList.remove('d-none');
            resultsHeader?.classList.add('d-none');
            dialogEl?.classList.remove('modal-xl');
            dialogEl?.classList.add('modal-lg');
            statusEl.textContent = '';
            resultsEl.classList.remove('d-none');
            resultsEl.innerHTML = `<div class="col-12">
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-0 py-3 px-3 small border border-warning-subtle" style="border-radius: 0.75rem;">
                    <i class="bi bi-search text-warning-emphasis fs-5 flex-shrink-0" aria-hidden="true"></i>
                    <div>
                        <div class="fw-semibold text-warning-emphasis">${emptyText}</div>
                        <div class="text-body-secondary mt-1">Try uploading another photo/angle, switching the search scope, or searching by keyword instead.</div>
                    </div>
                </div>
            </div>`;
            return;
        }

        if (expandMode) {
            enterExpandedResultsMode(items.length);
        } else {
            dialogEl?.classList.remove('modal-lg');
            dialogEl?.classList.add('modal-xl');
            statusEl.textContent = `Found ${items.length} matching item(s):`;
        }

        resultsEl.classList.remove('d-none');
        const qualityWarnings = expandMode
            ? (photoForm?.querySelector('[data-dropzone-root]')?.blfQualityWarnings || [])
            : [];
        const qualityNoticeHtml = qualityWarnings.length > 0
            ? `<div class="col-12">
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-1 py-2 px-3 small" style="border-radius: 0.65rem;">
                    <i class="bi bi-exclamation-triangle-fill text-warning-emphasis fs-6 flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <div>
                        <div class="fw-semibold text-warning-emphasis">Note: Low Image Quality Detected</div>
                        <div class="text-body-secondary">Because the uploaded photo appears blurry, too dark, overexposed, or lacks a clear subject, the predicted matches below may be inaccurate. Try uploading a clearer photo for more reliable results.</div>
                    </div>
                </div>
            </div>`
            : '';

        resultsEl.innerHTML = qualityNoticeHtml + items.map((item, idx) => {
            let breakdownHtml = '';
            if (item.breakdown) {
                if (item.breakdown.engine === 'exact') {
                    breakdownHtml = '<div class="mt-2 d-flex flex-wrap gap-1" style="font-size: 0.72rem;"><span class="badge bg-success-subtle text-success-emphasis">Exact Photo Match (100%)</span></div>';
                } else if (item.breakdown.color !== undefined) {
                    breakdownHtml = `<div class="mt-2 d-flex flex-wrap gap-1" style="font-size: 0.72rem;">
                        <span class="badge bg-light text-secondary border">Color: <strong class="text-navy" data-count-to="${item.breakdown.color}">0%</strong></span>
                        <span class="badge bg-light text-secondary border">Shape &amp; Edges: <strong class="text-navy" data-count-to="${item.breakdown.shape}">0%</strong></span>
                        <span class="badge bg-light text-secondary border">Layout: <strong class="text-navy" data-count-to="${item.breakdown.layout}">0%</strong></span>
                    </div>`;
                } else if (item.breakdown.semantic !== undefined) {
                    breakdownHtml = `<div class="mt-2 d-flex flex-wrap gap-1" style="font-size: 0.72rem;">
                        <span class="badge bg-light text-secondary border">AI Semantic Vision: <strong class="text-navy" data-count-to="${item.breakdown.semantic}">0%</strong></span>
                    </div>`;
                }
            }

            const hasScore = item.score !== null && item.score !== undefined;
            const tier = hasScore ? scoreTier(item.score) : null;

            const thumbHtml = item.image_url
                ? `<img src="${item.image_url}" alt="${item.item_name}" class="w-100 h-100" style="object-fit: cover;">`
                : `<div class="w-100 h-100 d-flex align-items-center justify-content-center text-primary opacity-50" style="background: #eff6ff;"><i class="bi bi-image fs-1" aria-hidden="true"></i></div>`;

            return `
            <div class="col-md-6 col-lg-4 blf-match-card-enter" style="animation-delay: ${Math.min(idx * 70, 420)}ms;">
                <div class="card h-100 border shadow-sm overflow-hidden" style="border-radius: 0.85rem;">
                    <div class="position-relative" style="height: 170px; background: #f8fbff;">
                        ${thumbHtml}
                        <span class="position-absolute top-0 start-0 m-2 badge bg-white text-navy border shadow-sm">${item.status_label}</span>
                        ${hasScore ? `<span class="position-absolute top-0 end-0 m-2 badge bg-primary shadow-sm"><span data-count-to="${item.score}">0%</span> Match</span>` : ''}
                    </div>
                    <div class="card-body p-3 d-flex flex-column">
                        ${hasScore ? `
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.72rem;">
                                    <span class="badge ${tier.badgeClass}">${tier.label}</span>
                                    <strong class="text-navy" data-count-to="${item.score}">0%</strong>
                                </div>
                                <div class="blf-score-bar">
                                    <div class="blf-score-bar-fill ${tier.barClass}" data-bar-to="${item.score}"></div>
                                </div>
                            </div>
                        ` : ''}
                        <div class="small fw-bold text-navy text-truncate" title="${item.item_name}">${item.item_name}</div>
                        <div class="mb-1"><code class="small text-primary">${item.reference_no}</code></div>
                        <div class="text-secondary mb-2" style="font-size: 0.75rem;">
                            ${[item.type_label, item.category, item.location, item.occurred_date].filter(Boolean).join(' · ')}
                        </div>
                        ${item.description ? `<p class="text-secondary mb-2 flex-grow-1" style="font-size: 0.76rem;">${item.description}</p>` : '<div class="flex-grow-1"></div>'}
                        ${breakdownHtml}
                        <div class="d-flex gap-2 mt-3 pt-2 border-top">
                            <a href="${item.url}" class="btn btn-sm btn-outline-primary flex-grow-1">View Details</a>
                            ${item.claim_url ? `<a href="${item.claim_url}" class="btn btn-sm btn-primary flex-grow-1">Claim</a>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        }).join('');

        animateMatchCounters(resultsEl);
    };

    let debounceTimer = null;
    const runTextSearch = async () => {
        const q = (textInput?.value || '').trim();
        if (q.length < 2) {
            resultsEl?.classList.add('d-none');
            if (statusEl) statusEl.textContent = 'Type at least 2 characters to search across Lost & Found records.';
            return;
        }

        if (statusEl) statusEl.textContent = 'Searching...';

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const formData = new FormData();
        formData.append('q', q);
        if (typeSelect?.value) formData.append('type', typeSelect.value);
        if (scopeSelect?.value) formData.append('scope', scopeSelect.value);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: formData,
            });
            const data = await response.json();
            renderResults(data.items || [], 'No matching items found for that query.');
        } catch (_) {
            if (statusEl) statusEl.textContent = 'Unable to run search right now.';
        }
    };

    textInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runTextSearch, 250);
    });
    typeSelect?.addEventListener('change', runTextSearch);
    scopeSelect?.addEventListener('change', runTextSearch);

    photoForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!photoInput?.files || photoInput.files.length === 0) {
            if (statusEl) statusEl.textContent = 'Please drop or select a photo first.';
            return;
        }

        if (photoSubmitBtn) {
            photoSubmitBtn.disabled = true;
            photoSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning...';
        }
        if (statusEl) statusEl.textContent = '';
        dialogEl?.classList.remove('modal-lg');
        dialogEl?.classList.add('modal-xl');
        inputView?.classList.add('d-none');
        resultsHeader?.classList.add('d-none');

        const scan = startPhotoScan(resultsEl, photoInput.files, { minDuration: 1550 });

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const formData = new FormData();
        Array.from(photoInput.files).forEach((file) => formData.append('photos[]', file));
        if (photoType?.value) formData.append('type', photoType.value);
        const photoScope = document.getElementById('smartPhotoScope');
        if (photoScope?.value) formData.append('scope', photoScope.value);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: formData,
            });
            const data = await response.json();
            await scan.finish();

            if (data.error) {
                exitExpandedResultsMode();
                if (statusEl) statusEl.textContent = data.error;
            } else {
                renderResults(data.items || [], 'No visually similar verified items found.', true);
            }
        } catch (_) {
            await scan.finish();
            exitExpandedResultsMode();
            if (statusEl) statusEl.textContent = 'Photo deep search failed. Please try again.';
        } finally {
            if (photoSubmitBtn) {
                photoSubmitBtn.disabled = false;
                photoSubmitBtn.innerHTML = '<i class="bi bi-camera me-1" aria-hidden="true"></i>Run Photo Deep Search';
            }
        }
    });
}
