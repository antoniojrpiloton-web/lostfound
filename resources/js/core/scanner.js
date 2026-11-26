export function scoreTier(score) {
    const num = Number(score) || 0;
    if (num >= 85) {
        return {
            badgeClass: 'bg-success-subtle text-success-emphasis border border-success-subtle',
            barClass: 'bg-success',
            label: 'Strong Match',
        };
    }
    if (num >= 70) {
        return {
            badgeClass: 'bg-primary-subtle text-primary border border-primary-subtle',
            barClass: 'bg-primary',
            label: 'Likely Match',
        };
    }
    return {
        badgeClass: 'bg-light text-secondary border',
        barClass: 'bg-secondary',
        label: 'Possible Match',
    };
}

export function startPhotoScan(container, files, options = {}) {
    const minDuration = options.minDuration ?? 1700;
    const startedAt = performance.now();
    const fileArray = Array.from(files || []).slice(0, 5);
    const isMulti = fileArray.length > 1;
    const urls = [];

    const framesHtml = fileArray.length > 0
        ? fileArray.map((file, idx) => {
            const url = URL.createObjectURL(file);
            urls.push(url);
            return `
                <div class="blf-scan-frame ${isMulti ? 'is-multi' : ''}">
                    <img src="${url}" alt="Uploaded photo" class="blf-scan-img">
                    <div class="blf-scan-laser" style="animation-delay: ${idx * 220}ms;"></div>
                    <span class="blf-scan-corner tl"></span>
                    <span class="blf-scan-corner tr"></span>
                    <span class="blf-scan-corner bl"></span>
                    <span class="blf-scan-corner br"></span>
                </div>
            `;
        }).join('')
        : `
            <div class="blf-scan-frame d-flex align-items-center justify-content-center text-secondary">
                <i class="bi bi-image fs-1 opacity-50"></i>
                <div class="blf-scan-laser"></div>
            </div>
        `;

    if (container) {
        container.classList.remove('d-none');
        container.innerHTML = `
            <div class="col-12">
                <div class="blf-scan-stage">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-auto d-flex flex-wrap align-items-center justify-content-center gap-3">
                            ${framesHtml}
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <div class="fw-semibold text-navy" data-scan-step-label>
                                    Checking image clarity &amp; lighting...
                                </div>
                                <span class="fw-semibold text-primary" style="font-variant-numeric: tabular-nums;" data-scan-pct>0%</span>
                            </div>

                            <div class="text-secondary small mb-3">
                                Scanning ${fileArray.length || 1} uploaded photo${fileArray.length > 1 ? 's' : ''} against Boracay Lost &amp; Found records
                            </div>

                            <div class="progress mb-3" style="height: 5px; border-radius: 999px; background: #e2e8f0;">
                                <div class="progress-bar bg-primary" data-scan-progress style="width: 6%; transition: width 0.2s linear; border-radius: 999px;"></div>
                            </div>

                            <div class="border-top pt-2">
                                <div class="blf-scan-step-row is-active" data-scan-chip="1">
                                    <span class="spinner-border spinner-border-sm text-primary" style="width: 0.85rem; height: 0.85rem;"></span>
                                    <span>Checking photo clarity, exposure &amp; subject</span>
                                </div>
                                <div class="blf-scan-step-row" data-scan-chip="2">
                                    <i class="bi bi-circle text-secondary opacity-50"></i>
                                    <span>Analyzing item visual features</span>
                                </div>
                                <div class="blf-scan-step-row" data-scan-chip="3">
                                    <i class="bi bi-circle text-secondary opacity-50"></i>
                                    <span>Comparing with verified listings</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    const stepLabel = container?.querySelector('[data-scan-step-label]');
    const progressBar = container?.querySelector('[data-scan-progress]');
    const pctEl = container?.querySelector('[data-scan-pct]');
    const chip1 = container?.querySelector('[data-scan-chip="1"]');
    const chip2 = container?.querySelector('[data-scan-chip="2"]');
    const chip3 = container?.querySelector('[data-scan-chip="3"]');

    const markDone = (chip) => {
        if (!chip) return;
        chip.classList.remove('is-active');
        chip.classList.add('is-done');
        const icon = chip.firstElementChild;
        if (icon) icon.outerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
    };

    const markActive = (chip) => {
        if (!chip) return;
        chip.classList.add('is-active');
        const icon = chip.firstElementChild;
        if (icon) icon.outerHTML = '<span class="spinner-border spinner-border-sm text-primary" style="width: 0.85rem; height: 0.85rem;"></span>';
    };

    let rafId = null;
    const animateProgress = (now) => {
        const elapsed = now - startedAt;
        const pct = Math.min(96, Math.round((elapsed / minDuration) * 96));
        if (progressBar) progressBar.style.width = `${pct}%`;
        if (pctEl) pctEl.textContent = `${pct}%`;
        if (pct < 96) {
            rafId = requestAnimationFrame(animateProgress);
        }
    };
    rafId = requestAnimationFrame(animateProgress);

    const t1 = setTimeout(() => {
        markDone(chip1);
        markActive(chip2);
        if (stepLabel) stepLabel.textContent = 'Analyzing item visual features...';
    }, Math.round(minDuration * 0.34));

    const t2 = setTimeout(() => {
        markDone(chip2);
        markActive(chip3);
        if (stepLabel) stepLabel.textContent = 'Comparing with verified listings...';
    }, Math.round(minDuration * 0.68));

    return {
        async finish() {
            const elapsed = performance.now() - startedAt;
            const remaining = Math.max(0, minDuration - elapsed);
            if (remaining > 0) {
                await new Promise((resolve) => setTimeout(resolve, remaining));
            }
            if (rafId) cancelAnimationFrame(rafId);
            clearTimeout(t1);
            clearTimeout(t2);
            if (progressBar) progressBar.style.width = '100%';
            if (pctEl) pctEl.textContent = '100%';
            markDone(chip3);
            await new Promise((resolve) => setTimeout(resolve, 160));
            urls.forEach((u) => {
                try {
                    URL.revokeObjectURL(u);
                } catch (_) {}
            });
        },
    };
}

export function animateMatchCounters(container) {
    if (!container) return;

    const counters = container.querySelectorAll('[data-count-to]');
    const duration = 850;
    const startTime = performance.now();

    const tick = (now) => {
        const progress = Math.min(1, (now - startTime) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);

        counters.forEach((el) => {
            const target = parseInt(el.getAttribute('data-count-to') || '0', 10);
            const suffix = el.getAttribute('data-count-suffix') ?? '%';
            el.textContent = `${Math.round(target * eased)}${suffix}`;
        });

        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    };

    if (counters.length > 0) {
        requestAnimationFrame(tick);
    }

    requestAnimationFrame(() => {
        container.querySelectorAll('[data-bar-to]').forEach((bar) => {
            const target = Math.max(0, Math.min(100, parseInt(bar.getAttribute('data-bar-to') || '0', 10)));
            bar.style.width = `${target}%`;
        });
    });
}
