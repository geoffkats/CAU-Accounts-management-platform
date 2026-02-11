<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Till Sale Receipt - {{ $sale->invoice_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 10px; margin: 0; padding: 0; color: #111827; }
        .receipt { width: 80mm; margin: 0 auto; }
        .center { text-align: center; }
        .logo { width: 48px; height: 48px; object-fit: contain; margin: 0 auto 6px; }
        .divider { border-top: 1px dashed #9ca3af; margin: 6px 0; }
        .row { display: table; width: 100%; margin-bottom: 3px; }
        .label { display: table-cell; width: 50%; color: #6b7280; }
        .value { display: table-cell; width: 50%; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 3px 0; border-bottom: 1px solid #e5e7eb; }
        th { text-align: left; font-weight: bold; }
        .right { text-align: right; }
        .total { font-weight: bold; }
        .footer { margin-top: 8px; text-align: center; color: #6b7280; }
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
        $discount = $sale->discount_amount ?? 0;
        $tax = $sale->tax_amount ?? 0;
        $total = $sale->amount ?? 0;
        $amountPaid = $sale->amount_paid ?? 0;
        $balance = $sale->remaining_balance ?? 0;
    @endphp

    <div class="receipt">
        <div class="center">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" class="logo" alt="Logo" />
            @endif
            <div><strong>{{ $settings->company_name ?? 'Company Name' }}</strong></div>
            <div>{{ $settings->address ?? '' }}</div>
            <div>{{ $settings->phone ?? '' }}</div>
        </div>

        <div class="divider"></div>

        <div class="row"><div class="label">Receipt #</div><div class="value">{{ $sale->receipt_number ?? $sale->invoice_number }}</div></div>
        <div class="row"><div class="label">Date</div><div class="value">{{ $sale->sale_date->format('M d, Y') }}</div></div>
        <div class="row"><div class="label">Cashier</div><div class="value">{{ auth()->user()->name ?? 'System' }}</div></div>
        <div class="row"><div class="label">POS Terminal</div><div class="value">N/A</div></div>

        <div class="divider"></div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $sale->description ?: 'Till sale item' }}</td>
                    <td class="right">1</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($subtotal, 0) }}</td>
                    <td class="right">{{ $sale->currency }} {{ number_format($total, 0) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="row"><div class="label">Subtotal</div><div class="value">{{ $sale->currency }} {{ number_format($subtotal, 0) }}</div></div>
        <div class="row"><div class="label">Discount</div><div class="value">{{ $sale->currency }} {{ number_format($discount, 0) }}</div></div>
        <div class="row"><div class="label">Tax</div><div class="value">{{ $sale->currency }} {{ number_format($tax, 0) }}</div></div>
        <div class="row total"><div class="label">Total</div><div class="value">{{ $sale->currency }} {{ number_format($total, 0) }}</div></div>
        <div class="row"><div class="label">Amount Paid</div><div class="value">{{ $sale->currency }} {{ number_format($amountPaid, 0) }}</div></div>
        <div class="row"><div class="label">Balance</div><div class="value">{{ $sale->currency }} {{ number_format($balance, 0) }}</div></div>

        <div class="divider"></div>

        <div class="row"><div class="label">Payment Method</div><div class="value">{{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? 'cash')) }}</div></div>

        <div class="footer">
            <div>Thank you for your business</div>
            <div>Returns accepted within 7 days with receipt</div>
            <div>{{ $settings->email ?? '' }}</div>
        </div>
    </div>
</body>
</html>
