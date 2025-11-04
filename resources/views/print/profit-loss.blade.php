<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss by Program</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; color:#111827; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        thead { background: #7c3aed; color: #fff; }
        tfoot td { font-weight: 600; }
    </style>
</head>
<body>
    <h1>Profit & Loss by Program</h1>
    <p class="muted">Period: {{ \Carbon\Carbon::parse($start)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($end)->format('M d, Y') }}</p>
    <table>
        <thead>
            <tr>
                <th>Program</th>
                <th>Code</th>
                <th style="text-align:right">Income</th>
                <th style="text-align:right">Expenses</th>
                <th style="text-align:right">Profit/Loss</th>
                <th style="text-align:right">Margin %</th>
            </tr>
        </thead>
        <tbody>
            @php $ti=0; $te=0; $tp=0; @endphp
            @foreach ($rows as $r)
                @php $ti+=$r['income']; $te+=$r['expenses']; $tp+=$r['profit']; @endphp
                <tr>
                    <td>{{ $r['program'] }}</td>
                    <td>{{ $r['code'] }}</td>
                    <td style="text-align:right">UGX {{ number_format($r['income'], 0) }}</td>
                    <td style="text-align:right">UGX {{ number_format($r['expenses'], 0) }}</td>
                    <td style="text-align:right">UGX {{ number_format($r['profit'], 0) }}</td>
                    <td style="text-align:right">{{ number_format($r['margin'], 2) }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Grand Total</td>
                <td style="text-align:right">UGX {{ number_format($ti, 0) }}</td>
                <td style="text-align:right">UGX {{ number_format($te, 0) }}</td>
                <td style="text-align:right">UGX {{ number_format($tp, 0) }}</td>
                <td style="text-align:right">{{ $ti > 0 ? number_format(($tp/$ti)*100, 2) : '0.00' }}%</td>
            </tr>
        </tfoot>
    </table>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
