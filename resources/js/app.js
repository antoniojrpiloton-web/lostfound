import * as bootstrap from 'bootstrap';
import { initTooltips, initToasts } from './core/ui';
import { initAdminShell } from './core/admin-shell';
import { initConfirmDialogs } from './core/confirm';
import { initSelect2 } from './core/select2';
import { initCharts } from './core/charts';
import { initDropzones } from './core/dropzone';
import { initSmartSearch } from './core/smart-search';
import { initDataTables } from './core/datatables';
import { startPhotoScan, animateMatchCounters, scoreTier } from './core/scanner';

window.bootstrap = bootstrap;
window.BLFScanner = { startPhotoScan, animateMatchCounters, scoreTier };

document.addEventListener('DOMContentLoaded', () => {
    initTooltips();
    initToasts();
    initAdminShell();
    initConfirmDialogs();
    initSelect2();
    initCharts();
    initDropzones();
    initSmartSearch();
    initDataTables();
});
