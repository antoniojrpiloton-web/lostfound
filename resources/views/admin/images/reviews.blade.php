@extends('layouts.admin')

@section('eyebrow', __('Operations'))
@section('title', __('Image Validation Review Queue'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Image Reviews')]]" />
@endsection

@section('content')
    <div class="blf-panel-card overflow-hidden">
        <div class="table-responsive">
            <table class="table blf-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Preview') }}</th>
                        <th>{{ __('Report') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Automated Notes') }}</th>
                        <th class="text-end">{{ __('Decision') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($images as $image)
                        @php
                            $report = $image->itemReport;
                            $notes = $image->validation_result['messages'] ?? [$image->validation_result['reason'] ?? __('Pending manual review')];
                        @endphp
                        <tr>
                            <td>
                                <img src="{{ route('admin.images.preview', $image) }}" alt="" class="rounded border" style="width: 64px; height: 48px; object-fit: cover;">
                            </td>
                            <td>
                                <div class="fw-medium text-navy">{{ $report?->item_name }}</div>
                                <code class="small text-secondary">{{ $report?->reference_no }}</code>
                            </td>
                            <td>{{ $report?->category?->label() }}</td>
                            <td><x-status-badge :status="$image->validation_status" /></td>
                            <td class="small text-secondary">{{ implode(' ', (array) $notes) }}</td>
                            <td class="text-end text-nowrap">
                                @if ($report && auth()->user()?->can('verify', $report))
                                    <form method="POST" action="{{ route('admin.images.decide', $image) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="overridden">
                                        <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.images.decide', $image) }}" class="d-inline" data-confirm="{{ __('Reject this image and require the reporter to upload a replacement?') }}" data-confirm-title="{{ __('Reject Image') }}" data-confirm-button="{{ __('Reject') }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button>
                                    </form>
                                @else
                                    <span class="small text-secondary">{{ __('Admin required') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state icon="bi-images" :title="__('No images awaiting manual review')" :message="__('Uncertain or flagged image uploads will appear here for institutional review.')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($images->hasPages())
            <div class="p-3 border-top bg-white">{{ $images->links() }}</div>
        @endif
    </div>
@endsection
