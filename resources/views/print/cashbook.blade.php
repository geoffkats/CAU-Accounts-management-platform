<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cashbook - {{ $account->name }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        @page { size: A4; margin: 12mm; }
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; color: #111827; }
        .brand-bar { height: 6px; background: linear-gradient(90deg, #0b3b66, #f59e0b); margin: -20px -20px 16px; }
        .header { margin-bottom: 14px; border-bottom: 1px solid #d1d5db; padding-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .company-name { font-size: 18px; font-weight: bold; letter-spacing: 0.2px; }
        .company-meta { color: #4b5563; font-size: 10px; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: bold; color: #0b3b66; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #0b3b66; color: #fff; padding: 8px; text-align: left; border: 1px solid #e5e7eb; }
        td { padding: 7px 8px; border: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f9f9f9; }
        .total-row { background-color: #e9e9e9; font-weight: bold; }
        .print-btn { margin: 12px 0; padding: 8px 12px; background: #0b3b66; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn no-print">🖨️ Print Cashbook</button>
    @php
        $settings = $settings ?? \App\Models\CompanySetting::first();
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
                    <div class="report-title">Cashbook - {{ strtoupper($account->name) }}</div>
                    <div class="company-meta">Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</div>
                    @if($transactionType !== 'all')
                        <div class="company-meta">Filter: {{ ucfirst($transactionType) }}</div>
                    @endif
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
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th class="text-right">Receipts</th>
                <th class="text-right">Payments</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-gray">
                <td colspan="5" class="font-bold">Opening Balance</td>
                <td class="text-right font-bold">{{ $currency->symbol }} {{ number_format($openingBalance, 2) }}</td>
            </tr>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->journalEntry->date->format('M d, Y') }}</td>
                <td>{{ $transaction->description }}</td>
                <td>{{ $transaction->journalEntry->reference }}</td>
                <td class="text-right">{{ $transaction->debit > 0 ? $currency->symbol . ' ' . number_format($transaction->debit, 2) : '-' }}</td>
                <td class="text-right">{{ $transaction->credit > 0 ? $currency->symbol . ' ' . number_format($transaction->credit, 2) : '-' }}</td>
                <td class="text-right font-bold">{{ $currency->symbol }} {{ number_format($transaction->running_balance, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">Closing Balance</td>
                <td class="text-right">{{ $currency->symbol }} {{ number_format($runningBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F d, Y H:i:s') }}
    </div>
</body>
</html>
