@extends('layouts.public')

@section('title', __('My Claims'))

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Personal History') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ __('My Ownership Claims') }}</h1>
        </div>
        <a href="{{ route('public.items.index', ['type' => 'found']) }}" class="btn btn-sm btn-primary">{{ __('Browse Found Items') }}</a>
    </div>

    <div class="blf-panel-card overflow-hidden">
        <div class="table-responsive">
            <table class="table blf-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Claim Reference') }}</th>
                        <th>{{ __('Found Item') }}</th>
                        <th>{{ __('Custody Institution') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Submitted') }}</th>
                        <th class="text-end">{{ __('Details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        <tr>
                            <td><code class="fw-semibold text-navy">{{ $claim->reference_no }}</code></td>
                            <td>
                                <div class="fw-medium text-navy">{{ $claim->itemReport?->item_name }}</div>
                                <code class="small text-secondary">{{ $claim->itemReport?->reference_no }}</code>
                            </td>
                            <td class="small text-secondary">{{ $claim->itemReport?->custodyOrganization?->name ?? '—' }}</td>
                            <td><x-status-badge :status="$claim->status" /></td>
                            <td class="small text-secondary">{{ $claim->submitted_at?->format('M d, Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('claims.show_own', $claim) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state icon="bi-patch-check" :title="__('No ownership claims submitted yet')" :message="__('Browse verified found items in custody to submit an ownership claim.')">
                                    <a href="{{ route('public.items.index', ['type' => 'found']) }}" class="btn btn-sm btn-primary">{{ __('Browse Found Items') }}</a>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($claims->hasPages())
            <div class="p-3 border-top bg-white">{{ $claims->links() }}</div>
        @endif
    </div>
@endsection
