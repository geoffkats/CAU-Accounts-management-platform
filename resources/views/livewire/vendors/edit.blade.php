<?php

use App\Models\Vendor;
use Livewire\Volt\Component;

new class extends Component {
    public Vendor $vendor;
    
    public string $name = '';
    public string $service_type = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';

    public function mount(Vendor $vendor): void
    {
        $this->vendor = $vendor;
        $this->name = $vendor->name;
        $this->service_type = $vendor->service_type ?? '';
        $this->email = $vendor->email ?? '';
        $this->phone = $vendor->phone ?? '';
        $this->address = $vendor->address ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $this->vendor->update($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Vendor updated successfully!'
        ]);

        $this->redirect(route('vendors.index'));
    }
}; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('vendors.index') }}" 
               class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Edit Vendor
            </h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400 ml-10">Update vendor information</p>
    </div>

    <!-- Form -->
    <form wire:submit="save" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
        <div class="grid grid-cols-1 gap-6">
            
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Vendor Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name"
                       wire:model="name"
                       placeholder="e.g., Office Supplies Ltd"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Service Type / Goods -->
            <div>
                <label for="service_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Service Type / Goods
                </label>
                <input type="text" 
                       id="service_type"
                       wire:model="service_type"
                       placeholder="e.g., Office Supplies, Catering Services, IT Equipment"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Specify what type of service or goods this vendor provides</p>
                @error('service_type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Email
                </label>
                <input type="email" 
                       id="email"
                       wire:model="email"
                       placeholder="vendor@example.com"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Phone
                </label>
                <input type="tel" 
                       id="phone"
                       wire:model="phone"
                       placeholder="+256 XXX XXXXXX"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Address -->
            <div>
                <label for="address" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Address
                </label>
                <textarea id="address"
                          wire:model="address"
                          rows="3"
                          placeholder="Street address, City, Country"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"></textarea>
                @error('address')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <!-- Actions -->
        <div class="flex gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <button type="submit"
                    class="px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors font-semibold">
                Update Vendor
            </button>
            <a href="{{ route('vendors.index') }}"
               class="px-8 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors font-semibold">
                Cancel
            </a>
        </div>
    </form>
</div>
