<?php

use App\Models\Customer;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        $query = Customer::withCount('sales')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->latest();

        return [
            'customers' => $query->paginate(15),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteCustomer(int $id): void
    {
        $customer = Customer::findOrFail($id);
        
        if ($customer->sales()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete customer with sales.'
            ]);
            return;
        }

        $customer->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Customer deleted successfully.'
        ]);
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Customers
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your clients and customers</p>
        </div>
        <a href="{{ route('customers.create') }}" 
           class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Customer
        </a>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <input type="text" 
               wire:model.live.debounce.300ms="search"
               placeholder="Search customers by name, email, or phone..."
               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 dark:bg-gray-700 dark:text-white">
    </div>

    <!-- Customers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($customers as $customer)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-shadow duration-200">
            <div class="bg-gradient-to-r from-cyan-500 to-blue-600 p-6">
                <div class="flex items-center justify-between">
                    <div class="h-12 w-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-xl">
                        {{ substr($customer->name, 0, 1) }}
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-cyan-100">Total Sales</div>
                        <div class="text-lg font-bold text-white">{{ $customer->sales_count }}</div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $customer->name }}</h3>
                
                @if($customer->student_name)
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ $customer->student_name }}</span>
                </div>
                @endif
                
                @if($customer->date_of_birth)
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Age: {{ $customer->age }} years</span>
                </div>
                @endif
                
                @if($customer->email)
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="truncate">{{ $customer->email }}</span>
                </div>
                @endif

                @if($customer->phone)
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{{ $customer->phone }}</span>
                </div>
                @endif

                @if($customer->address)
                <div class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <svg class="w-4 h-4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ $customer->address }}</span>
                </div>
                @endif

                <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('customers.edit', $customer) }}"
                       class="flex-1 px-4 py-2 text-cyan-600 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded-lg transition-colors duration-200 text-sm font-medium text-center">
                        Edit
                    </a>
                    <button wire:click="deleteCustomer({{ $customer->id }})"
                            wire:confirm="Are you sure you want to delete this customer?"
                            class="flex-1 px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200 text-sm font-medium">
                        Delete
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 font-medium mb-4">No customers found</p>
                <a href="{{ route('customers.create') }}" 
                   class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg hover:shadow-lg transition-all duration-200 text-sm font-medium">
                    Create First Customer
                </a>
            </div>
        </div>
        @endforelse
    </div>

    @if($customers->hasPages())
    <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        {{ $customers->links() }}
    </div>
    @endif
</div>
