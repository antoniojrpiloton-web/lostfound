import $ from 'jquery';
import DataTable from 'datatables.net-bs5';
import { initConfirmDialogs } from './core-confirm-hook';

export function initDataTables() {
    document.querySelectorAll('table[data-datatable-url]').forEach((tableEl) => {
        if (tableEl.dataset.dtInitialized === '1') {
            return;
        }
        tableEl.dataset.dtInitialized = '1';

        const url = tableEl.getAttribute('data-datatable-url');
        const filterSelector = tableEl.getAttribute('data-datatable-filter');
        const filterForm = filterSelector ? document.querySelector(filterSelector) : null;
        const orderCol = parseInt(tableEl.getAttribute('data-datatable-order-col') || '1', 10);
        const orderDir = tableEl.getAttribute('data-datatable-order-dir') || 'desc';
        const pageLength = parseInt(tableEl.getAttribute('data-datatable-page-length') || '15', 10);
        const deferTotal = parseInt(tableEl.getAttribute('data-datatable-total') || '-1', 10);

        const config = {
            processing: true,
            serverSide: true,
            pageLength,
            lengthMenu: [10, 15, 25, 50, 100],
            order: [[orderCol, orderDir]],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-nowrap' },
            ],
            ajax: {
                url,
                type: 'GET',
                data: (params) => {
                    if (filterForm) {
                        const fd = new FormData(filterForm);
                        fd.forEach((value, key) => {
                            params[key] = value;
                        });
                    }
                    return params;
                },
            },
            language: {
                search: '',
                searchPlaceholder: 'Search table...',
                lengthMenu: 'Show _MENU_',
                processing: '<span class="spinner-border spinner-border-sm text-primary me-1"></span>Loading...',
            },
        };

        const hasEmptyStateRow = tableEl.querySelector('tbody td[colspan]') !== null;
        if (deferTotal >= 0 && !hasEmptyStateRow) {
            config.deferLoading = deferTotal;
        } else if (hasEmptyStateRow) {
            const tbody = tableEl.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = '';
            }
        }

        const dt = new DataTable(tableEl, config);

        $(tableEl).on('draw.dt', () => {
            initConfirmDialogs();
        });

        if (filterForm) {
            filterForm.addEventListener('submit', (event) => {
                event.preventDefault();
                dt.ajax.reload();
            });
            filterForm.querySelectorAll('select').forEach((sel) => {
                sel.addEventListener('change', () => dt.ajax.reload());
            });
        }
    });
}
