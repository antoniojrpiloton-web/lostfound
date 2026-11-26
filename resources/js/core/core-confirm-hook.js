export function initConfirmDialogs() {
    const modalEl = document.getElementById('blfConfirmModal');
    if (!modalEl || !window.bootstrap) {
        return;
    }

    const titleEl = modalEl.querySelector('[data-confirm-title]');
    const messageEl = modalEl.querySelector('[data-confirm-message]');
    const submitBtn = modalEl.querySelector('[data-confirm-submit]');

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        if (form.dataset.confirmBound === '1') {
            return;
        }
        form.dataset.confirmBound = '1';

        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }
            event.preventDefault();

            if (titleEl) {
                titleEl.textContent = form.getAttribute('data-confirm-title') || 'Confirm Action';
            }
            if (messageEl) {
                messageEl.textContent = form.getAttribute('data-confirm') || 'Are you sure you want to continue?';
            }
            if (submitBtn) {
                submitBtn.textContent = form.getAttribute('data-confirm-button') || 'Confirm';
                submitBtn.className = `btn btn-sm ${form.getAttribute('data-confirm-variant') || 'btn-danger'}`;
                submitBtn.onclick = () => {
                    form.dataset.confirmed = '1';
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    form.submit();
                };
            }

            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });
}
