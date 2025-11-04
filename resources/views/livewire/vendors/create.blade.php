<?php

use App\Models\Vendor;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $service_type = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        Vendor::create($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Vendor created successfully.'
        ]);

        $this->redirect(route('vendors.index'));
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('vendors.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Create Vendor
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Add a new supplier or service provider</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden max-w-2xl">
        <div class="bg-purple-600 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-white">Vendor Information</h2>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
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
                    Email Address
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
                    Phone Number
                </label>
                <input type="text" 
                       id="phone"
                       wire:model="phone"
                       placeholder="+256 700 000000"
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
                          placeholder="Physical address or location"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                @error('address')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Create Vendor
                </button>
                <a href="{{ route('vendors.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
