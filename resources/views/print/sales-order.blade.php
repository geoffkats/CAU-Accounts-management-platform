<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order - {{ $sale->invoice_number }}</title>
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
        .summary-box { border: 1px solid #0b3b66; background: #f9fafb; padding: 10px; text-align: right; }
        .summary-row { margin-bottom: 4px; }
        .summary-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-total { font-size: 18px; font-weight: bold; color: #0b3b66; }
        .notes { font-size: 10px; color: #4b5563; }
        .signature-box { border: 1px solid #e5e7eb; padding: 10px; margin-top: 12px; }
        .signature-line { border-top: 1px solid #111827; margin-top: 22px; padding-top: 4px; font-weight: bold; }
        .signature-meta { font-size: 10px; color: #6b7280; margin-top: 4px; }
        .footer { margin-top: 18px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $settings = $settings ?? \App\Models\CompanySetting::first();
        $logoPath = $settings->logo_path ?? null;
        $logoUrl = null;

        if ($logoPath) {
            $cleanPath = ltrim($logoPath, '/');
            if (str_starts_with($cleanPath, 'storage/')) {
                $cleanPath = substr($cleanPath, strlen('storage/'));
            }

            if (Storage::disk('public')->exists($cleanPath)) {
                $logoUrl = Storage::url($cleanPath);
            }
        }

        $subtotal = $sale->amount ?? 0;
        $tax = $sale->tax_amount ?? 0;
        $total = $sale->amount ?? 0;
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
                    <div class="doc-title">Sales Order</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: top;">
                    <div class="meta-box">
                        <div class="meta-row"><div class="meta-label">Order #</div><div class="meta-value">{{ $sale->invoice_number }}</div></div>
                        <div class="meta-row"><div class="meta-label">Date</div><div class="meta-value">{{ $sale->sale_date->format('M d, Y') }}</div></div>
                        <div class="meta-row"><div class="meta-label">Delivery Date</div><div class="meta-value">{{ optional($sale->delivery_date)->format('M d, Y') ?? 'N/A' }}</div></div>
                        <div class="meta-row"><div class="meta-label">Salesperson</div><div class="meta-value">{{ auth()->user()->name ?? 'System' }}</div></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td class="info-cell">
                    <div class="section-title">Customer Information</div>
                    <div><span class="info-label">Customer:</span> <span class="info-value">{{ $sale->customer->name ?? 'N/A' }}</span></div>
                    @if($sale->customer?->email)
                        <div><span class="info-label">Email:</span> <span class="info-value">{{ $sale->customer->email }}</span></div>
                    @endif
                    @if($sale->customer?->phone)
                        <div><span class="info-label">Phone:</span> <span class="info-value">{{ $sale->customer->phone }}</span></div>
                    @endif
                </td>
                <td class="info-cell">
                    <div class="section-title">Order Details</div>
                    <div><span class="info-label">Program:</span> <span class="info-value">{{ $sale->program->name ?? 'N/A' }}</span></div>
                    <div><span class="info-label">Status:</span> <span class="info-value">{{ $sale->order_status ? ucfirst($sale->order_status) : 'N/A' }}</span></div>
                    <div><span class="info-label">Currency:</span> <span class="info-value">{{ $sale->currency }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Order Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Tax</th>
                    <th class="right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $sale->description ?: 'Sales order summary' }}</td>
                    <td class="right">1</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($subtotal, 2) }}</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($tax, 2) }}</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($total, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Order Summary</div>
        <div class="summary-box">
            <div class="summary-row"><span class="summary-label">Subtotal:</span> {{ $sale->currency }} {{ number_format($subtotal, 2) }}</div>
            <div class="summary-row"><span class="summary-label">Tax:</span> {{ $sale->currency }} {{ number_format($tax, 2) }}</div>
            <div class="summary-row"><span class="summary-label">Order Total:</span> <span class="summary-total">{{ $sale->currency }} {{ number_format($total, 2) }}</span></div>
        </div>
    </div>

    <div class="section notes">
        <div class="section-title">Delivery & Payment Terms</div>
        <div>{{ $sale->terms_conditions ?: 'Delivery timeline and payment terms apply as agreed.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Signatures</div>
        <div class="signature-box">
            <div class="signature-line">Customer Signature</div>
            <div class="signature-meta">Name: ________________________________</div>
            <div class="signature-meta">Date: _________________________________</div>
            <div class="signature-line" style="margin-top: 26px;">Salesperson Signature</div>
            <div class="signature-meta">Name: {{ auth()->user()->name ?? 'System' }}</div>
            <div class="signature-meta">Date: _________________________________</div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y H:i:s') }} | Sales Order #{{ $sale->invoice_number }}</p>
    </div>
</body>
</html>
