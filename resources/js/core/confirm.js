import { Modal } from 'bootstrap';

export function initConfirmDialogs() {
    const modalEl = document.getElementById('blfConfirmModal');
    if (!modalEl) {
        return;
    }

    const modal = Modal.getOrCreateInstance(modalEl);
    const titleEl = modalEl.querySelector('[data-confirm-title]');
    const messageEl = modalEl.querySelector('[data-confirm-message]');
    const submitBtn = modalEl.querySelector('[data-confirm-submit]');
    let pendingForm = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
            return;
        }

        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        pendingForm = form;

        if (titleEl) {
            titleEl.textContent = form.dataset.confirmTitle || 'Confirm Action';
        }
        if (messageEl) {
            messageEl.textContent = form.dataset.confirm || 'Are you sure you want to continue?';
        }
        if (submitBtn) {
            submitBtn.textContent = form.dataset.confirmButton || 'Confirm';
            submitBtn.className = `btn btn-sm ${form.dataset.confirmVariant || 'btn-danger'}`;
        }

        modal.show();
    });

    submitBtn?.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        const formToSubmit = pendingForm;
        pendingForm = null;
        formToSubmit.dataset.confirmed = '1';
        modal.hide();
        formToSubmit.requestSubmit();
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        pendingForm = null;
    });
}
