<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Item Turnover Receipt — {{ $recovery->itemReport?->reference_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0F294A; margin: 24px; }
        .header { border-bottom: 2px solid #2563EB; padding-bottom: 10px; margin-bottom: 16px; }
        .eyebrow { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #2563EB; font-weight: bold; }
        h1 { font-size: 18px; margin: 4px 0 2px; color: #0F294A; }
        .meta { font-size: 10px; color: #475569; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1D4ED8; background: #EFF6FF; padding: 5px 8px; margin: 14px 0 8px; border-left: 3px solid #2563EB; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        td { padding: 6px 8px; border: 1px solid #CBD5E1; vertical-align: top; }
        td.label { width: 32%; background: #F8FAFC; font-weight: bold; color: #334155; }
        .sig-table td { border: none; padding-top: 34px; text-align: center; width: 50%; }
        .sig-line { border-top: 1px solid #0F294A; margin: 0 24px 4px; }
        .footer { margin-top: 24px; font-size: 9px; color: #64748B; border-top: 1px solid #E2E8F0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Boracay Island Lost &amp; Found Recovery System</div>
        <h1>Official Item Turnover &amp; Recovery Receipt</h1>
        <div class="meta">
            Issued by: {{ $recovery->organization?->name ?? 'Boracay Lost & Found Authority' }}
            &nbsp;|&nbsp; Generated: {{ $generatedAt->format('M d, Y H:i') }}
        </div>
    </div>

    <div class="section-title">Item &amp; Claim Details</div>
    <table>
        <tr>
            <td class="label">Item Reference No.</td>
            <td><strong>{{ $recovery->itemReport?->reference_no }}</strong></td>
        </tr>
        <tr>
            <td class="label">Claim Reference No.</td>
            <td><strong>{{ $recovery->claim?->reference_no }}</strong></td>
        </tr>
        <tr>
            <td class="label">Item Description</td>
            <td>{{ $recovery->itemReport?->item_name }} ({{ $recovery->itemReport?->category?->label() }})</td>
        </tr>
        <tr>
            <td class="label">Brand / Color</td>
            <td>{{ trim(($recovery->itemReport?->brand ?? '—').' / '.($recovery->itemReport?->color ?? '—')) }}</td>
        </tr>
        <tr>
            <td class="label">Location Found</td>
            <td>{{ $recovery->itemReport?->location?->name ?? $recovery->itemReport?->barangay?->name ?? 'Boracay Island' }}</td>
        </tr>
    </table>

    <div class="section-title">Turnover &amp; Identity Verification (RA 10173 Masked)</div>
    <table>
        <tr>
            <td class="label">Received By (Claimant)</td>
            <td><strong>{{ $recovery->received_by_name }}</strong></td>
        </tr>
        <tr>
            <td class="label">Government ID Presented</td>
            <td>{{ $recovery->id_type }} (****{{ $recovery->id_last4 }})</td>
        </tr>
        <tr>
            <td class="label">Verification Outcome</td>
            <td>{{ ucfirst((string) $recovery->verification_outcome) }}</td>
        </tr>
        <tr>
            <td class="label">Turnover Date &amp; Time</td>
            <td>{{ $recovery->turnover_at?->format('M d, Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Processed By</td>
            <td>{{ $recovery->processor?->name ?? 'Institutional Staff' }}</td>
        </tr>
        <tr>
            <td class="label">Confirmed By</td>
            <td>{{ $recovery->confirmer?->name ?? 'Pending Confirmation' }}</td>
        </tr>
        <tr>
            <td class="label">Remarks</td>
            <td>{{ $recovery->remarks ?: 'Physical item released to verified owner.' }}</td>
        </tr>
    </table>

    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <strong>{{ $recovery->received_by_name }}</strong><br>
                <span class="meta">Claimant / Recipient Signature</span>
            </td>
            <td>
                <div class="sig-line"></div>
                <strong>{{ $recovery->confirmer?->name ?? $recovery->processor?->name ?? 'Authorized Officer' }}</strong><br>
                <span class="meta">Institutional Releasing Officer</span>
            </td>
        </tr>
    </table>

    <div class="footer">
        This document certifies physical release of the recovered property under the Boracay Lost &amp; Found System. Personal identifiers are masked in compliance with the Philippine Data Privacy Act of 2012 (RA 10173).
    </div>
</body>
</html>
