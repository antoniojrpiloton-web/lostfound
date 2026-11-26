import { Tooltip, Toast } from 'bootstrap';

export function initTooltips(root = document) {
    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        Tooltip.getOrCreateInstance(element);
    });
}

export function initToasts(root = document) {
    root.querySelectorAll('.toast[data-autoshow="true"]').forEach((element) => {
        Toast.getOrCreateInstance(element).show();
    });
}
