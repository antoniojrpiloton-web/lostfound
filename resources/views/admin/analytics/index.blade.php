@extends('layouts.admin')

@section('title', __('Analytics & Reports'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
        ['label' => __('Analytics & Reports')],
    ]" />
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Operational Intelligence') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ __('Analytics & Institutional Reports') }}</h1>
        </div>

        @if (auth()->user()?->hasPermission('export.generate'))
            <div class="d-flex gap-2">
                <a href="{{ route('admin.analytics.export.csv', request()->query()) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i>{{ __('Export CSV') }}
                </a>
                <a href="{{ route('admin.analytics.export.pdf', request()->query()) }}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="bi bi-filetype-pdf me-1" aria-hidden="true"></i>{{ __('Export PDF') }}
                </a>
            </div>
        @endif
    </div>

    <div class="blf-panel-card p-3 mb-3">
        <form method="GET" action="{{ route('admin.analytics.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="date_from" class="form-label small text-secondary mb-1">{{ __('From Date') }}</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label small text-secondary mb-1">{{ __('To Date') }}</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            @if ($organizations->isNotEmpty())
                <div class="col-md-4">
                    <label for="organization_id" class="form-label small text-secondary mb-1">{{ __('Institution') }}</label>
                    <select id="organization_id" name="organization_id" class="form-select form-select-sm">
                        <option value="">{{ __('All Institutions') }}</option>
                        @foreach ($organizations as $org)
                            <option value="{{ $org->id }}" @selected((string) ($filters['organization_id'] ?? '') === (string) $org->id)>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('Apply') }}</button>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-kpi-card :label="__('Total Lost')" :value="$summary['total_lost']" :subtitle="$summary['pending_verification'].' '.__('pending')" icon="bi-megaphone" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-kpi-card :label="__('Total Found')" :value="$summary['total_found']" :subtitle="$summary['unclaimed'].' '.__('unclaimed')" icon="bi-box-seam" tone="ocean" /></div>
        <div class="col-6 col-lg-3"><x-kpi-card :label="__('Recovered')" :value="$summary['recovered']" :subtitle="$summary['pending_claims'].' '.__('pending claims')" icon="bi-bag-check" tone="teal" /></div>
        <div class="col-6 col-lg-3"><x-kpi-card :label="__('Disposals')" :value="$summary['disposed']" :subtitle="$summary['disposal_eligible'].' '.__('in disposal queue')" icon="bi-archive" tone="navy" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="blf-panel-card p-3 p-md-4 h-100">
                <h2 class="h6 fw-semibold text-navy mb-3">{{ __('12-Month Report & Recovery Trend') }}</h2>
                <div style="height: 260px;">
                    <canvas data-blf-chart="monthly" data-chart-payload='@json($monthlyTrend)'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="blf-panel-card overflow-hidden">
        <div class="px-3 py-3 border-bottom">
            <h2 class="h6 fw-semibold text-navy mb-0">{{ __('Institution Summary') }}</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Institution') }}</th>
                        <th class="text-end">{{ __('Lost') }}</th>
                        <th class="text-end">{{ __('Found') }}</th>
                        <th class="text-end">{{ __('Verified') }}</th>
                        <th class="text-end">{{ __('Recovered') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($institutionBreakdown as $row)
                        <tr>
                            <td class="fw-medium text-navy">{{ $row['name'] }}</td>
                            <td class="text-end">{{ $row['lost'] }}</td>
                            <td class="text-end">{{ $row['found'] }}</td>
                            <td class="text-end">{{ $row['verified'] }}</td>
                            <td class="text-end fw-semibold text-success">{{ $row['recovered'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
