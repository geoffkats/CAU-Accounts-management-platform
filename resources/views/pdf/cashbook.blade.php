<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cashbook - {{ $account->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .report-title { font-size: 14px; font-weight: bold; margin-top: 5px; }
        .period { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #f5f5f5; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f9f9f9; }
        .total-row { background-color: #e9e9e9; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings->company_name ?? 'Company Name' }}</div>
        <div class="report-title">CASHBOOK - {{ strtoupper($account->name) }}</div>
        <div class="period">Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 40%;">Description</th>
                <th style="width: 15%;">Reference</th>
                <th style="width: 11%;" class="text-right">Receipts</th>
                <th style="width: 11%;" class="text-right">Payments</th>
                <th style="width: 11%;" class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-gray">
                <td colspan="5" class="font-bold">Opening Balance</td>
                <td class="text-right font-bold">{{ number_format($openingBalance, 2) }}</td>
            </tr>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->journalEntry->date->format('M d, Y') }}</td>
                <td>{{ $transaction->description }}</td>
                <td>{{ $transaction->journalEntry->reference }}</td>
                <td class="text-right">{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '-' }}</td>
                <td class="text-right">{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '-' }}</td>
                <td class="text-right font-bold">{{ number_format($transaction->running_balance, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">Closing Balance</td>
                <td class="text-right">{{ number_format($runningBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px; text-align: center; color: #666;">
        Generated on {{ now()->format('F d, Y H:i:s') }}
    </div>
</body>
</html>
