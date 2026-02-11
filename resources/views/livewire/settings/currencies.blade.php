<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;

state(['currencies', 'rates', 'lastUpdate', 'updating' => false, 'message' => '']);

mount(function () {
    $this->loadData();
});

$loadData = function () {
    $this->currencies = Currency::orderBy('is_base', 'desc')->orderBy('code')->get();
    $this->rates = ExchangeRate::with(['fromCurrency', 'toCurrency'])
        ->where('effective_date', now()->toDateString())
        ->orderBy('from_currency')
        ->get();
    $this->lastUpdate = ExchangeRate::latest('updated_at')->first()?->updated_at;
};

$updateRates = function () {
    $this->updating = true;
    $this->message = '';

    $service = app(CurrencyService::class);
    $result = $service->updateRatesFromAPI();

    if ($result['success']) {
        $this->message = $result['message'];
        $this->loadData();
        $this->dispatch('rates-updated');
    } else {
        $this->message = 'Error: ' . $result['message'];
    }

    $this->updating = false;
};

$toggleCurrency = function ($id) {
    $currency = Currency::find($id);
    if ($currency && !$currency->is_base) {
        $currency->is_active = !$currency->is_active;
        $currency->save();
        $this->loadData();
    }
};

$setBaseCurrency = function ($id) {
    $currency = Currency::find($id);
    if ($currency) {
        // Remove base flag from all currencies
        Currency::where('is_base', true)->update(['is_base' => false]);
        
        // Set this currency as base
        $currency->is_base = true;
        $currency->is_active = true; // Ensure base currency is active
        $currency->save();
        
        $this->message = "Base currency changed to {$currency->code}. All amounts will now be displayed in {$currency->code}.";
        $this->loadData();
    }
};

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white">Currency Management</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Manage currencies and exchange rates</p>
        </div>
        
        <button 
            wire:click="updateRates"
            wire:loading.attr="disabled"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
            <svg wire:loading.remove wire:target="updateRates" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg wire:loading wire:target="updateRates" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span wire:loading.remove wire:target="updateRates">Update Rates</span>
            <span wire:loading wire:target="updateRates">Updating...</span>
        </button>
    </div>

    @if($message)
        <div class="p-4 rounded-lg {{ str_contains($message, 'Error') ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' }}">
            {{ $message }}
        </div>
    @endif

    @if($lastUpdate)
        <div class="text-sm text-zinc-600 dark:text-zinc-400">
            Last updated: {{ $lastUpdate->diffForHumans() }}
        </div>
    @endif

    <!-- Currencies Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Available Currencies</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Configure which currencies are active for transactions</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Currency</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Code</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Symbol</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($currencies as $currency)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/30 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $currency->name }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $currency->code }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $currency->symbol }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($currency->is_base)
                                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    Base Currency
                                </span>
                            @else
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Foreign</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($currency->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-900/30 dark:text-zinc-400">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            @if(!$currency->is_base)
                                <div class="flex items-center justify-end gap-3">
                                    <button 
                                        wire:click="setBaseCurrency({{ $currency->id }})"
                                        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                        Set as Base
                                    </button>
                                    <button 
                                        wire:click="toggleCurrency({{ $currency->id }})"
                                        class="text-sm {{ $currency->is_active ? 'text-red-600 hover:text-red-700 dark:text-red-400' : 'text-green-600 hover:text-green-700 dark:text-green-400' }} font-medium">
                                        {{ $currency->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            @else
                                <span class="text-sm text-zinc-500 dark:text-zinc-400 italic">Base Currency</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Exchange Rates Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Current Exchange Rates</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Today's rates ({{ now()->format('F j, Y') }})</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">From</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">To</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Rate</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($rates as $rate)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/30 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $rate->from_currency }}
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            <svg class="w-4 h-4 inline text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ number_format($rate->rate, 6) }}
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $rate->to_currency }}
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $rate->source === 'api' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900/30 dark:text-zinc-400' }}">
                                {{ ucfirst($rate->source) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center">
                            <p class="text-zinc-500 dark:text-zinc-400">No exchange rates found for today</p>
                            <button wire:click="updateRates" class="mt-3 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                Update Rates Now
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-5">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">About Exchange Rates</h4>
                <p class="text-sm text-blue-700 dark:text-blue-400">
                    Exchange rates are automatically fetched from a free API service. Click "Update Rates" to get the latest rates. 
                    All transactions in foreign currencies are automatically converted to your base currency ({{ Currency::getBaseCurrency()?->code ?? 'UGX' }}) using the rate on the transaction date.
                </p>
            </div>
        </div>
    </div>
</div>
