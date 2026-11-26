@extends('layouts.admin')

@section('eyebrow', __('Users & Stations'))
@section('title', __('Police Stations & Assistance Booths'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Dashboard'), 'url' => route('admin.dashboard')], ['label' => __('Stations & Booths')]]" />
@endsection

@section('actions')
    @can('create', App\Models\Organization::class)
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createOrganizationModal">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Station / Booth') }}
        </button>
    @endcan
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.organizations') }}"
                data-datatable-total="{{ $organizations->total() }}"
                data-datatable-order-col="2"
                data-datatable-order-dir="asc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Station / Booth Name') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Coverage Area') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $organization)
                        <tr>
                            <td class="fw-semibold text-navy">{{ $organization->code }}</td>
                            <td>
                                <div class="fw-medium">{{ $organization->name }}</div>
                                @if ($organization->contact_email)
                                    <div class="small text-secondary">{{ $organization->contact_email }}</div>
                                @endif
                            </td>
                            <td>{{ $organization->type->label() }}</td>
                            <td>
                                @forelse ($organization->barangays as $barangay)
                                    <span class="badge text-bg-light border text-secondary fw-normal">{{ $barangay->name }}</span>
                                @empty
                                    <span class="text-secondary small">—</span>
                                @endforelse
                            </td>
                            <td>
                                <x-status-badge :status="$organization->is_active ? 'active' : 'suspended'" :label="$organization->is_active ? __('Active') : __('Inactive')" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="bi-building" :title="__('No organizations yet')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($organizations->hasPages())
            <div class="card-footer bg-white py-2 px-3">
                {{ $organizations->links() }}
            </div>
        @endif
    </div>

    <div class="modal fade" id="createOrganizationModal" tabindex="-1" aria-labelledby="createOrganizationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.organizations.store') }}" class="modal-content">
                @csrf
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy" id="createOrganizationModalLabel">{{ __('Add Police Station / Booth') }}</h2>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="org_type" class="form-label small fw-medium">{{ __('Desk Type') }}</label>
                        <select id="org_type" name="type" class="form-select form-select-sm" required>
                            @foreach (App\Enums\OrganizationType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="org_code" class="form-label small fw-medium">{{ __('Code') }}</label>
                        <input id="org_code" name="code" type="text" class="form-control form-control-sm" placeholder="e.g. PNP-STN2" required>
                    </div>
                    <div class="mb-3">
                        <label for="org_name" class="form-label small fw-medium">{{ __('Station / Booth Name') }}</label>
                        <input id="org_name" name="name" type="text" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="org_email" class="form-label small fw-medium">{{ __('Contact Email') }}</label>
                            <input id="org_email" name="contact_email" type="email" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-6">
                            <label for="org_phone" class="form-label small fw-medium">{{ __('Contact Phone') }}</label>
                            <input id="org_phone" name="contact_phone" type="text" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save Station / Booth') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
