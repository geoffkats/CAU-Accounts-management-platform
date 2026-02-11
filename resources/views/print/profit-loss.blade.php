<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement</title>
    <style>
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
        tfoot td { font-weight: 600; }
        .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
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
                    <h1>Profit &amp; Loss Statement</h1>
                    <div class="muted">Period: {{ \Carbon\Carbon::parse($periodStart)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($periodEnd)->format('M d, Y') }}</div>
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
                <th>Section</th>
                <th>Account</th>
                <th style="text-align:right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Sales</strong></td>
                <td></td>
                <td style="text-align:right"><strong>{{ $baseCurrency->symbol }} {{ number_format($totalSales, 2) }}</strong></td>
            </tr>
            @foreach ($salesRows as $row)
                <tr>
                    <td></td>
                    <td>{{ $row['code'] }} - {{ $row['name'] }}</td>
                    <td style="text-align:right">{{ $baseCurrency->symbol }} {{ number_format($row['amount'], 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td><strong>Cost of Sales</strong></td>
                <td></td>
                <td style="text-align:right"><strong>{{ $baseCurrency->symbol }} {{ number_format($totalCost, 2) }}</strong></td>
            </tr>
            @foreach ($costRows as $row)
                <tr>
                    <td></td>
                    <td>{{ $row['code'] }} - {{ $row['name'] }}</td>
                    <td style="text-align:right">{{ $baseCurrency->symbol }} {{ number_format($row['amount'], 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td><strong>Gross Profit</strong></td>
                <td></td>
                <td style="text-align:right"><strong>{{ $baseCurrency->symbol }} {{ number_format($grossProfit, 2) }}</strong></td>
            </tr>

            <tr>
                <td><strong>Expenses</strong></td>
                <td></td>
                <td style="text-align:right"><strong>{{ $baseCurrency->symbol }} {{ number_format($totalExpenses, 2) }}</strong></td>
            </tr>
            @foreach ($expenseRows as $row)
                <tr>
                    <td></td>
                    <td>{{ $row['code'] }} - {{ $row['name'] }}</td>
                    <td style="text-align:right">{{ $baseCurrency->symbol }} {{ number_format($row['amount'], 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td><strong>Net Profit/Loss</strong></td>
                <td></td>
                <td style="text-align:right"><strong>{{ $baseCurrency->symbol }} {{ number_format($netProfit, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    <div class="footer">
        Generated on {{ now()->format('F d, Y H:i:s') }}
    </div>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
