<?php

use App\Models\Vendor;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        $query = Vendor::withCount('expenses')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->latest();

        return [
            'vendors' => $query->paginate(15),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteVendor(int $id): void
    {
        $vendor = Vendor::findOrFail($id);
        
        if ($vendor->expenses()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete vendor with expenses.'
            ]);
            return;
        }

        $vendor->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Vendor deleted successfully.'
        ]);
    }
}; ?>

<div class="space-y-6 px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 bg-clip-text text-transparent">
                Vendors Directory
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2 text-lg">Manage suppliers, contractors, utilities & service providers</p>
        </div>
        <a href="{{ route('vendors.create') }}" 
           class="px-8 py-3.5 bg-gradient-to-r from-purple-500 via-indigo-600 to-blue-600 hover:from-purple-600 hover:via-indigo-700 hover:to-blue-700 text-white rounded-xl transition-all duration-200 font-semibold inline-flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Vendor
        </a>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" 
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search vendors by name, email, phone, or TIN..."
                   class="w-full pl-12 pr-4 py-4 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white text-lg transition-all">
        </div>
    </div>

    <!-- Vendors Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($vendors as $vendor)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <!-- Card Header with Gradient -->
            <div class="bg-gradient-to-r from-purple-500 via-indigo-600 to-blue-600 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                            {{ substr($vendor->name, 0, 1) }}
                        </div>
                        <div>
                            @if($vendor->vendor_type)
                            <div class="inline-flex items-center gap-1 px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-xs font-semibold text-white mb-2">
                                @if($vendor->vendor_type === 'utility')
                                    🔌 Utility
                                @elseif($vendor->vendor_type === 'supplier')
                                    📦 Supplier
                                @elseif($vendor->vendor_type === 'contractor')
                                    🔧 Contractor
                                @elseif($vendor->vendor_type === 'government')
                                    🏛️ Government
                                @endif
                            </div>
                            @endif
                            <div class="text-xs text-purple-100">Total Expenses</div>
                            <div class="text-2xl font-bold text-white">{{ $vendor->expenses_count }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card Body -->
            <div class="p-6 space-y-4">
                <!-- Vendor Name -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">{{ $vendor->name }}</h3>
                    @if($vendor->service_type)
                    <div class="flex items-center gap-2 text-sm text-purple-600 dark:text-purple-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span class="font-medium">{{ $vendor->service_type }}</span>
                    </div>
                    @endif
                </div>

                <!-- Business Details Section -->
                @if($vendor->tin || $vendor->business_type)
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3 space-y-2">
                    @if($vendor->tin)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">TIN:</span>
                        <span class="text-gray-900 dark:text-white font-semibold font-mono">{{ $vendor->tin }}</span>
                    </div>
                    @endif
                    @if($vendor->business_type)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Type:</span>
                        <span class="text-gray-900 dark:text-white capitalize">
                            @if($vendor->business_type === 'individual') 👤 Individual
                            @elseif($vendor->business_type === 'company') 🏢 Company
                            @elseif($vendor->business_type === 'government') 🏛️ Government
                            @endif
                        </span>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Utility Account (if applicable) -->
                @if($vendor->vendor_type === 'utility' && $vendor->account_number)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Account:</span>
                        <span class="text-gray-900 dark:text-white font-semibold font-mono">{{ $vendor->account_number }}</span>
                    </div>
                </div>
                @endif

                <!-- Contact Information -->
                <div class="space-y-2">
                    @if($vendor->email)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="truncate">{{ $vendor->email }}</span>
                    </div>
                    @endif

                    @if($vendor->phone)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>{{ $vendor->phone }}</span>
                    </div>
                    @endif

                    @if($vendor->address)
                    <div class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="line-clamp-2">{{ $vendor->address }}</span>
                    </div>
                    @endif
                </div>

                <!-- Payment Information -->
                @if($vendor->payment_method)
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Payment:</span>
                        <span class="text-gray-900 dark:text-white font-semibold">
                            @if($vendor->payment_method === 'bank')
                                🏦 Bank Transfer
                            @elseif($vendor->payment_method === 'mobile_money')
                                📱 Mobile Money
                            @elseif($vendor->payment_method === 'ussd')
                                📱 USSD
                            @elseif($vendor->payment_method === 'cash')
                                💵 Cash
                            @endif
                        </span>
                    </div>
                    
                    @if($vendor->payment_method === 'bank' && $vendor->bank_name)
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ $vendor->bank_name }}</span>
                        @if($vendor->bank_account_number)
                            <span class="font-mono ml-1">• {{ substr($vendor->bank_account_number, -4) }}</span>
                        @endif
                    </div>
                    @endif

                    @if($vendor->payment_method === 'mobile_money' && $vendor->mobile_money_provider)
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium capitalize">{{ $vendor->mobile_money_provider }}</span>
                        @if($vendor->mobile_money_number)
                            <span class="font-mono ml-1">• {{ $vendor->mobile_money_number }}</span>
                        @endif
                    </div>
                    @endif

                    @if($vendor->payment_method === 'ussd' && $vendor->ussd_provider)
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium capitalize">{{ $vendor->ussd_provider }}</span>
                        @if($vendor->ussd_number)
                            <span class="font-mono ml-1">• {{ $vendor->ussd_number }}</span>
                        @endif
                    </div>
                    @endif
                </div>
                @endif

                <!-- Currency Badge -->
                @if($vendor->currency && $vendor->currency !== 'UGX')
                <div class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-semibold">
                    💱 {{ $vendor->currency }}
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex gap-2 pt-4 border-t-2 border-gray-200 dark:border-gray-700">
                    <a href="{{ route('vendors.edit', $vendor) }}"
                       class="flex-1 px-4 py-2.5 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 text-sm font-semibold text-center flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </a>
                    <button wire:click="deleteVendor({{ $vendor->id }})"
                            wire:confirm="Are you sure you want to delete this vendor?"
                            class="flex-1 px-4 py-2.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200 text-sm font-semibold flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl shadow-xl border-2 border-dashed border-purple-300 dark:border-purple-700 p-16 text-center">
                <div class="bg-white dark:bg-gray-800 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Vendors Found</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6 text-lg">Start building your vendor directory by adding your first vendor</p>
                <a href="{{ route('vendors.create') }}" 
                   class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-purple-500 via-indigo-600 to-blue-600 hover:from-purple-600 hover:via-indigo-700 hover:to-blue-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create First Vendor
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($vendors->hasPages())
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        {{ $vendors->links() }}
    </div>
    @endif
</div>