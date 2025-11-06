<?php

use App\Models\User;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

new class extends Component {
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'accountant';

    public function mount(): void
    {
        if (request()->has('id')) {
            $user = User::findOrFail(request()->id);
            $this->id = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role ?? 'accountant';
        }
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', $this->id ? 'unique:users,email,' . $this->id : 'unique:users,email'],
            'role' => ['required', 'in:admin,accountant,audit'],
        ];

        // Password is required for new users, optional for updates
        if (!$this->id) {
            $rules['password'] = ['required', 'confirmed', Password::min(8)];
        } elseif ($this->password) {
            $rules['password'] = ['confirmed', Password::min(8)];
        }

        $validated = $this->validate($rules);

        if ($this->id) {
            $user = User::findOrFail($this->id);
            
            // Only update password if provided
            if ($this->password) {
                $validated['password'] = Hash::make($this->password);
            } else {
                unset($validated['password']);
            }
            
            $user->update($validated);
            $message = 'User updated successfully.';
        } else {
            $validated['password'] = Hash::make($this->password);
            User::create($validated);
            $message = 'User created successfully.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);

        $this->redirect(route('users.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('users.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 bg-clip-text text-transparent">
                {{ $id ? 'Edit User' : 'Create New User' }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $id ? 'Update user account and permissions' : 'Add a new user to the system' }}</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-6">
            <h2 class="text-xl font-bold text-white">User Account Details</h2>
        </div>

        <form wire:submit="save" class="p-6 space-y-8">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name"
                               wire:model="name"
                               placeholder="e.g., Jane Accountant"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email"
                               wire:model="email"
                               placeholder="jane@codeacademy.ug"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Role Selection -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Role & Permissions</h3>
                <div>
                    <label for="role" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        User Role <span class="text-red-500">*</span>
                    </label>
                    <select id="role"
                            wire:model="role"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="admin">Administrator - Full access to all modules</option>
                        <option value="accountant">Accountant - Financial management access</option>
                        <option value="audit">Auditor - Read-only access</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    
                    <!-- Role Permission Info -->
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="text-sm text-blue-900 dark:text-blue-300 space-y-2">
                            @if ($role === 'admin')
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold">Administrator Access</p>
                                        <ul class="list-disc list-inside mt-1 space-y-1 text-xs">
                                            <li>Full access to all financial modules</li>
                                            <li>User management and role assignment</li>
                                            <li>Company settings and configuration</li>
                                            <li>Staff management and payroll</li>
                                            <li>Budget reallocations and approvals</li>
                                            <li>Audit trail access and verification</li>
                                        </ul>
                                    </div>
                                </div>
                            @elseif ($role === 'accountant')
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold">Accountant Access</p>
                                        <ul class="list-disc list-inside mt-1 space-y-1 text-xs">
                                            <li>Chart of Accounts management</li>
                                            <li>Sales, expenses, and journal entries</li>
                                            <li>Vendor invoices and payments</li>
                                            <li>Student invoices and payments</li>
                                            <li>Asset management and depreciation</li>
                                            <li>Budget viewing and reports</li>
                                            <li>Cannot manage users or staff</li>
                                        </ul>
                                    </div>
                                </div>
                            @elseif ($role === 'audit')
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <div>
                                        <p class="font-semibold">Auditor Access (Read-Only)</p>
                                        <ul class="list-disc list-inside mt-1 space-y-1 text-xs">
                                            <li>View all financial records and transactions</li>
                                            <li>Generate and export all reports</li>
                                            <li>View audit logs and activity history</li>
                                            <li>Cannot create or modify any records</li>
                                            <li>Cannot change settings or manage users</li>
                                            <li>Ideal for board members and external auditors</li>
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Section -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $id ? 'Change Password (Optional)' : 'Set Password' }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Password @if(!$id)<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="password" 
                               id="password"
                               wire:model="password"
                               placeholder="{{ $id ? 'Leave blank to keep current password' : 'Minimum 8 characters' }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                               @if(!$id) required @endif>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Confirm Password @if(!$id)<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="password" 
                               id="password_confirmation"
                               wire:model="password_confirmation"
                               placeholder="{{ $id ? 'Leave blank to keep current password' : 'Confirm password' }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                               @if(!$id) required @endif>
                        @error('password_confirmation')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                @if($id)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 inline text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Only fill in the password fields if you want to change the user's password.
                    </p>
                @endif
            </div>

            <!-- Security Note -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-sm text-yellow-800 dark:text-yellow-300">
                        <p class="font-semibold mb-1">Security Recommendations:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Use a strong password with at least 8 characters</li>
                            <li>Encourage users to enable Two-Factor Authentication (2FA) from their profile</li>
                            <li>Passwords are encrypted using bcrypt hashing</li>
                            <li>Users can reset their password via email if forgotten</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $id ? 'Update User' : 'Create User' }}
                </button>
                <a href="{{ route('users.index') }}"
                   class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300">User Account Management</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-400 space-y-1">
                    <p>After creating a user, they can log in with their email and password. They will be prompted to set up 2FA for enhanced security.</p>
                    <p>All user actions are logged in the Activity Log with cryptographic hash chain verification for complete audit compliance.</p>
                    <p>Users can update their profile, password, and enable 2FA from Settings → Profile.</p>
                </div>
            </div>
        </div>
    </div>
</div>
