@extends('layouts.admin')

@section('eyebrow', __('Operations'))
@section('title', __('Claims Verification Queue'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Claims Queue')]]" />
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <form id="claimsFilterForm" method="GET" action="{{ route('admin.claims.index') }}" class="blf-table-toolbar">
            <div class="small text-secondary">{{ __('Verify private ownership proof before approving physical turnover.') }}</div>
            <div class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">{{ __('All Claim Statuses') }}</option>
                    @foreach (App\Enums\ClaimStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucwords(str_replace('_', ' ', $status->value)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.claims') }}"
                data-datatable-filter="#claimsFilterForm"
                data-datatable-total="{{ $claims->total() }}"
                data-datatable-order-col="5"
                data-datatable-order-dir="desc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Claimant') }}</th>
                        <th>{{ __('Found Item') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Claim Ref') }}</th>
                        <th>{{ __('Submitted') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($claims as $claim)
                        <tr>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.claims.show', $claim) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('Review') }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-medium text-navy">{{ $claim->claimant?->name }}</div>
                                <div class="small text-secondary">{{ $claim->claimant?->email }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-navy">{{ $claim->itemReport?->item_name }}</div>
                                <code class="small text-primary">{{ $claim->itemReport?->reference_no }}</code>
                            </td>
                            <td><x-status-badge :status="$claim->status" /></td>
                            <td><code class="small text-secondary">{{ $claim->reference_no }}</code></td>
                            <td class="small text-secondary">{{ $claim->submitted_at?->format('M d, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
