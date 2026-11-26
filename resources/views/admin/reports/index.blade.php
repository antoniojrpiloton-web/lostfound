@extends('layouts.admin')

@section('eyebrow', __('Operations'))
@section('title', __('Reports Queue'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Reports Queue')]]" />
@endsection

@section('actions')
    <div class="d-flex flex-wrap gap-2">
        @if (auth()->user()?->hasPermission('image.review'))
            <a href="{{ route('admin.images.reviews') }}" class="btn btn-sm btn-light bg-white border text-navy px-3">
                <i class="bi bi-images me-1" aria-hidden="true"></i>{{ __('Image Reviews') }}
                @if (($flaggedPhotosCount ?? 0) > 0)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1">{{ $flaggedPhotosCount }}</span>
                @endif
            </a>
        @endif
        @if (($boothCustodyCount ?? 0) > 0 && auth()->user()?->hasPermission('custody.manage'))
            <form
                method="POST"
                action="{{ route('admin.reports.return_all_booth_to_station') }}"
                class="m-0"
                data-confirm="{{ __('Log all :count unclaimed beach booth item(s) as returned to the Police Station for safekeeping?', ['count' => $boothCustodyCount]) }}"
                data-confirm-title="{{ __('End-of-Shift Return to Police Station') }}"
                data-confirm-button="{{ __('Return All to Station') }}"
            >
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary px-3">
                    <i class="bi bi-building-lock me-1" aria-hidden="true"></i>{{ __('End of Shift: Return Booth Items (:count)', ['count' => $boothCustodyCount]) }}
                </button>
            </form>
        @endif
        @if (auth()->user()?->hasPermission('report.encode_on_behalf'))
            <a href="{{ route('admin.found.create') }}" class="btn btn-sm btn-primary px-3">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('Register Found Item') }}
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <form id="reportsFilterForm" method="GET" action="{{ route('admin.reports.index') }}" class="blf-table-toolbar">
            <div class="small text-secondary">{{ __('Server-side paginated reports queue across your institution.') }}</div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <select name="type" class="form-select form-select-sm" style="width: auto;">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach (App\Enums\ReportType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <select name="verification_status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">{{ __('All Verification') }}</option>
                    @foreach (App\Enums\VerificationStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('verification_status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.reports') }}"
                data-datatable-filter="#reportsFilterForm"
                data-datatable-total="{{ $reports->total() }}"
                data-datatable-order-col="7"
                data-datatable-order-dir="desc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Item') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Location / Area') }}</th>
                        <th>{{ __('Verification') }}</th>
                        <th>{{ __('Item Status') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Occurred') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Review') }}">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('Review') }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-medium text-navy">{{ $report->item_name }}</div>
                                <div class="small text-secondary">{{ $report->category?->label() }}</div>
                            </td>
                            <td><x-status-badge :status="$report->type" /></td>
                            <td>{{ $report->location?->name ?: ($report->location_details ?: '—') }}</td>
                            <td><x-status-badge :status="$report->verification_status" :label="$report->verification_status->label()" /></td>
                            <td><x-status-badge :status="$report->item_status" /></td>
                            <td><code class="small text-primary">{{ $report->reference_no }}</code></td>
                            <td class="small text-secondary">{{ $report->occurred_date?->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
