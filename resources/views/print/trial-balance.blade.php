<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trial Balance</title>
    <style>
        @media print { .no-print { display: none; } }
        @page { size: A4; margin: 12mm; }
        body { font-family: "Helvetica", "Arial", sans-serif; color:#111827; font-size: 11px; margin: 0; padding: 0; }
        .brand-bar { height: 6px; background: linear-gradient(90deg, #0b3b66, #f59e0b); margin: -20px -20px 16px; }
        .header { margin-bottom: 14px; border-bottom: 1px solid #d1d5db; padding-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .company-name { font-size: 18px; font-weight: bold; letter-spacing: 0.2px; }
        .company-meta { color: #4b5563; font-size: 10px; margin-top: 4px; }
        h1 { font-size: 18px; margin: 0 0 6px; color: #0b3b66; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        thead { background: #0b3b66; color: #fff; }
        tfoot th { font-weight: 700; }
        .right { text-align: right; }
        .warning { margin-top: 10px; color: #b91c1c; font-weight: 700; }
        .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .print-btn { margin-top: 12px; padding: 8px 12px; background: #0b3b66; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
@php
    $settings = \App\Models\CompanySetting::first();
    $logoPath = $settings->logo_path ?? null;
    $logoFile = null;
    $logoUrl = null;

    if ($logoPath) {
        $cleanPath = ltrim($logoPath, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, strlen('storage/'));
        }

        if (Storage::disk('public')->exists($cleanPath)) {
            $logoFile = Storage::disk('public')->path($cleanPath);
            $logoUrl = Storage::url($cleanPath);
        }
    }
@endphp

<div class="brand-bar"></div>
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 20%; vertical-align: top;">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" class="logo" alt="Logo" />
                @endif
            </td>
            <td style="width: 60%; text-align: center; vertical-align: top;">
                <div class="company-name">{{ $settings->company_name ?? 'Company Name' }}</div>
                <div class="company-meta">{{ $settings->address ?? '' }}</div>
                <div class="company-meta">{{ $settings->phone ?? '' }} @if($settings->email) | {{ $settings->email }} @endif</div>
                <h1>Trial Balance</h1>
                <div class="muted">Period: {{ $start }} to {{ $end }}</div>
            </td>
            <td style="width: 20%; text-align: right; vertical-align: top;">
                <div class="company-meta">Generated {{ now()->format('M d, Y') }}</div>
            </td>
        </tr>
    </table>
</div>

    <table>
        <thead>
        <tr>
            <th>Code</th>
            <th>Account</th>
            <th class="right">Debit</th>
            <th class="right">Credit</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td>{{ $r['code'] }}</td>
                <td>{{ $r['name'] }}</td>
                <td class="right">{{ number_format($r['debit'], 2) }}</td>
                <td class="right">{{ number_format($r['credit'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="2" class="right">Totals</th>
            <th class="right">{{ number_format($totals['debit'], 2) }}</th>
            <th class="right">{{ number_format($totals['credit'], 2) }}</th>
        </tr>
        </tfoot>
    </table>
    @if(abs(($totals['debit'] ?? 0) - ($totals['credit'] ?? 0)) > 0.01)
        <div class="warning">Warning: Debits and Credits do not match.</div>
    @endif

    <div class="no-print">
        <button onclick="window.print()" class="print-btn">Print</button>
    </div>

    <div class="footer">
        Generated on {{ now()->format('F d, Y H:i:s') }}
    </div>
</body>
</html>
