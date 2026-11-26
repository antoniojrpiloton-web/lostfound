@extends('layouts.admin')

@section('eyebrow', __('Master Data'))
@section('title', __('Barangays'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Barangays')]]" />
@endsection

@section('actions')
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createBarangayModal">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Barangay') }}
    </button>
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.barangays') }}"
                data-datatable-total="{{ $barangays->count() }}"
                data-datatable-order-col="2"
                data-datatable-order-dir="asc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Barangay') }}</th>
                        <th>{{ __('Municipality / Province') }}</th>
                        <th>{{ __('Locations') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barangays as $barangay)
                        <tr>
                            <td class="fw-semibold text-navy">{{ $barangay->code }}</td>
                            <td class="fw-medium">{{ $barangay->name }}</td>
                            <td class="text-secondary">{{ $barangay->municipality }}, {{ $barangay->province }}</td>
                            <td>{{ $barangay->locations_count }}</td>
                            <td><x-status-badge :status="$barangay->is_active ? 'active' : 'suspended'" :label="$barangay->is_active ? __('Active') : __('Inactive')" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><x-empty-state icon="bi-geo-alt" :title="__('No barangays configured')" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="createBarangayModal" tabindex="-1" aria-labelledby="createBarangayModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.barangays.store') }}" class="modal-content">
                @csrf
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy" id="createBarangayModalLabel">{{ __('Add Barangay') }}</h2>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="brgy_code" class="form-label small fw-medium">{{ __('Code') }}</label>
                        <input id="brgy_code" name="code" type="text" class="form-control form-control-sm" placeholder="e.g. BRGY-CATICLAN" required>
                    </div>
                    <div class="mb-3">
                        <label for="brgy_name" class="form-label small fw-medium">{{ __('Name') }}</label>
                        <input id="brgy_name" name="name" type="text" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="brgy_muni" class="form-label small fw-medium">{{ __('Municipality') }}</label>
                            <input id="brgy_muni" name="municipality" type="text" value="Malay" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="brgy_prov" class="form-label small fw-medium">{{ __('Province') }}</label>
                            <input id="brgy_prov" name="province" type="text" value="Aklan" class="form-control form-control-sm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save Barangay') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
