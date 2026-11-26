<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Reference Slip') }} — {{ $report->reference_no }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #f6f7f9; }
        .slip-card { max-width: 560px; margin: 2rem auto; background: #ffffff; border: 1px solid #d1d5db; border-radius: 0.75rem; padding: 1.75rem; }
        @media print {
            body { background: #ffffff; }
            .no-print { display: none !important; }
            .slip-card { margin: 0 auto; border: 1px solid #000; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="slip-card shadow-sm">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('images/boracay-lf-logo.png') }}" alt="{{ __('Boracay Lost & Found') }}" width="46" height="46" style="object-fit: contain;">
                <div>
                    <div class="text-uppercase text-secondary fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                        {{ __('Boracay Lost and Found System') }}
                    </div>
                    <h1 class="h5 fw-bold text-navy mb-0">{{ __('Official Reference & Tracking Slip') }}</h1>
                </div>
            </div>
            <span class="badge {{ $report->type->value === 'found' ? 'text-bg-primary' : 'text-bg-dark' }}">
                {{ $report->type->label() }}
            </span>
        </div>

        <div class="row g-3 align-items-center mb-3">
            <div class="col-sm-8">
                <div class="small text-secondary">{{ __('Reference Number') }}</div>
                <div class="h4 fw-bold text-navy mb-2" style="font-family: ui-monospace, monospace;">{{ $report->reference_no }}</div>

                <div class="small text-secondary">{{ __('Item') }}</div>
                <div class="fw-semibold text-navy mb-2">{{ $report->item_name }}</div>

                <div class="small text-secondary">
                    {{ $report->category?->label() }} · {{ $report->location?->name ?: ($report->location_details ?: 'Boracay Island') }}
                </div>
                <div class="small text-secondary">
                    {{ __('Date:') }} {{ $report->occurred_date?->format('M d, Y') }}
                </div>
            </div>
            <div class="col-sm-4 text-center">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=4&data={{ urlencode($lookupUrl) }}"
                    alt="{{ __('QR Code for :ref', ['ref' => $report->reference_no]) }}"
                    class="border rounded p-1 bg-white"
                    width="140"
                    height="140"
                >
                <div class="text-secondary mt-1" style="font-size: 0.68rem;">{{ __('Scan to track status') }}</div>
            </div>
        </div>

        <div class="border-top pt-3 small">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-secondary">{{ __('Handling / Custody Desk') }}</span>
                <strong class="text-navy">{{ $report->custodyOrganization?->name ?? $report->handlingOrganization?->name ?? 'Boracay L&F Desk' }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-secondary">{{ __('Verification Status') }}</span>
                <strong class="text-navy">{{ ucfirst(str_replace('_', ' ', $report->verification_status->value)) }}</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-secondary">{{ __('Item Status') }}</span>
                <strong class="text-navy">{{ ucfirst(str_replace('_', ' ', $report->item_status->value)) }}</strong>
            </div>
        </div>

        <div class="mt-3 pt-2 border-top text-secondary" style="font-size: 0.72rem;">
            {{ __('Present this slip and a valid government ID when claiming or confirming turnover at the designated Boracay Police Booth or Police Station.') }}
        </div>

        <div class="no-print d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <button type="button" onclick="window.close()" class="btn btn-sm btn-light border">{{ __('Close') }}</button>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-primary px-3">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>{{ __('Print Slip') }}
            </button>
        </div>
    </div>
</body>
</html>
