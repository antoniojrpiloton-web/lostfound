import jQuery from 'jquery';
import select2 from 'select2';

window.$ = window.jQuery = jQuery;
select2(jQuery);

export function initSelect2(root = document) {
    const selects = root.querySelectorAll('select[data-select2-url], select[data-select2="local"]');

    selects.forEach((element) => {
        const $el = jQuery(element);
        if ($el.hasClass('select2-hidden-accessible')) {
            return;
        }

        const modal = element.closest('.modal');
        const url = element.dataset.select2Url;
        const placeholder = element.dataset.placeholder || 'Select an option';
        const allowClear = element.dataset.allowClear !== 'false';
        const dependsSelector = element.dataset.dependsOn;
        const dependsParam = element.dataset.dependsParam || 'parent_id';

        const config = {
            theme: 'bootstrap-5',
            width: '100%',
            placeholder,
            allowClear,
            selectionCssClass: element.classList.contains('form-select-sm') ? 'select2--small' : '',
            dropdownCssClass: element.classList.contains('form-select-sm') ? 'select2--small' : '',
            ...(modal ? { dropdownParent: jQuery(modal) } : {}),
        };

        if (url) {
            config.ajax = {
                url,
                dataType: 'json',
                delay: 250,
                cache: true,
                data: (params) => {
                    const query = {
                        q: params.term || '',
                        page: params.page || 1,
                    };

                    if (dependsSelector) {
                        const parentEl = document.querySelector(dependsSelector);
                        if (parentEl && parentEl.value) {
                            query[dependsParam] = parentEl.value;
                        }
                    }

                    return query;
                },
                processResults: (data) => ({
                    results: data.results || [],
                    pagination: {
                        more: Boolean(data.pagination && data.pagination.more),
                    },
                }),
            };
        }

        $el.select2(config);

        if (dependsSelector) {
            const parentEl = document.querySelector(dependsSelector);
            if (parentEl) {
                jQuery(parentEl).on('change', () => {
                    $el.val(null).trigger('change');
                });
            }
        }
    });
}
