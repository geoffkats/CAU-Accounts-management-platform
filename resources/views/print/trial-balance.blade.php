<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trial Balance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>@media print {.no-print{display:none}}</style>
</head>
<body class="p-8">
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Trial Balance</h1>
        <div class="text-gray-600">Period: {{ $start }} to {{ $end }}</div>
    </div>

    <table class="w-full border-collapse">
        <thead>
        <tr class="bg-gray-100">
            <th class="border px-3 py-2 text-left">Code</th>
            <th class="border px-3 py-2 text-left">Account</th>
            <th class="border px-3 py-2 text-right">Debit</th>
            <th class="border px-3 py-2 text-right">Credit</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td class="border px-3 py-2 font-mono">{{ $r['code'] }}</td>
                <td class="border px-3 py-2">{{ $r['name'] }}</td>
                <td class="border px-3 py-2 text-right">{{ number_format($r['debit'], 2) }}</td>
                <td class="border px-3 py-2 text-right">{{ number_format($r['credit'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="bg-gray-100">
            <th colspan="2" class="border px-3 py-2 text-right">Totals</th>
            <th class="border px-3 py-2 text-right">{{ number_format($totals['debit'], 2) }}</th>
            <th class="border px-3 py-2 text-right">{{ number_format($totals['credit'], 2) }}</th>
        </tr>
        </tfoot>
    </table>
    @if(abs(($totals['debit'] ?? 0) - ($totals['credit'] ?? 0)) > 0.01)
        <div class="mt-4 text-red-600 font-semibold">Warning: Debits and Credits do not match.</div>
    @endif

    <div class="mt-6 no-print">
        <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white rounded">Print</button>
    </div>
    
</div>
</body>
</html>
