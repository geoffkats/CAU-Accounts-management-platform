<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Voucher - {{ $payment->voucher_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: "Helvetica", "Arial", sans-serif; font-size: 11px; margin: 0; padding: 0; color: #111827; }
        .brand-bar { height: 6px; background: linear-gradient(90deg, #0b3b66, #f59e0b); margin: -20px -20px 16px; }
        .header { margin-bottom: 18px; border-bottom: 1px solid #d1d5db; padding-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 80px; height: 80px; object-fit: contain; }
        .company-name { font-size: 18px; font-weight: bold; letter-spacing: 0.2px; }
        .company-meta { color: #4b5563; font-size: 10px; margin-top: 4px; }
        .voucher-title { font-size: 16px; font-weight: bold; text-transform: uppercase; color: #0b3b66; }
        .voucher-number { font-size: 12px; color: #374151; text-align: right; }
        .section { margin-bottom: 14px; }
        .section-title { font-weight: bold; font-size: 12px; margin-bottom: 8px; background: #f3f4f6; padding: 6px 8px; border-left: 4px solid #f59e0b; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-cell { width: 50%; padding: 6px 8px; vertical-align: top; }
        .info-label { font-weight: bold; color: #374151; display: inline-block; width: 38%; }
        .info-value { color: #111827; }
        .amount-box { background: #f9fafb; padding: 14px; text-align: center; border: 1px solid #0b3b66; margin: 18px 0; }
        .amount-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .amount-value { font-size: 22px; font-weight: bold; margin: 8px 0; color: #0b3b66; }
        .amount-words { font-size: 10px; color: #6b7280; font-style: italic; }
        .signature-box { border: 1px solid #d1d5db; padding: 12px; margin-top: 14px; }
        .signature-table { width: 100%; border-collapse: collapse; }
        .signature-cell { width: 50%; padding: 10px 8px; }
        .signature-line { border-top: 1px solid #111827; margin-top: 32px; padding-top: 6px; font-weight: bold; }
        .signature-meta { font-size: 10px; color: #6b7280; margin-top: 4px; }
        .footer { margin-top: 18px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $logoPath = $settings->logo_path ?? null;
        $logoFile = null;

        if ($logoPath) {
            $cleanPath = ltrim($logoPath, '/');
            if (str_starts_with($cleanPath, 'storage/')) {
                $cleanPath = substr($cleanPath, strlen('storage/'));
            }

            if (Storage::disk('public')->exists($cleanPath)) {
                $logoFile = Storage::disk('public')->path($cleanPath);
            }
        }
    @endphp

    <div class="brand-bar"></div>

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 20%; vertical-align: top;">
                    @if($logoFile)
                        <img src="{{ $logoFile }}" class="logo" alt="Logo" />
                    @endif
                </td>
                <td style="width: 60%; text-align: center; vertical-align: top;">
                    <div class="company-name">{{ $settings->company_name ?? 'Company Name' }}</div>
                    <div class="company-meta">{{ $settings->address ?? '' }}</div>
                    <div class="company-meta">{{ $settings->phone ?? '' }} @if($settings->email) | {{ $settings->email }} @endif</div>
                    <div class="voucher-title">Payment Voucher</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: top;">
                    <div class="voucher-number">Voucher #{{ $payment->voucher_number }}</div>
                    <div class="company-meta">{{ $payment->payment_date->format('M d, Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment Information</div>
        <table class="info-table">
            <tr>
                <td class="info-cell">
                    <span class="info-label">Pay To:</span>
                    <span class="info-value">{{ $payment->expense->vendor->name ?? $payment->expense->staff->first_name . ' ' . $payment->expense->staff->last_name ?? 'N/A' }}</span>
                </td>
                <td class="info-cell">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value">{{ ucfirst($payment->payment_method ?? 'N/A') }}</span>
                </td>
            </tr>
            <tr>
                <td class="info-cell">
                    <span class="info-label">Payment Account:</span>
                    <span class="info-value">{{ $payment->paymentAccount->name ?? 'N/A' }} ({{ $payment->paymentAccount->code ?? '' }})</span>
                </td>
                <td class="info-cell">
                    <span class="info-label">Reference:</span>
                    <span class="info-value">{{ $payment->payment_reference ?? 'N/A' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Expense Details</div>
        <table class="info-table">
            <tr>
                <td class="info-cell">
                    <span class="info-label">Description:</span>
                    <span class="info-value">{{ $payment->expense->description }}</span>
                </td>
                <td class="info-cell">
                    <span class="info-label">Expense Date:</span>
                    <span class="info-value">{{ $payment->expense->expense_date->format('F d, Y') }}</span>
                </td>
            </tr>
            <tr>
                <td class="info-cell">
                    <span class="info-label">Program:</span>
                    <span class="info-value">{{ $payment->expense->program->name ?? 'N/A' }}</span>
                </td>
                <td class="info-cell">
                    <span class="info-label">Category:</span>
                    <span class="info-value">{{ ucfirst($payment->expense->category ?? 'N/A') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="amount-box">
        <div class="amount-label">Amount Paid</div>
        <div class="amount-value">{{ $currency->symbol ?? '' }} {{ number_format($payment->amount, 2) }}</div>
        <div class="amount-words">{{ ucwords(\NumberFormatter::create('en', \NumberFormatter::SPELLOUT)->format($payment->amount)) }} Only</div>
    </div>

    @if($payment->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <div>{{ $payment->notes }}</div>
    </div>
    @endif

    <div class="signature-box">
        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line">Prepared By</div>
                    <div class="signature-meta">{{ auth()->user()->name ?? 'System' }}</div>
                    <div class="signature-meta">{{ $payment->created_at->format('M d, Y H:i') }}</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line">Received By</div>
                    <div class="signature-meta">{{ $payment->expense->vendor->name ?? $payment->expense->staff->first_name ?? 'Payee' }}</div>
                    <div class="signature-meta">Date: _______________</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y H:i:s') }} | Voucher #{{ $payment->voucher_number }}</p>
    </div>
</body>
</html>
