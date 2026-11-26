function formatBytes(bytes) {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / (1024 ** i)).toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
}

export function initDropzones() {
    document.querySelectorAll('[data-dropzone-root]').forEach((root) => {
        const area = root.querySelector('[data-dropzone-area]');
        const input = root.querySelector('[data-dropzone-input]');
        const previews = root.querySelector('[data-dropzone-previews]');
        if (!area || !input || !previews) {
            return;
        }

        const isMultiple = input.hasAttribute('multiple');
        const maxFiles = parseInt(root.getAttribute('data-max-files') || '0', 10);
        const validateUrl = root.getAttribute('data-validate-url') || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const validationCache = new WeakMap();
        let storedFiles = [];

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const buildStatusHtml = (state) => {
            if (!state) return '';
            if (state.loading) {
                return '<div class="mt-1 small text-primary"><span class="spinner-border spinner-border-sm me-1" style="width:0.7rem;height:0.7rem;"></span>Checking clarity &amp; subject...</div>';
            }
            if (state.status === 'passed') {
                const conf = state.metrics?.object_confidence ? ` · Subject ${state.metrics.object_confidence}%` : '';
                return `<div class="mt-1 small text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i>Clear &amp; recognizable${escapeHtml(conf)}</div>`;
            }
            if (state.status === 'rejected') {
                const reason = (state.messages && state.messages.length > 0)
                    ? state.messages.join(' ')
                    : 'Photo rejected. Please upload a clearer photo of the item.';
                return `<div class="mt-1 small text-danger fw-medium"><i class="bi bi-exclamation-octagon-fill me-1"></i>${escapeHtml(reason)}</div>`;
            }
            if (state.status === 'review') {
                const reason = (state.messages && state.messages.length > 0)
                    ? state.messages.join(' ')
                    : 'Borderline quality — will require manual review.';
                return `<div class="mt-1 small text-warning-emphasis fw-medium"><i class="bi bi-exclamation-triangle-fill me-1"></i>${escapeHtml(reason)}</div>`;
            }
            return '';
        };

        const runLiveValidation = async (file) => {
            if (!validateUrl || !file.type || !file.type.startsWith('image/')) {
                return;
            }
            if (validationCache.has(file)) {
                return;
            }

            validationCache.set(file, { loading: true });
            renderPreviews();

            try {
                const fd = new FormData();
                fd.append('image', file);
                const form = root.closest('form');
                const categoryInput = form?.querySelector('[name="category_id"]');
                if (categoryInput && categoryInput.value) {
                    fd.append('category_id', categoryInput.value);
                }

                const response = await fetch(validateUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });

                if (!response.ok) {
                    validationCache.set(file, {
                        loading: false,
                        status: 'review',
                        messages: ['Unable to pre-check image quality right now.'],
                    });
                } else {
                    const data = await response.json();
                    validationCache.set(file, {
                        loading: false,
                        status: data.status || 'review',
                        messages: Array.isArray(data.messages) ? data.messages : [],
                        metrics: data.metrics || {},
                    });
                }
            } catch (_) {
                validationCache.set(file, {
                    loading: false,
                    status: 'review',
                    messages: [],
                });
            }

            renderPreviews();
        };

        const syncInput = () => {
            try {
                const dt = new DataTransfer();
                storedFiles.forEach((file) => dt.items.add(file));
                input.files = dt.files;
            } catch (_) {
            }
        };

        const syncQualityBanner = () => {
            const warnings = [];
            storedFiles.forEach((file) => {
                const state = validationCache.get(file);
                if (state && !state.loading && (state.status === 'rejected' || state.status === 'review')) {
                    warnings.push({
                        name: file.name,
                        status: state.status,
                        messages: Array.isArray(state.messages) ? state.messages : [],
                    });
                }
            });

            root.blfQualityWarnings = warnings;
            root.dispatchEvent(new CustomEvent('blf:quality-changed', { bubbles: true, detail: { warnings } }));

            let banner = root.querySelector('[data-dropzone-quality-banner]');
            if (warnings.length === 0) {
                if (banner) banner.remove();
                return;
            }

            if (!banner) {
                banner = document.createElement('div');
                banner.setAttribute('data-dropzone-quality-banner', '');
                banner.className = 'alert alert-warning d-flex align-items-start gap-2 mt-2 mb-0 py-2 px-3 small';
                banner.style.borderRadius = '0.65rem';
                previews.insertAdjacentElement('afterend', banner);
            }

            const hasRejected = warnings.some((w) => w.status === 'rejected');
            const reasons = [...new Set(warnings.flatMap((w) => w.messages).filter(Boolean))];
            const reasonSummary = reasons.length > 0 ? ` (${reasons.join(' ')})` : '';

            banner.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill text-warning-emphasis fs-6 flex-shrink-0 mt-1" aria-hidden="true"></i>
                <div>
                    <div class="fw-semibold text-warning-emphasis">${hasRejected ? 'Low Image Quality Detected — Predictions May Be Inaccurate' : 'Borderline Image Quality — Match Accuracy May Vary'}</div>
                    <div class="text-body-secondary mt-1">
                        One or more selected photos may be blurry, too dark, overexposed, or have an unclear subject${escapeHtml(reasonSummary)}.
                        Visual search results and AI similarity scores may contain inaccurate matches — for best results, use a sharp, well-lit photo focused on the item.
                    </div>
                </div>
            `;
        };

        const renderPreviews = () => {
            previews.innerHTML = '';
            storedFiles.forEach((file, index) => {
                const state = validationCache.get(file);
                const item = document.createElement('div');
                item.className = 'blf-dropzone-item';
                if (state && !state.loading && state.status === 'rejected') {
                    item.classList.add('border-danger', 'bg-danger-subtle');
                } else if (state && !state.loading && state.status === 'passed') {
                    item.classList.add('border-success');
                } else if (state && !state.loading && state.status === 'review') {
                    item.classList.add('border-warning');
                }

                if (file.type && file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.className = 'blf-dropzone-thumb';
                    img.alt = file.name;
                    img.src = URL.createObjectURL(file);
                    item.appendChild(img);
                } else {
                    const icon = document.createElement('div');
                    icon.className = 'blf-dropzone-file-icon';
                    icon.innerHTML = '<i class="bi bi-file-earmark-pdf"></i>';
                    item.appendChild(icon);
                }

                const meta = document.createElement('div');
                meta.className = 'blf-dropzone-meta';
                meta.innerHTML = `
                    <div class="blf-dropzone-name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
                    <div class="blf-dropzone-size">${formatBytes(file.size)}</div>
                    ${buildStatusHtml(state)}
                `;
                item.appendChild(meta);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'blf-dropzone-remove';
                removeBtn.setAttribute('aria-label', `Remove ${file.name}`);
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    storedFiles.splice(index, 1);
                    syncInput();
                    renderPreviews();
                });
                item.appendChild(removeBtn);

                previews.appendChild(item);
            });

            syncQualityBanner();
        };

        const addFiles = (fileList) => {
            const incoming = Array.from(fileList || []);
            if (incoming.length === 0) {
                return;
            }

            if (isMultiple) {
                storedFiles = [...storedFiles, ...incoming];
                if (maxFiles > 0 && storedFiles.length > maxFiles) {
                    storedFiles = storedFiles.slice(0, maxFiles);
                }
            } else {
                storedFiles = [incoming[0]];
            }

            syncInput();
            renderPreviews();
            storedFiles.forEach((file) => runLiveValidation(file));
        };

        input.addEventListener('change', () => {
            storedFiles = Array.from(input.files || []);
            if (maxFiles > 0 && storedFiles.length > maxFiles) {
                storedFiles = storedFiles.slice(0, maxFiles);
                syncInput();
            }
            renderPreviews();
            storedFiles.forEach((file) => runLiveValidation(file));
        });

        input.addEventListener('blf:sync-files', () => {
            storedFiles = Array.from(input.files || []);
            renderPreviews();
            storedFiles.forEach((file) => runLiveValidation(file));
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            area.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                area.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            area.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                area.classList.remove('is-dragover');
            });
        });

        area.addEventListener('drop', (event) => {
            const files = event.dataTransfer?.files;
            if (files && files.length > 0) {
                addFiles(files);
            }
        });
    });
}
