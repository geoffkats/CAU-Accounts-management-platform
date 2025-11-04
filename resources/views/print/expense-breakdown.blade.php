<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Breakdown</title>
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
    <h1>Expense Breakdown</h1>
    <p class="muted">Period: {{ \Carbon\Carbon::parse($start)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($end)->format('M d, Y') }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ ucfirst($groupBy) }}</th>
                <th style="text-align:right">Count</th>
                <th style="text-align:right">Amount</th>
                <th style="text-align:right">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr>
                    <td>{{ $r['name'] }}</td>
                    <td style="text-align:right">{{ $r['count'] }}</td>
                    <td style="text-align:right">UGX {{ number_format($r['amount'], 0) }}</td>
                    <td style="text-align:right">{{ number_format(($r['amount'] / $total) * 100, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td style="text-align:right">{{ collect($rows)->sum('count') }}</td>
                <td style="text-align:right">UGX {{ number_format(collect($rows)->sum('amount'), 0) }}</td>
                <td style="text-align:right">100%</td>
            </tr>
        </tfoot>
    </table>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
