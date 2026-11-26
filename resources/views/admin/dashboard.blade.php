@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('Dashboard')]]" />
@endsection

@section('hero-header')
    @php
        $firstName = explode(' ', trim($user->name))[0] ?? $user->name;
    @endphp
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <div class="blf-date-eyebrow mb-1">{{ strtoupper(now()->format('l · F d · Y')) }}</div>
            <h1 class="h2 fw-bold text-navy mb-1">
                {{ __('Welcome back,') }} <span class="text-ocean">{{ $firstName }}</span>
            </h1>
            <p class="small text-secondary mb-0">
                {{ $membership?->organization?->name ?? __('Platform-wide overview across Boracay Island Police Booths and Stations.') }}
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($user->hasPermission('report.encode_on_behalf'))
                <a href="{{ route('admin.found.create') }}" class="btn btn-sm btn-primary fw-medium px-3">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('Register Found Item') }}
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card
                :label="__('Total Lost Reports')"
                :value="$stats['total_lost']"
                :subtitle="$stats['pending_verification'].' '.__('pending verification')"
                :badge="$stats['verified'].' '.__('verified')"
                icon="bi-megaphone"
                tone="warning"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card
                :label="__('Total Found Reports')"
                :value="$stats['total_found']"
                :subtitle="$stats['unclaimed'].' '.__('unclaimed in custody')"
                :badge="$stats['possible_matches'].' '.__('matches')"
                icon="bi-box-seam"
                tone="ocean"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card
                :label="__('Pending Claims')"
                :value="$stats['pending_claims']"
                :subtitle="$stats['claimed'].' '.__('approved for turnover')"
                :badge="$stats['pending_claims'] > 0 ? __('Action') : __('Clear')"
                icon="bi-patch-check"
                tone="navy"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-kpi-card
                :label="__('Recovered Items')"
                :value="$stats['recovered']"
                :subtitle="$stats['disposal_eligible'].' '.__('disposal queue').' · '.$stats['disposed'].' '.__('disposed')"
                :badge="__('Confirmed')"
                icon="bi-bag-check"
                tone="teal"
            />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="blf-panel-card p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="blf-page-eyebrow">{{ __('6-Month Trend') }}</div>
                        <h2 class="h6 fw-semibold text-navy mb-0">{{ __('Lost, Found & Recovered Activity') }}</h2>
                    </div>
                    @if ($user->hasPermission('analytics.view'))
                        <a href="{{ route('admin.analytics.index') }}" class="small text-decoration-none fw-medium text-ocean">
                            {{ __('Full report') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
                <div style="height: 240px;">
                    <canvas data-blf-chart="monthly" data-chart-payload='@json($monthlyTrend)'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="blf-panel-card h-100">
                <div class="px-3 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="small fw-semibold text-navy">{{ __('Institutional Profile') }}</span>
                    <x-status-badge status="active" :label="$membership?->role?->name ?? __('Authorized')" />
                </div>
                <div class="p-3 small">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Signed In As') }}</dt>
                        <dd class="col-sm-8 fw-medium text-navy">{{ $user->name }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Organization') }}</dt>
                        <dd class="col-sm-8">{{ $membership?->organization?->name ?? __('Platform-Wide') }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal">{{ __('Team') }}</dt>
                        <dd class="col-sm-8 mb-0">{{ $stats['memberships'] }} {{ __('members') }} · {{ $stats['pending_invitations'] }} {{ __('pending invites') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="blf-panel-card h-100">
                <div class="px-3 py-3 border-bottom">
                    <span class="small fw-semibold text-navy">{{ __('Recent Activity') }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($recentActivity as $entry)
                        <div class="list-group-item px-3 py-2 border-light">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="small">
                                    <span class="fw-medium text-navy">{{ $entry->user?->name ?? __('System') }}</span>
                                    <span class="text-secondary">· {{ str_replace(['.', '_'], ' ', $entry->action) }}</span>
                                </div>
                                <span class="text-secondary text-nowrap" style="font-size: 0.72rem;">{{ $entry->created_at?->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-activity" :title="__('No recent activity')" :message="__('Audit events will appear here as actions occur.')" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
