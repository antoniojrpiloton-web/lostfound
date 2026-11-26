@extends('layouts.admin')

@section('eyebrow', __('Operations'))
@section('title', __('Recovery & Turnover Log'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Recoveries')]]" />
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.recoveries') }}"
                data-datatable-total="{{ $recoveries->total() }}"
                data-datatable-order-col="4"
                data-datatable-order-dir="desc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Item & Claim') }}</th>
                        <th>{{ __('Receiving Claimant') }}</th>
                        <th>{{ __('ID Check') }}</th>
                        <th>{{ __('Turnover Date') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recoveries as $recovery)
                        <tr>
                            <td class="text-nowrap">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.recoveries.receipt_pdf', $recovery) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>{{ __('Receipt PDF') }}
                                    </a>
                                    @if ($recovery->confirmed_by === null && auth()->user()?->hasPermission('recovery.confirm'))
                                        <form method="POST" action="{{ route('admin.recoveries.confirm', $recovery) }}" class="d-inline" data-confirm="{{ __('Confirm physical recovery? This marks the item returned and closes competing claims.') }}" data-confirm-title="{{ __('Confirm Recovery') }}" data-confirm-button="{{ __('Confirm Recovery') }}" data-confirm-variant="btn-success">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success">{{ __('Confirm') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-navy">{{ $recovery->itemReport?->item_name }}</div>
                                <code class="small text-secondary">{{ $recovery->itemReport?->reference_no }} · {{ $recovery->claim?->reference_no }}</code>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $recovery->received_by_name }}</div>
                                <div class="small text-secondary">{{ $recovery->organization?->name }}</div>
                            </td>
                            <td class="small text-secondary">{{ $recovery->id_type }} (****{{ $recovery->id_last4 }})</td>
                            <td class="small text-secondary">{{ $recovery->turnover_at?->format('M d, Y H:i') }}</td>
                            <td>
                                @if ($recovery->confirmed_by !== null)
                                    <x-status-badge status="completed" :label="__('Confirmed')" />
                                @else
                                    <x-status-badge status="pending" :label="__('Awaiting Admin Confirmation')" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
