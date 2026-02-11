<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $sale->invoice_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; color: #111827; }
        .brand-bar { height: 6px; background: linear-gradient(90deg, #0b3b66, #f59e0b); margin: -20px -20px 16px; }
        .header { margin-bottom: 16px; border-bottom: 1px solid #d1d5db; padding-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 80px; height: 80px; object-fit: contain; }
        .company-name { font-size: 18px; font-weight: bold; letter-spacing: 0.2px; }
        .company-meta { color: #4b5563; font-size: 10px; margin-top: 4px; }
        .doc-title { font-size: 16px; font-weight: bold; text-transform: uppercase; color: #0b3b66; }
        .doc-number { font-size: 12px; color: #374151; text-align: right; }
        .meta-box { border: 1px solid #e5e7eb; background: #f9fafb; padding: 8px 10px; }
        .meta-row { display: table; width: 100%; margin-bottom: 4px; }
        .meta-label { display: table-cell; width: 45%; font-weight: bold; color: #374151; }
        .meta-value { display: table-cell; width: 55%; text-align: right; }
        .section { margin-bottom: 14px; }
        .section-title { font-weight: bold; font-size: 12px; margin-bottom: 8px; background: #f3f4f6; padding: 6px 8px; border-left: 4px solid #f59e0b; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-cell { width: 50%; padding: 6px 8px; vertical-align: top; }
        .info-label { font-weight: bold; color: #374151; display: inline-block; width: 38%; }
        .info-value { color: #111827; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items-table th { background: #0b3b66; color: #fff; padding: 6px 8px; text-align: left; border: 1px solid #e5e7eb; }
        .items-table td { padding: 6px 8px; border: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .amount-box { background: #f9fafb; padding: 14px; text-align: right; margin: 18px 0; border: 1px solid #0b3b66; }
        .amount-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .amount-value { font-size: 22px; font-weight: bold; margin: 8px 0; color: #0b3b66; }
        .notes { font-size: 10px; color: #4b5563; }
        .approval-box { border: 1px solid #e5e7eb; padding: 10px; margin-top: 12px; }
        .approval-line { border-top: 1px solid #111827; margin-top: 22px; padding-top: 4px; font-weight: bold; }
        .approval-meta { font-size: 10px; color: #6b7280; margin-top: 4px; }
        .footer { margin-top: 18px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
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
                    <div class="doc-title">Invoice</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: top;">
                    <div class="doc-number">Invoice #{{ $sale->invoice_number }}</div>
                    <div class="company-meta">{{ $sale->sale_date->format('M d, Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td class="info-cell">
                    <div class="section-title">Billed To</div>
                    <div><span class="info-label">Customer:</span> <span class="info-value">{{ $sale->customer->name ?? 'N/A' }}</span></div>
                    @if($sale->customer?->email)
                        <div><span class="info-label">Email:</span> <span class="info-value">{{ $sale->customer->email }}</span></div>
                    @endif
                    @if($sale->customer?->phone)
                        <div><span class="info-label">Phone:</span> <span class="info-value">{{ $sale->customer->phone }}</span></div>
                    @endif
                </td>
                <td class="info-cell">
                    <div class="section-title">Invoice Details</div>
                    <div><span class="info-label">Program:</span> <span class="info-value">{{ $sale->program->name ?? 'N/A' }}</span></div>
                    <div><span class="info-label">Currency:</span> <span class="info-value">{{ $sale->currency }}</span></div>
                    <div><span class="info-label">Product Area:</span> <span class="info-value">{{ $sale->product_area_code ?? 'N/A' }}</span></div>
                </td>
                <td class="info-cell">
                    <div class="section-title">Metadata</div>
                    <div class="meta-box">
                        <div class="meta-row"><div class="meta-label">Invoice #</div><div class="meta-value">{{ $sale->invoice_number }}</div></div>
                        <div class="meta-row"><div class="meta-label">Invoice Date</div><div class="meta-value">{{ $sale->sale_date->format('M d, Y') }}</div></div>
                        <div class="meta-row"><div class="meta-label">Due Date</div><div class="meta-value">{{ optional($sale->due_date)->format('M d, Y') ?? 'N/A' }}</div></div>
                        <div class="meta-row"><div class="meta-label">Status</div><div class="meta-value">{{ strtoupper(str_replace('_', ' ', $sale->status)) }}</div></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Line Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Rate</th>
                    <th class="right">Tax</th>
                    <th class="right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $sale->description ?: 'Invoice summary' }}</td>
                    <td class="right">1</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($sale->amount, 2) }}</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($sale->tax_amount ?? 0, 2) }}</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($sale->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="amount-box">
        <div class="amount-label">Total Amount Due</div>
        <div class="amount-value">{{ $sale->currency }} {{ number_format($sale->amount, 2) }}</div>
        <div class="amount-label">Amount Paid: {{ $sale->currency }} {{ number_format($sale->amount_paid, 2) }}</div>
        <div class="amount-label">Balance Due: {{ $sale->currency }} {{ number_format($sale->remaining_balance, 2) }}</div>
    </div>

    <div class="section notes">
        <div class="section-title">Notes & Terms</div>
        <div>{{ $sale->terms_conditions ?: 'Payment terms apply. Please reference the invoice number on payment.' }}</div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y H:i:s') }} | Invoice #{{ $sale->invoice_number }}</p>
    </div>
</body>
</html>
