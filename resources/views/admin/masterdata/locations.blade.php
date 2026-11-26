@extends('layouts.admin')

@section('eyebrow', __('Master Data'))
@section('title', __('Locations & Landmarks'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Locations')]]" />
@endsection

@section('actions')
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createLocationModal">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Location') }}
    </button>
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.locations') }}"
                data-datatable-total="{{ $locations->total() }}"
                data-datatable-order-col="1"
                data-datatable-order-dir="asc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Landmark / Area') }}</th>
                        <th>{{ __('Zone') }}</th>
                        <th>{{ __('Assigned Institution') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr>
                            <td class="fw-medium text-navy">{{ $location->name }}</td>
                            <td>{{ $location->barangay?->name }}</td>
                            <td class="text-secondary">{{ $location->organization?->name ?? '—' }}</td>
                            <td class="text-capitalize">{{ $location->type ?? '—' }}</td>
                            <td><x-status-badge :status="$location->is_active ? 'active' : 'suspended'" :label="$location->is_active ? __('Active') : __('Inactive')" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><x-empty-state icon="bi-pin-map" :title="__('No locations found')" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($locations->hasPages())
            <div class="p-3 border-top bg-white">{{ $locations->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="createLocationModal" tabindex="-1" aria-labelledby="createLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.locations.store') }}" class="modal-content">
                @csrf
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy" id="createLocationModalLabel">{{ __('Add Location') }}</h2>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    @if ($barangays->isNotEmpty())
                        <input type="hidden" name="barangay_id" value="{{ $barangays->first()->id }}">
                    @endif
                    <div class="mb-3">
                        <label for="loc_org" class="form-label small fw-medium">{{ __('Assigned Institution') }}</label>
                        <select id="loc_org" name="organization_id" class="form-select form-select-sm" data-select2-url="{{ route('api.select2.organizations') }}" data-placeholder="{{ __('Default Boracay Police Station') }}">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="loc_name" class="form-label small fw-medium">{{ __('Location Name') }}</label>
                        <input id="loc_name" name="name" type="text" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-4">
                            <label for="loc_type" class="form-label small fw-medium">{{ __('Type') }}</label>
                            <input id="loc_type" name="type" type="text" class="form-control form-control-sm" placeholder="beach / port">
                        </div>
                        <div class="col-sm-4">
                            <label for="loc_lat" class="form-label small fw-medium">{{ __('Latitude') }}</label>
                            <input id="loc_lat" name="latitude" type="text" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-4">
                            <label for="loc_lng" class="form-label small fw-medium">{{ __('Longitude') }}</label>
                            <input id="loc_lng" name="longitude" type="text" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div>
                        <label for="loc_desc" class="form-label small fw-medium">{{ __('Description') }}</label>
                        <textarea id="loc_desc" name="description" rows="2" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save Location') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
