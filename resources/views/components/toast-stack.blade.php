<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;" aria-live="polite" aria-atomic="true">
    @if (session('status'))
        <div class="toast align-items-center text-bg-success border-0" role="status" data-autoshow="true" data-bs-delay="4500">
            <div class="d-flex">
                <div class="toast-body small">
                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ session('status') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="toast align-items-center text-bg-danger border-0" role="alert" data-autoshow="true" data-bs-delay="6000">
            <div class="d-flex">
                <div class="toast-body small">
                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
            </div>
        </div>
    @endif
</div>
