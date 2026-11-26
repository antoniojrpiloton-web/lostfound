@extends('layouts.admin')

@section('eyebrow', __('Master Data'))
@section('title', __('Item Categories'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Categories')]]" />
@endsection

@section('actions')
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Category') }}
    </button>
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <div class="table-responsive">
            <table
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.categories') }}"
                data-datatable-total="{{ $categories->total() }}"
                data-datatable-order-col="1"
                data-datatable-order-dir="asc"
            >
                <thead>
                    <tr>
                        <th>{{ __('English') }}</th>
                        <th>{{ __('Filipino') }}</th>
                        <th>{{ __('Parent') }}</th>
                        <th>{{ __('Slug') }}</th>
                        <th>{{ __('Order') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-medium text-navy">{{ $category->label('en') }}</td>
                            <td>{{ $category->label('fil') }}</td>
                            <td class="text-secondary">{{ $category->parent?->label('en') ?? '—' }}</td>
                            <td><code class="small">{{ $category->slug }}</code></td>
                            <td>{{ $category->sort_order }}</td>
                            <td><x-status-badge :status="$category->is_active ? 'active' : 'suspended'" :label="$category->is_active ? __('Active') : __('Inactive')" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><x-empty-state icon="bi-tags" :title="__('No categories configured')" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="p-3 border-top bg-white">{{ $categories->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="modal-content">
                @csrf
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy" id="createCategoryModalLabel">{{ __('Add Category') }}</h2>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="cat_parent" class="form-label small fw-medium">{{ __('Parent Category (Optional)') }}</label>
                        <select id="cat_parent" name="parent_id" class="form-select form-select-sm" data-select2-url="{{ route('api.select2.categories') }}" data-placeholder="{{ __('Top-Level Category') }}">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cat_slug" class="form-label small fw-medium">{{ __('Slug') }}</label>
                        <input id="cat_slug" name="slug" type="text" class="form-control form-control-sm" placeholder="e.g. diving-gear" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="cat_en" class="form-label small fw-medium">{{ __('Label (English)') }}</label>
                            <input id="cat_en" name="label_en" type="text" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="cat_fil" class="form-label small fw-medium">{{ __('Label (Filipino)') }}</label>
                            <input id="cat_fil" name="label_fil" type="text" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label for="cat_order" class="form-label small fw-medium">{{ __('Sort Order') }}</label>
                        <input id="cat_order" name="sort_order" type="number" min="0" max="999" value="50" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save Category') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
