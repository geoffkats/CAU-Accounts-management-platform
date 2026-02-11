<?php

use App\Models\Staff;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $id = null;
    public string $employee_number = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $employment_type = 'contract';
    public string $payment_method = 'mobile_money';
    public string $mobile_money_provider = 'MTN';
    public string $mobile_money_number = '';
    public string $bank_name = '';
    public string $bank_account = '';
    public string $nssf_number = '';
    public string $tin_number = '';
    public ?float $base_salary = null;
    public ?float $hourly_rate = null;
    public string $hire_date = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->employee_number = 'EMP-' . now()->format('Ymd') . '-' . rand(100, 999);
        $this->hire_date = now()->format('Y-m-d');

        if (request()->has('id')) {
            $staff = Staff::findOrFail(request()->id);
            $this->id = $staff->id;
            $this->fill($staff->toArray());
            $this->hire_date = $staff->hire_date?->format('Y-m-d') ?? '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'employee_number' => ['required', 'string', 'max:50', $this->id ? 'unique:staff,employee_number,' . $this->id : 'unique:staff,employee_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', $this->id ? 'unique:staff,email,' . $this->id : 'unique:staff,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,consultant'],
            'payment_method' => ['required', 'in:bank,mobile_money,cash'],
            'mobile_money_provider' => ['nullable', 'string', 'max:50'],
            'mobile_money_number' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'nssf_number' => ['nullable', 'string', 'max:50'],
            'tin_number' => ['nullable', 'string', 'max:50'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($this->id) {
            $staff = Staff::findOrFail($this->id);
            $staff->update($validated);
            $message = 'Staff member updated successfully.';
        } else {
            Staff::create($validated);
            $message = 'Staff member created successfully.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);

        $this->redirect(route('staff.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('staff.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 bg-clip-text text-transparent">
                {{ $id ? 'Edit Staff Member' : 'Add Staff Member' }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $id ? 'Update employee information' : 'Create new employee or instructor' }}</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-6">
            <h2 class="text-xl font-bold text-white">Employee Information</h2>
        </div>

        <form wire:submit="save" class="p-6 space-y-8">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="employee_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Employee Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="employee_number"
                               wire:model="employee_number"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white font-mono"
                               required>
                        @error('employee_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="employment_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Employment Type <span class="text-red-500">*</span>
                        </label>
                        <select id="employment_type"
                                wire:model="employment_type"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                                required>
                            <option value="contract">Contract</option>
                            <option value="consultant">Consultant</option>
                            <option value="part_time">Part-Time</option>
                            <option value="full_time">Full-Time</option>
                        </select>
                        @error('employment_type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="first_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="first_name"
                               wire:model="first_name"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                               required>
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="last_name"
                               wire:model="last_name"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                               required>
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email"
                               wire:model="email"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Phone
                        </label>
                        <input type="text" 
                               id="phone"
                               wire:model="phone"
                               placeholder="+256..."
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="hire_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Hire Date
                        </label>
                        <input type="date" 
                               id="hire_date"
                               wire:model="hire_date"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('hire_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select id="payment_method"
                                wire:model.live="payment_method"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                                required>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cash">Cash</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($payment_method === 'mobile_money')
                        <div>
                            <label for="mobile_money_provider" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Provider
                            </label>
                            <select id="mobile_money_provider"
                                    wire:model="mobile_money_provider"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                <option value="MTN">MTN Mobile Money</option>
                                <option value="Airtel">Airtel Money</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="mobile_money_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Mobile Money Number
                            </label>
                            <input type="text" 
                                   id="mobile_money_number"
                                   wire:model="mobile_money_number"
                                   placeholder="256..."
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                            @error('mobile_money_number')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    @if($payment_method === 'bank')
                        <div>
                            <label for="bank_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Bank Name
                            </label>
                            <input type="text" 
                                   id="bank_name"
                                   wire:model="bank_name"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                            @error('bank_name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="bank_account" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Account Number
                            </label>
                            <input type="text" 
                                   id="bank_account"
                                   wire:model="bank_account"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                            @error('bank_account')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>

            <!-- Compensation & Tax -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Compensation & Tax Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="base_salary" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Base Salary (Monthly - for full-time)
                        </label>
                        <input type="number" 
                               id="base_salary"
                               wire:model="base_salary"
                               step="0.01"
                               min="0"
                               placeholder="UGX 0.00"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('base_salary')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="hourly_rate" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Hourly Rate (for contract/instructors)
                        </label>
                        <input type="number" 
                               id="hourly_rate"
                               wire:model="hourly_rate"
                               step="0.01"
                               min="0"
                               placeholder="UGX 0.00"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('hourly_rate')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tin_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            TIN (Tax Identification Number)
                        </label>
                        <input type="text" 
                               id="tin_number"
                               wire:model="tin_number"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('tin_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nssf_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            NSSF Number
                        </label>
                        <input type="text" 
                               id="nssf_number"
                               wire:model="nssf_number"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('nssf_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Notes
                </label>
                <textarea id="notes"
                          wire:model="notes"
                          rows="4"
                          placeholder="Additional notes..."
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $id ? 'Update Staff Member' : 'Create Staff Member' }}
                </button>
                <a href="{{ route('staff.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
