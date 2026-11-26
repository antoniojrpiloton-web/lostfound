const SKIP_TAGS = new Set([
    'SCRIPT',
    'STYLE',
    'NOSCRIPT',
    'CODE',
    'PRE',
    'SVG',
    'PATH',
    'KBD',
    'SAMP',
    'VAR',
    'TEXTAREA',
]);

const SKIP_PATTERN = /^(BLF-[A-Z]+-\d{4}-\d+|CLM-\d{4}-\d+|https?:\/\/\S+|[^\s@]+@[^\s@]+\.[^\s@]+|Ctrl\s*\+\s*K|Ctrl\s+K|[\d\s.,:%+\-–—/·•()|#]+)$/i;

function shouldTranslate(text, locale) {
    const trimmed = (text || '').trim();
    if (trimmed.length < 2 || trimmed.length > 600) {
        return false;
    }
    if (SKIP_PATTERN.test(trimmed)) {
        return false;
    }
    if (['ja', 'ko', 'zh'].includes(locale)) {
        return /[A-Za-z]{2,}/.test(trimmed);
    }
    return /[A-Za-z]{2,}/.test(trimmed);
}

function isInsideSkippedElement(node) {
    let el = node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
    while (el) {
        if (SKIP_TAGS.has(el.tagName)) {
            return true;
        }
        if (el.classList?.contains('notranslate') || el.hasAttribute?.('data-no-translate')) {
            return true;
        }
        if (el.isContentEditable) {
            return true;
        }
        el = el.parentElement;
    }
    return false;
}

export function initAutoTranslate() {
    const locale = (document.documentElement.lang || 'en').toLowerCase().trim();
    if (!locale || locale === 'en' || locale.startsWith('en-')) {
        return;
    }

    const storageKey = `blf_i18n_v1_${locale}`;
    let cache = {};
    try {
        cache = JSON.parse(sessionStorage.getItem(storageKey) || '{}') || {};
    } catch {
        cache = {};
    }

    const translatedNodes = new WeakSet();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let inFlight = false;
    let rescanQueued = false;
    let debounceTimer = null;

    function saveCache() {
        try {
            const keys = Object.keys(cache);
            if (keys.length > 1200) {
                const trimmed = {};
                keys.slice(-900).forEach((k) => {
                    trimmed[k] = cache[k];
                });
                cache = trimmed;
            }
            sessionStorage.setItem(storageKey, JSON.stringify(cache));
        } catch {
        }
    }

    function collectTargets(root = document.body) {
        if (!root) {
            return { textNodes: [], attrTargets: [], missing: [] };
        }

        const textNodes = [];
        const attrTargets = [];
        const missingSet = new Set();

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        let current;
        while ((current = walker.nextNode())) {
            if (translatedNodes.has(current)) {
                continue;
            }
            if (isInsideSkippedElement(current)) {
                continue;
            }
            const raw = current.nodeValue || '';
            const trimmed = raw.trim();
            if (!shouldTranslate(trimmed, locale)) {
                continue;
            }

            if (Object.prototype.hasOwnProperty.call(cache, trimmed)) {
                const replacement = cache[trimmed];
                if (replacement && replacement !== trimmed) {
                    const lead = raw.match(/^\s*/)?.[0] || '';
                    const trail = raw.match(/\s*$/)?.[0] || '';
                    current.nodeValue = `${lead}${replacement}${trail}`;
                }
                translatedNodes.add(current);
            } else {
                textNodes.push({ node: current, original: trimmed, raw });
                missingSet.add(trimmed);
            }
        }

        const attrEls = root.querySelectorAll('input[placeholder], textarea[placeholder], [title]');
        attrEls.forEach((el) => {
            if (isInsideSkippedElement(el)) {
                return;
            }

            ['placeholder', 'title'].forEach((attr) => {
                const doneKey = `blfTr_${attr}`;
                if (el.dataset[doneKey] === locale) {
                    return;
                }
                const val = (el.getAttribute(attr) || '').trim();
                if (!shouldTranslate(val, locale)) {
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(cache, val)) {
                    if (cache[val] && cache[val] !== val) {
                        el.setAttribute(attr, cache[val]);
                    }
                    el.dataset[doneKey] = locale;
                } else {
                    attrTargets.push({ el, attr, original: val, doneKey });
                    missingSet.add(val);
                }
            });
        });

        return {
            textNodes,
            attrTargets,
            missing: Array.from(missingSet),
        };
    }

    async function translatePage() {
        if (inFlight) {
            rescanQueued = true;
            return;
        }

        const { textNodes, attrTargets, missing } = collectTargets(document.body);
        if (missing.length === 0) {
            return;
        }

        inFlight = true;
        try {
            const batches = [];
            for (let i = 0; i < missing.length; i += 60) {
                batches.push(missing.slice(i, i + 60));
            }

            for (const batch of batches) {
                const response = await fetch('/api/translate/batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ locale, texts: batch }),
                });

                if (!response.ok) {
                    continue;
                }

                const payload = await response.json();
                const map = payload?.translations || {};
                Object.entries(map).forEach(([orig, tr]) => {
                    if (typeof tr === 'string' && tr.trim() !== '') {
                        cache[orig] = tr;
                    }
                });
            }

            saveCache();

            textNodes.forEach(({ node, original, raw }) => {
                if (!node.isConnected) {
                    return;
                }
                const replacement = cache[original];
                if (replacement && replacement !== original) {
                    const lead = raw.match(/^\s*/)?.[0] || '';
                    const trail = raw.match(/\s*$/)?.[0] || '';
                    node.nodeValue = `${lead}${replacement}${trail}`;
                }
                translatedNodes.add(node);
            });

            attrTargets.forEach(({ el, attr, original, doneKey }) => {
                if (!el.isConnected) {
                    return;
                }
                const replacement = cache[original];
                if (replacement && replacement !== original) {
                    el.setAttribute(attr, replacement);
                }
                el.dataset[doneKey] = locale;
            });
        } catch {
        } finally {
            inFlight = false;
            if (rescanQueued) {
                rescanQueued = false;
                scheduleScan();
            }
        }
    }

    function scheduleScan() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(() => {
            translatePage();
        }, 120);
    }

    translatePage();

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.addedNodes.length > 0) {
                scheduleScan();
                return;
            }
        }
    });

    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    }
}
