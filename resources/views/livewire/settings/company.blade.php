<?php

use App\Models\CompanySetting;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public string $company_name = '';
    public string $company_email = '';
    public string $company_phone = '';
    public string $company_address = '';
    public string $tax_id = '';
    public string $currency = 'UGX';
    public string $currency_symbol = 'UGX';
    public string $date_format = 'd/m/Y';
    public string $timezone = 'Africa/Kampala';
    public ?string $lock_before_date = null;

    public function mount(): void
    {
        $s = CompanySetting::get();
        $this->company_name = $s->company_name ?? '';
        $this->company_email = $s->company_email ?? '';
        $this->company_phone = $s->company_phone ?? '';
        $this->company_address = $s->company_address ?? '';
        $this->tax_id = $s->tax_id ?? '';
        $this->currency = $s->currency ?? 'UGX';
        $this->currency_symbol = $s->currency_symbol ?? 'UGX';
        $this->date_format = $s->date_format ?? 'd/m/Y';
        $this->timezone = $s->timezone ?? 'Africa/Kampala';
        $this->lock_before_date = $s->lock_before_date?->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'company_name' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:500',
            'tax_id' => 'nullable|string|max:100',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'date_format' => 'required|string|max:50',
            'timezone' => 'required|string|max:100',
            'lock_before_date' => 'nullable|date',
        ]);

        $s = CompanySetting::first();
        if (!$s) { $s = new CompanySetting(); }
        $s->fill($validated);
        $s->save();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Company settings updated successfully.'
        ]);
    }

    public function setBaseCurrency(string $code): void
    {
        // Remove base flag from all currencies
        Currency::where('is_base', true)->update(['is_base' => false]);
        
        // Set selected currency as base
        $currency = Currency::where('code', $code)->first();
        if ($currency) {
            $currency->is_base = true;
            $currency->is_active = true;
            $currency->save();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Base currency changed to {$code}. All amounts will now be displayed in {$code}."
            ]);
        }
    }

    public function with(): array
    {
        return [
            'currencies' => Currency::orderBy('is_base', 'desc')->orderBy('code')->get(),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Company Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Update organization details and compliance policies</p>
    </div>

    <form wire:submit="save">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Company Name</label>
                    <input type="text" wire:model="company_name" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <input type="email" wire:model="company_email" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                    <input type="text" wire:model="company_phone" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address</label>
                    <input type="text" wire:model="company_address" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tax ID</label>
                    <input type="text" wire:model="tax_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                    <input type="text" wire:model="currency" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="UGX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency Symbol</label>
                    <input type="text" wire:model="currency_symbol" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="UGX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period Lock (No postings before)</label>
                    <input type="date" wire:model="lock_before_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Transactions dated before this date will be blocked.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg">Save Settings</button>
            </div>
        </div>
    </form>

    <!-- Multi-Currency Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Multi-Currency Settings</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage your base currency and exchange rates</p>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div>
                    <div class="text-sm font-medium text-blue-900 dark:text-blue-300">Current Base Currency</div>
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-400 mt-1">
                        {{ $baseCurrency->code }} ({{ $baseCurrency->symbol }})
                    </div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">All financial reports and summaries display in this currency</p>
                </div>
                <svg class="w-12 h-12 text-blue-400 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Available Currencies</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($currencies as $curr)
                    <div class="p-4 border {{ $curr->is_base ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }} rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $curr->code }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $curr->name }}</div>
                            </div>
                            <div class="text-2xl">{{ $curr->symbol }}</div>
                        </div>
                        @if($curr->is_base)
                            <div class="mt-2 px-2 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 text-xs font-semibold rounded text-center">
                                BASE CURRENCY
                            </div>
                        @else
                            <button 
                                wire:click="setBaseCurrency('{{ $curr->code }}')"
                                class="mt-2 w-full px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium rounded transition-colors">
                                Set as Base
                            </button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-amber-800 dark:text-amber-300">
                    <strong>Note:</strong> Changing the base currency will update all dashboard displays immediately. Historical transactions remain in their original currencies and are converted to the new base currency for reporting.
                </div>
            </div>

            <div class="pt-4">
                <a href="{{ route('settings.currencies') }}" class="inline-flex items-center text-sm font-medium text-purple-600 hover:text-purple-700 dark:text-purple-400">
                    Advanced Currency Management
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Update exchange rates, manage currencies, and view conversion reports</p>
            </div>
        </div>
    </div>
</div>
