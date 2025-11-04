<?php

use App\Models\Customer;
use Livewire\Volt\Component;

new class extends Component {
    public Customer $customer;
    public string $name = '';
    public string $student_name = '';
    public string $date_of_birth = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
        $this->name = $customer->name;
        $this->student_name = $customer->student_name ?? '';
        $this->date_of_birth = $customer->date_of_birth ? $customer->date_of_birth->format('Y-m-d') : '';
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone ?? '';
        $this->address = $customer->address ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $this->customer->update($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Customer updated successfully.'
        ]);

        $this->redirect(route('customers.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('customers.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Edit Customer
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Update customer information</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden max-w-2xl">
        <div class="bg-gradient-to-r from-cyan-500 to-blue-600 p-6">
            <h2 class="text-xl font-bold text-white">Customer Information</h2>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Customer Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name"
                       wire:model="name"
                       placeholder="e.g., Makerere University"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 dark:bg-gray-700 dark:text-white"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Student Name -->
            <div>
                <label for="student_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Student Name
                </label>
                <input type="text" 
                       id="student_name"
                       wire:model="student_name"
                       placeholder="e.g., John Doe"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 dark:bg-gray-700 dark:text-white">
                @error('student_name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date of Birth -->
            <div>
                <label for="date_of_birth" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Date of Birth
                </label>
                <input type="date" 
                       id="date_of_birth"
                       wire:model="date_of_birth"
                       max="{{ date('Y-m-d') }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 dark:bg-gray-700 dark:text-white">
                @error('date_of_birth')
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
                       placeholder="customer@example.com"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 dark:bg-gray-700 dark:text-white">
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
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 dark:bg-gray-700 dark:text-white">
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
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                @error('address')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Update Customer
                </button>
                <a href="{{ route('customers.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
