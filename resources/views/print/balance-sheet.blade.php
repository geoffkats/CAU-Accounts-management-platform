<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet - {{ $settings->company_name ?? config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            padding: 20mm;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-date {
            font-size: 10pt;
            color: #555;
        }
        .content {
            margin-top: 20px;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            background: #f5f5f5;
            padding: 8px 12px;
            border-bottom: 2px solid #333;
            margin-bottom: 10px;
        }
        .section-body {
            padding-left: 12px;
        }
        .account-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        .account-name {
            flex: 1;
        }
        .account-code {
            color: #666;
            margin-right: 8px;
            font-size: 9pt;
        }
        .amount-container {
            display: flex;
            gap: 30px;
            align-items: center;
            min-width: 280px;
            justify-content: flex-end;
        }
        .amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
            min-width: 120px;
        }
        .amount-prior {
            text-align: right;
            font-variant-numeric: tabular-nums;
            min-width: 100px;
            color: #666;
            font-size: 9pt;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 12px;
            font-weight: bold;
            background: #f9f9f9;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            margin-top: 5px;
        }
        .net-income-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #e5e5e5;
            font-style: italic;
            color: #333;
        }
        .equation-check {
            margin-top: 30px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 10pt;
        }
        .balanced {
            color: #059669;
            font-weight: bold;
        }
        .imbalance {
            color: #dc2626;
            font-weight: bold;
        }
        @media print {
            body {
                padding: 10mm;
            }
            .section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings->company_name ?? config('app.name') }}</div>
        <div class="report-title">Balance Sheet</div>
        <div class="report-date">
            As of {{ $asOfLabel }}
            @if($showComparative)
                vs {{ $priorLabel }}
            @endif
        </div>
    </div>

    <div class="content">
        <!-- Assets -->
        <div class="section">
            <div class="section-title">ASSETS</div>
            <div class="section-body">
                @forelse($assetRows as $row)
                    <div class="account-row">
                        <div class="account-name">
                            <span class="account-code">{{ $row['code'] }}</span>
                            {{ $row['name'] }}
                        </div>
                        <div class="amount-container">
                            @if($showComparative)
                                <div class="amount-prior">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</div>
                            @endif
                            <div class="amount">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 10px 0; color: #999;">No asset balances.</div>
                @endforelse
            </div>
            <div class="total-row">
                <span>TOTAL ASSETS</span>
                <div class="amount-container">
                    @if($showComparative)
                        <div class="amount-prior">{{ ($baseCurrency->symbol ?? '') }} {{ $totalAssetsPrior < 0 ? '(' . number_format(abs($totalAssetsPrior), 2) . ')' : number_format($totalAssetsPrior, 2) }}</div>
                    @endif
                    <div class="amount">{{ ($baseCurrency->symbol ?? '') }} {{ $totalAssets < 0 ? '(' . number_format(abs($totalAssets), 2) . ')' : number_format($totalAssets, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Liabilities -->
        <div class="section">
            <div class="section-title">LIABILITIES</div>
            <div class="section-body">
                @forelse($liabilityRows as $row)
                    <div class="account-row">
                        <div class="account-name">
                            <span class="account-code">{{ $row['code'] }}</span>
                            {{ $row['name'] }}
                        </div>
                        <div class="amount-container">
                            @if($showComparative)
                                <div class="amount-prior">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</div>
                            @endif
                            <div class="amount">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 10px 0; color: #999;">No liability balances.</div>
                @endforelse
            </div>
            <div class="total-row">
                <span>TOTAL LIABILITIES</span>
                <div class="amount-container">
                    @if($showComparative)
                        <div class="amount-prior">{{ ($baseCurrency->symbol ?? '') }} {{ $totalLiabilitiesPrior < 0 ? '(' . number_format(abs($totalLiabilitiesPrior), 2) . ')' : number_format($totalLiabilitiesPrior, 2) }}</div>
                    @endif
                    <div class="amount">{{ ($baseCurrency->symbol ?? '') }} {{ $totalLiabilities < 0 ? '(' . number_format(abs($totalLiabilities), 2) . ')' : number_format($totalLiabilities, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Equity -->
        <div class="section">
            <div class="section-title">EQUITY</div>
            <div class="section-body">
                @forelse($equityRows as $row)
                    <div class="account-row">
                        <div class="account-name">
                            <span class="account-code">{{ $row['code'] }}</span>
                            {{ $row['name'] }}
                        </div>
                        <div class="amount-container">
                            @if($showComparative)
                                <div class="amount-prior">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</div>
                            @endif
                            <div class="amount">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 10px 0; color: #999;">No equity balances.</div>
                @endforelse
                <div class="net-income-row">
                    <div class="account-name">Net Income (Current Period)</div>
                    <div class="amount-container">
                        @if($showComparative)
                            <div class="amount-prior">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncomePrior < 0 ? '(' . number_format(abs($netIncomePrior), 2) . ')' : number_format($netIncomePrior, 2) }}</div>
                        @endif
                        <div class="amount">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncome < 0 ? '(' . number_format(abs($netIncome), 2) . ')' : number_format($netIncome, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="total-row">
                <span>TOTAL EQUITY</span>
                <div class="amount-container">
                    @if($showComparative)
                        <div class="amount-prior">{{ ($baseCurrency->symbol ?? '') }} {{ $totalEquityPrior < 0 ? '(' . number_format(abs($totalEquityPrior), 2) . ')' : number_format($totalEquityPrior, 2) }}</div>
                    @endif
                    <div class="amount">{{ ($baseCurrency->symbol ?? '') }} {{ $totalEquity < 0 ? '(' . number_format(abs($totalEquity), 2) . ')' : number_format($totalEquity, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Equation Check -->
        <div class="equation-check">
            <div style="margin-bottom: 8px;">
                <strong>Accounting Equation Check:</strong>
            </div>
            <div>
                Assets: {{ ($baseCurrency->symbol ?? '') }} {{ $totalAssets < 0 ? '(' . number_format(abs($totalAssets), 2) . ')' : number_format($totalAssets, 2) }}
                =
                Liabilities + Equity: {{ ($baseCurrency->symbol ?? '') }} {{ ($totalLiabilities + $equityWithEarnings) < 0 ? '(' . number_format(abs($totalLiabilities + $equityWithEarnings), 2) . ')' : number_format(($totalLiabilities + $equityWithEarnings), 2) }}
            </div>
            <div style="margin-top: 8px;">
                @if(abs(($totalAssets - ($totalLiabilities + $equityWithEarnings))) < 0.01)
                    <span class="balanced">✓ BALANCED</span>
                @else
                    <span class="imbalance">⚠ IMBALANCE</span>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
