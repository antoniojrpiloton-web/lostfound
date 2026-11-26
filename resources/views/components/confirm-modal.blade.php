<div class="modal fade" id="blfConfirmModal" tabindex="-1" aria-labelledby="blfConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2 px-3">
                <h2 class="modal-title h6 mb-0 text-navy" id="blfConfirmModalLabel" data-confirm-title>{{ __('Confirm Action') }}</h2>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body py-3 px-3 small text-secondary" data-confirm-message>
                {{ __('Are you sure you want to continue?') }}
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-sm btn-danger" data-confirm-submit>{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>
</div>
