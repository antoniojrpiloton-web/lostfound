@extends('layouts.public')

@section('title', __('My Reports'))

@section('content')
    <section class="py-4">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <div class="blf-page-eyebrow">{{ __('Personal History') }}</div>
                    <h1 class="h4 text-navy fw-bold mb-0">{{ __('My Reports') }}</h1>
                </div>
                <a href="{{ route('reports.lost.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('Report Lost Item') }}
                </a>
            </div>

            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table class="table blf-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Item') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Location / Area') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Verification') }}</th>
                                <th>{{ __('Item Status') }}</th>
                                <th class="text-end">{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr>
                                    <td class="fw-semibold text-navy"><code>{{ $report->reference_no }}</code></td>
                                    <td>
                                        <div class="fw-medium">{{ $report->item_name }}</div>
                                        <div class="small text-secondary">{{ $report->category?->label() }}</div>
                                    </td>
                                    <td><x-status-badge :status="$report->type" /></td>
                                    <td>{{ $report->location?->name ?: ($report->location_details ?: '—') }}</td>
                                    <td class="small text-secondary">{{ $report->occurred_date?->format('M d, Y') }}</td>
                                    <td><x-status-badge :status="$report->verification_status" :label="$report->verification_status->label()" /></td>
                                    <td><x-status-badge :status="$report->item_status" /></td>
                                    <td class="text-end">
                                        <a href="{{ route('reports.show_own', $report) }}" class="btn btn-sm btn-outline-primary">{{ __('Track') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <x-empty-state icon="bi-journal-text" :title="__('You have not submitted any reports yet')" :message="__('Submit a lost item report to start tracking its verification and recovery status.')">
                                            <a href="{{ route('reports.lost.create') }}" class="btn btn-sm btn-primary">{{ __('Report Lost Item') }}</a>
                                        </x-empty-state>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($reports->hasPages())
                    <div class="card-footer bg-white py-2 px-3">{{ $reports->links() }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
