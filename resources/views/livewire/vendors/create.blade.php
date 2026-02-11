<?php

use App\Models\Vendor;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $vendor_type = '';
    public string $service_type = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $tin = '';
    public string $account_number = '';
    public string $payment_method = '';
    public string $bank_name = '';
    public string $bank_account_number = '';
    public string $bank_account_name = '';
    public string $mobile_money_provider = '';
    public string $mobile_money_number = '';
    public string $business_type = '';
    public string $currency = 'UGX';
    public string $ussd_provider = '';
    public string $ussd_number = '';

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'vendor_type' => ['required', 'string', 'in:utility,supplier,contractor,government'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'tin' => ['nullable', 'string', 'max:50'],
            'business_type' => ['nullable', 'string', 'in:individual,company,government'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_method' => ['nullable', 'string', 'in:bank,mobile_money,cash,ussd'],
        ];

        // Conditional validation based on vendor type
        if ($this->vendor_type === 'utility') {
            $rules['account_number'] = ['required', 'string', 'max:100'];
        }

        // Conditional validation based on payment method
        if ($this->payment_method === 'bank') {
            $rules['bank_name'] = ['required', 'string', 'max:255'];
            $rules['bank_account_number'] = ['required', 'string', 'max:50'];
            $rules['bank_account_name'] = ['required', 'string', 'max:255'];
        } elseif ($this->payment_method === 'mobile_money') {
            $rules['mobile_money_provider'] = ['required', 'string', 'in:mtn,airtel'];
            $rules['mobile_money_number'] = ['required', 'string', 'max:50'];
        } elseif ($this->payment_method === 'ussd') {
            $rules['ussd_provider'] = ['required', 'string', 'in:mtn,airtel'];
            $rules['ussd_number'] = ['required', 'string', 'max:50'];
        }

        $validated = $this->validate($rules);

        Vendor::create($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Vendor created successfully.'
        ]);

        $this->redirect(route('vendors.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('vendors.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 bg-clip-text text-transparent">
                Create Vendor
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Add a new supplier or service provider</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-6">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Vendor Information
            </h2>
            <p class="text-purple-100 mt-1 text-sm">Complete the form below with vendor details</p>
        </div>

        <form wire:submit="save" class="p-8 space-y-8">
            <!-- Basic Information Section -->
            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Basic Information</h3>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="lg:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Vendor Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name"
                               wire:model="name"
                               placeholder="e.g., UMEME Ltd, Office Supplies Ltd"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Vendor Type -->
                    <div>
                        <label for="vendor_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Vendor Type <span class="text-red-500">*</span>
                        </label>
                        <select id="vendor_type"
                                wire:model.live="vendor_type"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all"
                                required>
                            <option value="">Select Vendor Type</option>
                            <option value="utility">🔌 Utility (UMEME, NWSC, Internet Provider)</option>
                            <option value="supplier">📦 Supplier (Goods Provider)</option>
                            <option value="contractor">🔧 Contractor (Service Provider)</option>
                            <option value="government">🏛️ Government (URA, KCCA, Licenses)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select the type of vendor for proper classification</p>
                        @error('vendor_type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Service Type -->
                    <div>
                        <label for="service_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Service Type / Goods
                        </label>
                        <input type="text" 
                               id="service_type"
                               wire:model="service_type"
                               placeholder="e.g., Electricity, Office Supplies, Catering Services"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Specify what this vendor provides</p>
                        @error('service_type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Account/Meter Number (Only for Utilities) -->
                @if($vendor_type === 'utility')
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="p-2 bg-blue-500 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-blue-900 dark:text-blue-100">Utility Account Information</h4>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">Required for utility vendors to track meter or account numbers</p>
                            </div>
                        </div>
                        
                        <label for="account_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Account/Meter Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="account_number"
                               wire:model="account_number"
                               placeholder="e.g., 123456789 (UMEME), 987654321 (NWSC)"
                               class="w-full px-4 py-3 border-2 border-blue-300 dark:border-blue-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all"
                               required>
                        @error('account_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <!-- Business Details Section -->
            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Business Details</h3>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Business Type -->
                    <div>
                        <label for="business_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Business Type
                        </label>
                        <select id="business_type"
                                wire:model="business_type"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all">
                            <option value="">Select Business Type</option>
                            <option value="individual">👤 Individual</option>
                            <option value="company">🏢 Company</option>
                            <option value="government">🏛️ Government Entity</option>
                        </select>
                        @error('business_type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- TIN -->
                    <div>
                        <label for="tin" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            TIN (Tax Identification Number)
                        </label>
                        <input type="text" 
                               id="tin"
                               wire:model="tin"
                               placeholder="e.g., 1000123456"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">🇺🇬 URA Tax Identification Number</p>
                        @error('tin')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Contact Information</h3>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input type="email" 
                                   id="email"
                                   wire:model="email"
                                   placeholder="vendor@example.com"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all">
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Phone Number
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <input type="text" 
                                   id="phone"
                                   wire:model="phone"
                                   placeholder="+256 700 000000"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all">
                        </div>
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div class="lg:col-span-2">
                        <label for="address" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Physical Address
                        </label>
                        <textarea id="address"
                                  wire:model="address"
                                  rows="3"
                                  placeholder="Enter physical address or location"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white resize-none transition-all"></textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Payment Information Section -->
            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Payment Information</h3>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Payment Method -->
                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Preferred Payment Method
                        </label>
                        <select id="payment_method"
                                wire:model.live="payment_method"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all">
                            <option value="">Select Payment Method</option>
                            <option value="bank">🏦 Bank Transfer</option>
                            <option value="mobile_money">📱 Mobile Money</option>
                            <option value="ussd">📱 USSD (USSD Payments)</option>
                            <option value="cash">💵 Cash</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Currency
                        </label>
                        <select id="currency"
                                wire:model="currency"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all">
                            <option value="UGX">🇺🇬 UGX (Ugandan Shilling)</option>
                            <option value="USD">🇺🇸 USD (US Dollar)</option>
                            <option value="EUR">🇪🇺 EUR (Euro)</option>
                            <option value="GBP">🇬🇧 GBP (British Pound)</option>
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Bank Details (Only if Bank Transfer selected) -->
                @if($payment_method === 'bank')
                    <div class="mt-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-start gap-3 mb-6">
                            <div class="p-3 bg-green-500 rounded-xl shadow-md">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-green-900 dark:text-green-100">Bank Account Details</h4>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">Enter the vendor's bank account information for transfers</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="bank_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Bank Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="bank_name"
                                       wire:model="bank_name"
                                       placeholder="e.g., Stanbic Bank, Centenary Bank, DFCU Bank"
                                       class="w-full px-4 py-3 border-2 border-green-300 dark:border-green-700 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all">
                                @error('bank_name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="bank_account_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Account Number <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="bank_account_number"
                                           wire:model="bank_account_number"
                                           placeholder="e.g., 9030012345678"
                                           class="w-full px-4 py-3 border-2 border-green-300 dark:border-green-700 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white font-mono transition-all">
                                    @error('bank_account_number')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="bank_account_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Account Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="bank_account_name"
                                           wire:model="bank_account_name"
                                           placeholder="e.g., Company Name Ltd"
                                           class="w-full px-4 py-3 border-2 border-green-300 dark:border-green-700 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all">
                                    @error('bank_account_name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Mobile Money Details (Only if Mobile Money selected) -->
                @if($payment_method === 'mobile_money')
                    <div class="mt-6 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-start gap-3 mb-6">
                            <div class="p-3 bg-yellow-500 rounded-xl shadow-md">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-yellow-900 dark:text-yellow-100">Mobile Money Details</h4>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">Enter the vendor's mobile money account for payments</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="mobile_money_provider" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Provider <span class="text-red-500">*</span>
                                </label>
                                <select id="mobile_money_provider"
                                        wire:model="mobile_money_provider"
                                        class="w-full px-4 py-3 border-2 border-yellow-300 dark:border-yellow-700 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white transition-all">
                                    <option value="">Select Provider</option>
                                    <option value="mtn">📱 MTN Mobile Money</option>
                                    <option value="airtel">📱 Airtel Money</option>
                                </select>
                                @error('mobile_money_provider')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="mobile_money_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Mobile Money Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="mobile_money_number"
                                       wire:model="mobile_money_number"
                                       placeholder="+256 700 000000"
                                       class="w-full px-4 py-3 border-2 border-yellow-300 dark:border-yellow-700 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white font-mono transition-all">
                                @error('mobile_money_number')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <!-- USSD Details (Only if USSD selected) -->
                @if($payment_method === 'ussd')
                    <div class="mt-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6 shadow-lg">
                        <div class="flex items-start gap-3 mb-6">
                            <div class="p-3 bg-blue-500 rounded-xl shadow-md">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 10-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-blue-900 dark:text-blue-100">USSD Payment Details</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Enter USSD code or account number for mobile-based social security payments</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="ussd_provider" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Provider <span class="text-red-500">*</span>
                                </label>
                                <select id="ussd_provider"
                                        wire:model="ussd_provider"
                                        class="w-full px-4 py-3 border-2 border-blue-300 dark:border-blue-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                                    <option value="">Select Provider</option>
                                    <option value="mtn">📱 MTN (e.g., *206*6#)</option>
                                    <option value="airtel">📱 Airtel (e.g., *185*7#)</option>
                                </select>
                                @error('ussd_provider')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="ussd_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    USSD Code/Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="ussd_number"
                                       wire:model="ussd_number"
                                       placeholder="e.g., *206*6# or account number"
                                       class="w-full px-4 py-3 border-2 border-blue-300 dark:border-blue-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white font-mono transition-all">
                                @error('ussd_number')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between gap-4 pt-8 border-t-2 border-gray-200 dark:border-gray-700">
                <a href="{{ route('vendors.index') }}"
                   class="px-8 py-3.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition-all duration-200 font-semibold flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Cancel
                </a>
                <button type="submit"
                        class="px-8 py-3.5 bg-gradient-to-r from-purple-500 via-indigo-600 to-blue-600 hover:from-purple-600 hover:via-indigo-700 hover:to-blue-700 text-white rounded-xl transition-all duration-200 font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Create Vendor
                </button>
            </div>
        </form>
    </div>
</div>
