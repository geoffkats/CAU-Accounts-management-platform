<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Balance Sheet</title>
	<style>
		@page { size: A4; margin: 12mm; }
		body { font-family: "Helvetica", "Arial", sans-serif; font-size: 11px; color:#111827; margin: 0; padding: 0; }
		.brand-bar { height: 6px; background: linear-gradient(90deg, #0b3b66, #f59e0b); margin: -20px -20px 16px; }
		.header { margin-bottom: 14px; border-bottom: 1px solid #d1d5db; padding-bottom: 10px; }
		.header-table { width: 100%; border-collapse: collapse; }
		.logo { width: 70px; height: 70px; object-fit: contain; }
		.company-name { font-size: 18px; font-weight: bold; letter-spacing: 0.2px; }
		.company-meta { color: #4b5563; font-size: 10px; margin-top: 4px; }
		h1 { font-size: 18px; margin: 0 0 4px; color: #0b3b66; text-transform: uppercase; }
		.muted { color: #6b7280; font-size: 10px; }
		table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 14px; }
		th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
		thead { background: #0b3b66; color: #fff; }
		.section { background: #f3f4f6; font-weight: 700; }
		.total { font-weight: 700; background: #f9fafb; }
		.right { text-align: right; }
		.footer { margin-top: 14px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
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
					<h1>Balance Sheet</h1>
					<div class="muted">As of {{ $asOfLabel }}</div>
					@if($showComparative)
						<div class="muted">Comparative as of {{ $priorLabel }}</div>
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
				<th>Section</th>
				<th>Account</th>
				@if($showComparative)
					<th class="right">Prior</th>
				@endif
				<th class="right">Current</th>
			</tr>
		</thead>
		<tbody>
			<tr class="section"><td colspan="{{ $showComparative ? 4 : 3 }}">Fixed Assets</td></tr>
			@foreach($fixedAssets as $row)
				<tr>
					<td></td>
					<td>{{ $row['code'] }} - {{ $row['name'] }}</td>
					@if($showComparative)
						<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['prior'], 2) }}</td>
					@endif
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['balance'], 2) }}</td>
				</tr>
			@endforeach
			<tr class="total">
				<td>Total Fixed Assets</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalFixedAssetsPrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalFixedAssets, 2) }}</td>
			</tr>

			<tr class="section"><td colspan="{{ $showComparative ? 4 : 3 }}">Current Assets</td></tr>
			@foreach($currentAssets as $row)
				<tr>
					<td></td>
					<td>{{ $row['code'] }} - {{ $row['name'] }}</td>
					@if($showComparative)
						<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['prior'], 2) }}</td>
					@endif
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['balance'], 2) }}</td>
				</tr>
			@endforeach
			<tr class="total">
				<td>Total Current Assets</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalCurrentAssetsPrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalCurrentAssets, 2) }}</td>
			</tr>

			<tr class="total">
				<td>Total Assets</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalAssetsPrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalAssets, 2) }}</td>
			</tr>

			<tr class="section"><td colspan="{{ $showComparative ? 4 : 3 }}">Long-term Liabilities</td></tr>
			@foreach($longTermLiabilities as $row)
				<tr>
					<td></td>
					<td>{{ $row['code'] }} - {{ $row['name'] }}</td>
					@if($showComparative)
						<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['prior'], 2) }}</td>
					@endif
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['balance'], 2) }}</td>
				</tr>
			@endforeach
			<tr class="total">
				<td>Total Long-term Liabilities</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalLongTermLiabilitiesPrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalLongTermLiabilities, 2) }}</td>
			</tr>

			<tr class="section"><td colspan="{{ $showComparative ? 4 : 3 }}">Short-term Liabilities</td></tr>
			@foreach($shortTermLiabilities as $row)
				<tr>
					<td></td>
					<td>{{ $row['code'] }} - {{ $row['name'] }}</td>
					@if($showComparative)
						<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['prior'], 2) }}</td>
					@endif
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['balance'], 2) }}</td>
				</tr>
			@endforeach
			<tr class="total">
				<td>Total Short-term Liabilities</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalShortTermLiabilitiesPrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalShortTermLiabilities, 2) }}</td>
			</tr>

			<tr class="total">
				<td>Total Liabilities</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalLiabilitiesPrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalLiabilities, 2) }}</td>
			</tr>

			<tr class="section"><td colspan="{{ $showComparative ? 4 : 3 }}">Owners' Equity</td></tr>
			@foreach($equityRows as $row)
				<tr>
					<td></td>
					<td>{{ $row['code'] }} - {{ $row['name'] }}</td>
					@if($showComparative)
						<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['prior'], 2) }}</td>
					@endif
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($row['balance'], 2) }}</td>
				</tr>
			@endforeach
			<tr class="total">
				<td>Total Equity</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalEquityPrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalEquity, 2) }}</td>
			</tr>

			<tr class="total">
				<td>Net Income (Included in Equity)</td>
				<td></td>
				@if($showComparative)
					<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($netIncomePrior, 2) }}</td>
				@endif
				<td class="right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($netIncome, 2) }}</td>
			</tr>
		</tbody>
	</table>

	<div class="footer">
		Generated on {{ now()->format('F d, Y H:i:s') }}
	</div>

	<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
