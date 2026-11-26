<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Boracay Lost and Found — Analytics Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #123047; }
        h1 { font-size: 16px; margin: 0 0 4px 0; color: #123047; }
        h2 { font-size: 13px; margin: 16px 0 6px 0; color: #087EA4; border-bottom: 1px solid #d9e2ec; padding-bottom: 3px; }
        .meta { font-size: 10px; color: #52606d; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #d9e2ec; padding: 5px 6px; text-align: left; }
        th { background-color: #f4f7fb; font-weight: bold; }
        .kpi-table td { text-align: center; }
        .kpi-value { font-size: 15px; font-weight: bold; color: #123047; }
        .kpi-label { font-size: 9px; color: #52606d; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>Boracay Lost and Found System — Operational Analytics Report</h1>
    <div class="meta">
        Generated: {{ $generatedAt->format('F d, Y H:i') }} (Asia/Manila) |
        Prepared by: {{ $user->name }} |
        Date Range: {{ $filters['date_from'] ?? 'All' }} to {{ $filters['date_to'] ?? 'Present' }}
    </div>

    <table class="kpi-table">
        <tr>
            <td><div class="kpi-value">{{ $summary['total_lost'] }}</div><div class="kpi-label">Total Lost</div></td>
            <td><div class="kpi-value">{{ $summary['total_found'] }}</div><div class="kpi-label">Total Found</div></td>
            <td><div class="kpi-value">{{ $summary['verified'] }}</div><div class="kpi-label">Verified</div></td>
            <td><div class="kpi-value">{{ $summary['pending_verification'] }}</div><div class="kpi-label">Pending Verify</div></td>
            <td><div class="kpi-value">{{ $summary['pending_claims'] }}</div><div class="kpi-label">Pending Claims</div></td>
            <td><div class="kpi-value">{{ $summary['recovered'] }}</div><div class="kpi-label">Recovered</div></td>
            <td><div class="kpi-value">{{ $summary['unclaimed'] }}</div><div class="kpi-label">Unclaimed</div></td>
            <td><div class="kpi-value">{{ $summary['disposed'] }}</div><div class="kpi-label">Disposed</div></td>
        </tr>
    </table>

    <h2>Barangay Breakdown</h2>
    <table>
        <thead>
            <tr>
                <th>Barangay</th>
                <th>Lost</th>
                <th>Found</th>
                <th>Total</th>
                <th>Share</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barangayBreakdown as $b)
                <tr>
                    <td>{{ $b['name'] }}</td>
                    <td>{{ $b['lost'] }}</td>
                    <td>{{ $b['found'] }}</td>
                    <td>{{ $b['total'] }}</td>
                    <td>{{ $b['percent'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Institution Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Institution</th>
                <th>Lost</th>
                <th>Found</th>
                <th>Verified</th>
                <th>Recovered</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($institutionBreakdown as $inst)
                <tr>
                    <td>{{ $inst['name'] }}</td>
                    <td>{{ $inst['lost'] }}</td>
                    <td>{{ $inst['found'] }}</td>
                    <td>{{ $inst['verified'] }}</td>
                    <td>{{ $inst['recovered'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Report Records (Up to 200 Most Recent)</h2>
    <table>
        <thead>
            <tr>
                <th>Reference No</th>
                <th>Type</th>
                <th>Item Name</th>
                <th>Category</th>
                <th>Barangay</th>
                <th>Occurred Date</th>
                <th>Verification</th>
                <th>Item Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ ucfirst($row->type->value) }}</td>
                    <td>{{ $row->item_name }}</td>
                    <td>{{ $row->category?->label() }}</td>
                    <td>{{ $row->barangay?->name }}</td>
                    <td>{{ $row->occurred_date?->toDateString() }}</td>
                    <td>{{ $row->verification_status->value }}</td>
                    <td>{{ $row->item_status->value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
