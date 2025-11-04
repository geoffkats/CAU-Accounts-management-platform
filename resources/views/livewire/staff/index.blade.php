<?php

use App\Models\Staff;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $employmentFilter = 'all';
    public string $statusFilter = 'active';

    public function with(): array
    {
        $query = Staff::query()
            ->when($this->search, function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->employmentFilter !== 'all', function ($q) {
                $q->where('employment_type', $this->employmentFilter);
            })
            ->when($this->statusFilter === 'active', function ($q) {
                $q->where('is_active', true);
            })
            ->when($this->statusFilter === 'inactive', function ($q) {
                $q->where('is_active', false);
            })
            ->latest();

        return [
            'staff' => $query->paginate(15),
            'totalStaff' => Staff::where('is_active', true)->count(),
            'totalInstructors' => Staff::whereIn('employment_type', ['part_time', 'contract', 'consultant'])->where('is_active', true)->count(),
            'totalFullTime' => Staff::where('employment_type', 'full_time')->where('is_active', true)->count(),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEmploymentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteStaff(int $id): void
    {
        $staff = Staff::findOrFail($id);
        $staff->is_active = false;
        $staff->termination_date = now();
        $staff->save();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Staff member deactivated successfully.'
        ]);
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 bg-clip-text text-transparent">
                Staff & Instructors
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage employees and instructor assignments</p>
        </div>
        <a href="{{ route('staff.create') }}" 
           class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Staff Member
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-6 rounded-xl border-2 border-purple-200 dark:border-purple-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Staff</div>
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $totalStaff }}</div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Instructors</div>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $totalInstructors }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Full-Time</div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $totalFullTime }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Name, email, or employee #..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Employment Type</label>
                <select wire:model.live="employmentFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Types</option>
                    <option value="full_time">Full-Time</option>
                    <option value="part_time">Part-Time</option>
                    <option value="contract">Contract</option>
                    <option value="consultant">Consultant</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Employee #</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Name</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Employment Type</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Payment Method</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Rate/Salary</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($staff as $member)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $member->employee_number }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $member->full_name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $member->email }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                {{ $member->employment_type === 'full_time' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $member->employment_type === 'contract' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $member->employment_type === 'part_time' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $member->employment_type === 'consultant' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $member->employment_type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($member->payment_method === 'mobile_money')
                                <div>{{ $member->mobile_money_provider }}</div>
                                <div class="text-xs">{{ $member->mobile_money_number }}</div>
                            @elseif($member->payment_method === 'bank')
                                <div>{{ $member->bank_name }}</div>
                                <div class="text-xs">{{ $member->bank_account }}</div>
                            @else
                                Cash
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                            @if($member->base_salary)
                                UGX {{ number_format($member->base_salary, 0) }}/mo
                            @elseif($member->hourly_rate)
                                UGX {{ number_format($member->hourly_rate, 0) }}/hr
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                {{ $member->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('staff.assignments', $member->id) }}" 
                                   class="text-purple-600 hover:text-purple-900 dark:text-purple-400"
                                   title="Assignments">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </a>
                                <a href="{{ route('staff.edit', $member->id) }}" 
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                   title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                @if($member->is_active)
                                    <button wire:click="deleteStaff({{ $member->id }})"
                                            wire:confirm="Are you sure you want to deactivate this staff member?"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400"
                                            title="Deactivate">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium mb-4">No staff members found</p>
                            <a href="{{ route('staff.create') }}" 
                               class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:shadow-lg transition-all duration-200 text-sm font-medium">
                                Add First Staff Member
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($staff->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $staff->links() }}
        </div>
        @endif
    </div>
</div>
