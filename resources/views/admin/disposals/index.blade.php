@extends('layouts.admin')

@section('title', __('Disposal Monitoring'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
        ['label' => __('Disposal Monitoring')],
    ]" />
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Custody Retention & Governance') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ __('Disposal Monitoring Queue') }}</h1>
        </div>
        <span class="badge bg-light text-secondary border fw-normal px-2 py-2">
            <i class="bi bi-clock-history text-primary me-1" aria-hidden="true"></i>{{ __('Automatic Server-Side Retention Scan') }}
        </span>
    </div>

    <div class="blf-panel-card overflow-hidden">
        <form id="disposalsFilterForm" method="GET" action="{{ route('admin.disposals.index') }}" class="blf-table-toolbar">
            <div class="small text-secondary">{{ __('Found items past the retention threshold without active claims.') }}</div>
            <div class="d-flex gap-2">
                <select id="status" name="status" class="form-select form-select-sm pe-4" style="width: auto; min-width: 145px;">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $statusCase)
                        <option value="{{ $statusCase->value }}" @selected($selectedStatus === $statusCase->value)>
                            {{ ucfirst($statusCase->value) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table
                class="table blf-table table-hover align-middle mb-0 w-100 small"
                data-datatable-url="{{ route('admin.datatables.disposals') }}"
                data-datatable-filter="#disposalsFilterForm"
                data-datatable-total="{{ $disposals->total() }}"
                data-datatable-order-col="4"
                data-datatable-order-dir="desc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Item') }}</th>
                        <th>{{ __('Custody Org') }}</th>
                        <th>{{ __('Eligible On') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($disposals as $disposal)
                        <tr>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.disposals.show', $disposal) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('Review') }}
                                </a>
                            </td>
                            <td><code class="text-primary">{{ $disposal->itemReport?->reference_no }}</code></td>
                            <td>
                                <div class="fw-semibold text-navy">{{ $disposal->itemReport?->item_name }}</div>
                                <div class="text-secondary">{{ $disposal->itemReport?->category?->label() }}</div>
                            </td>
                            <td>{{ $disposal->organization?->name }}</td>
                            <td>{{ $disposal->eligible_on?->format('M d, Y') }}</td>
                            <td>{{ $methods[$disposal->method] ?? ($disposal->method ?: '—') }}</td>
                            <td><x-status-badge :status="$disposal->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
