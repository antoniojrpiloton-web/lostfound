@extends('layouts.admin')

@section('eyebrow', __('Governance & Accountability'))
@section('title', __('Audit Logs'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Audit Logs')]]" />
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <form id="auditFilterForm" method="GET" action="{{ route('admin.audit_logs.index') }}" class="blf-table-toolbar">
            <div class="small text-secondary">
                <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                {{ __('Immutable activity trail recording verifications, match decisions, claims, custody transfers, and system actions.') }}
            </div>
            <div class="d-flex gap-2">
                <select name="action" class="form-select form-select-sm" style="width: auto;">
                    <option value="">{{ __('All Actions') }}</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.audit_logs') }}"
                data-datatable-filter="#auditFilterForm"
                data-datatable-total="{{ $logs->total() }}"
                data-datatable-order-col="5"
                data-datatable-order-dir="desc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Actor') }}</th>
                        <th>{{ __('Target') }}</th>
                        <th>{{ __('IP Address') }}</th>
                        <th>{{ __('Timestamp') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="text-nowrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#auditDiffModal"
                                    data-audit-action="{{ $log->action }}"
                                    data-audit-actor="{{ $log->user?->name ?? __('System') }}"
                                    data-audit-time="{{ $log->created_at?->format('M d, Y H:i:s') }}"
                                    data-audit-old="{{ json_encode($log->old_values ?? (object) [], JSON_PRETTY_PRINT) }}"
                                    data-audit-new="{{ json_encode($log->new_values ?? (object) [], JSON_PRETTY_PRINT) }}"
                                >
                                    {{ __('Inspect') }}
                                </button>
                            </td>
                            <td>
                                <span class="badge bg-light text-navy border fw-medium">{{ $log->action }}</span>
                            </td>
                            <td>
                                <div class="fw-medium text-navy">{{ $log->user?->name ?? __('System') }}</div>
                                <div class="small text-secondary">{{ $log->organization?->name ?? '—' }}</div>
                            </td>
                            <td class="small text-secondary">
                                {{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}
                            </td>
                            <td class="small text-secondary"><code>{{ $log->ip_address ?: '—' }}</code></td>
                            <td class="small text-secondary">{{ $log->created_at?->format('M d, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="auditDiffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header py-2 px-3">
                    <div>
                        <h2 class="modal-title h6 text-navy mb-0" id="auditDiffTitle">{{ __('Audit Event Details') }}</h2>
                        <div class="small text-secondary" id="auditDiffMeta"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small fw-semibold text-secondary mb-1">{{ __('Previous Values') }}</div>
                            <pre id="auditDiffOld" class="p-2 rounded border bg-light small mb-0" style="max-height: 300px; overflow: auto;"></pre>
                        </div>
                        <div class="col-md-6">
                            <div class="small fw-semibold text-secondary mb-1">{{ __('New Values') }}</div>
                            <pre id="auditDiffNew" class="p-2 rounded border bg-light small mb-0" style="max-height: 300px; overflow: auto;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('auditDiffModal');
    const titleEl = document.getElementById('auditDiffTitle');
    const metaEl = document.getElementById('auditDiffMeta');
    const oldEl = document.getElementById('auditDiffOld');
    const newEl = document.getElementById('auditDiffNew');

    modal?.addEventListener('show.bs.modal', (event) => {
        const btn = event.relatedTarget;
        if (!btn) return;
        if (titleEl) titleEl.textContent = btn.getAttribute('data-audit-action') || 'Audit Event';
        if (metaEl) metaEl.textContent = `${btn.getAttribute('data-audit-actor') || 'System'} · ${btn.getAttribute('data-audit-time') || ''}`;
        if (oldEl) oldEl.textContent = btn.getAttribute('data-audit-old') || '{}';
        if (newEl) newEl.textContent = btn.getAttribute('data-audit-new') || '{}';
    });
});
</script>
@endPushOnce
