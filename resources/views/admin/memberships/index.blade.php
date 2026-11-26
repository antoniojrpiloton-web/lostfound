@extends('layouts.admin')

@section('eyebrow', __('Users & Stations'))
@section('title', __('Staff Accounts'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Dashboard'), 'url' => route('admin.dashboard')], ['label' => __('Staff Accounts')]]" />
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.memberships') }}"
                data-datatable-total="{{ $memberships->total() }}"
                data-datatable-order-col="1"
                data-datatable-order-dir="asc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Member') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Organization') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($memberships as $membership)
                        <tr>
                            <td class="text-nowrap">
                                @if ($membership->role?->key === 'platform_super_admin')
                                    <span class="badge bg-light text-secondary border fw-normal">{{ __('Protected') }}</span>
                                @else
                                    @can('update', $membership)
                                        @if ($membership->status === App\Enums\MembershipStatus::Active)
                                            <form method="POST" action="{{ route('admin.memberships.update', $membership) }}" class="d-inline" data-confirm="{{ __('Suspend this member\'s institutional access?') }}" data-confirm-title="{{ __('Suspend Member') }}" data-confirm-button="{{ __('Suspend') }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="suspended">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Suspend') }}</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.memberships.update', $membership) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Activate') }}</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="small text-secondary">—</span>
                                    @endcan
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-navy">{{ $membership->user?->name }}</div>
                                <div class="small text-secondary">{{ $membership->user?->email }}</div>
                            </td>
                            <td>{{ $membership->role?->name }}</td>
                            <td>{{ $membership->organization?->name ?? __('Platform-Wide') }}</td>
                            <td><x-status-badge :status="$membership->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
