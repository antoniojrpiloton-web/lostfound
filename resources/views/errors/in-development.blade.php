@extends('layouts.admin')

@section('eyebrow', __('Upcoming Release'))
@section('title', $title ?? __('Module Under Development'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
        ['label' => $title ?? __('In Development')],
    ]" />
@endsection

@section('content')
    <div class="row justify-content-center py-4">
        <div class="col-md-8 col-lg-6">
            <div class="blf-panel-card p-5 text-center bg-white">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border" style="width: 72px; height: 72px;">
                        <i class="bi bi-cone-striped fs-2 text-warning" aria-hidden="true"></i>
                    </span>
                </div>

                <h2 class="h4 fw-bold text-navy mb-2">{{ $title ?? __('Module Under Development') }}</h2>
                <div class="mb-3">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 small">{{ __('In Development') }}</span>
                </div>

                <p class="text-secondary small mb-4" style="line-height: 1.65;">
                    {{ $description ?? __('This module is currently being developed and will be enabled in the upcoming system update.') }}
                </p>

                <div>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm px-4 py-2">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('Back to Dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
