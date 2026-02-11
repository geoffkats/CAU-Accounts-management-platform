<?php

use App\Models\Staff;
use App\Models\InstructorAssignment;
use App\Models\Program;
use Livewire\Volt\Component;
use function Laravel\Folio\name;

new class extends Component {
    public Staff $staff;
    public $assignments = [];
    public $showModal = false;
    
    // Form fields
    public $editingId = null;
    public $program_id = '';
    public $start_date = '';
    public $end_date = '';
    public $rate_per_hour = '';
    public $rate_per_class = '';
    public $fixed_amount = '';
    public $is_active = true;
    public $notes = '';

    public function mount($id)
    {
        $this->staff = Staff::findOrFail($id);
        $this->loadAssignments();
    }

    public function loadAssignments()
    {
        $this->assignments = InstructorAssignment::with('program')
            ->where('staff_id', $this->staff->id)
            ->orderByDesc('is_active')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $assignment = InstructorAssignment::findOrFail($id);
        
        $this->editingId = $assignment->id;
        $this->program_id = $assignment->program_id;
        $this->start_date = $assignment->start_date?->format('Y-m-d') ?? '';
        $this->end_date = $assignment->end_date?->format('Y-m-d') ?? '';
        $this->rate_per_hour = $assignment->rate_per_hour ?? '';
        $this->rate_per_class = $assignment->rate_per_class ?? '';
        $this->fixed_amount = $assignment->fixed_amount ?? '';
        $this->is_active = $assignment->is_active;
        $this->notes = $assignment->notes ?? '';
        
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'program_id' => 'required|exists:programs,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'rate_per_hour' => 'nullable|numeric|min:0',
            'rate_per_class' => 'nullable|numeric|min:0',
            'fixed_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['staff_id'] = $this->staff->id;

        if ($this->editingId) {
            $assignment = InstructorAssignment::findOrFail($this->editingId);
            $assignment->update($validated);
            session()->flash('message', 'Assignment updated successfully.');
        } else {
            InstructorAssignment::create($validated);
            session()->flash('message', 'Assignment created successfully.');
        }

        $this->loadAssignments();
        $this->closeModal();
    }

    public function toggleStatus($id)
    {
        $assignment = InstructorAssignment::findOrFail($id);
        $assignment->update(['is_active' => !$assignment->is_active]);
        $this->loadAssignments();
        session()->flash('message', 'Assignment status updated.');
    }

    public function delete($id)
    {
        InstructorAssignment::findOrFail($id)->delete();
        $this->loadAssignments();
        session()->flash('message', 'Assignment deleted successfully.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->program_id = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->rate_per_hour = '';
        $this->rate_per_class = '';
        $this->fixed_amount = '';
        $this->is_active = true;
        $this->notes = '';
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'programs' => Program::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                            Instructor Assignments
                        </h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $this->staff->full_name }} - {{ $this->staff->employee_number }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('staff.index') }}" 
                           class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                            Back to Staff
                        </a>
                        <button wire:click="openCreateModal"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                            Add Assignment
                        </button>
                    </div>
                </div>

                @if (session()->has('message'))
                    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900/30 dark:border-green-700 dark:text-green-400">
                        {{ session('message') }}
                    </div>
                @endif

                <!-- Assignments Table -->
                <div class="bg-white dark:bg-zinc-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Program</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-zinc-200 dark:bg-zinc-800 dark:divide-zinc-700">
                        @forelse($assignments as $assignment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $assignment->program->name }}
                                    </div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $assignment->program->code }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $assignment->start_date->format('M d, Y') }}
                                    @if($assignment->end_date)
                                        - {{ $assignment->end_date->format('M d, Y') }}
                                    @else
                                        - <span class="text-zinc-500 dark:text-zinc-400">Ongoing</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    @if($assignment->fixed_amount)
                                        UGX {{ number_format($assignment->fixed_amount, 0) }} (Fixed)
                                    @elseif($assignment->rate_per_hour)
                                        UGX {{ number_format($assignment->rate_per_hour, 0) }}/hr
                                    @elseif($assignment->rate_per_class)
                                        UGX {{ number_format($assignment->rate_per_class, 0) }}/class
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">Not set</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $assignment->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                        {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="openEditModal({{ $assignment->id }})"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                                title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="toggleStatus({{ $assignment->id }})"
                                                class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400"
                                                title="Toggle Status">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </button>
                                        <button wire:click="delete({{ $assignment->id }})"
                                                wire:confirm="Are you sure you want to delete this assignment?"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400"
                                                title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No assignments found. Click "Add Assignment" to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal -->
            @if($showModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = 'auto'">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-zinc-500 bg-opacity-75 dark:bg-zinc-900 dark:bg-opacity-75" wire:click="closeModal"></div>

                <!-- Modal panel -->
                <div class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 shadow-xl rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $editingId ? 'Edit Assignment' : 'Add Assignment' }}
                        </h3>
                        <button wire:click="closeModal" class="text-zinc-400 hover:text-zinc-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <!-- Program -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Program <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="program_id" 
                                    class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-100">
                                <option value="">Select a program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }} ({{ $program->code }})</option>
                                @endforeach
                            </select>
                            @error('program_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <!-- Date Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Start Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="start_date" 
                                       class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-100">
                                @error('start_date') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    End Date
                                </label>
                                <input type="date" wire:model="end_date" 
                                       class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-100">
                                @error('end_date') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Rates -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Rate per Hour (UGX)
                                </label>
                                <input type="number" step="0.01" wire:model="rate_per_hour" 
                                       class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-100">
                                @error('rate_per_hour') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Rate per Class (UGX)
                                </label>
                                <input type="number" step="0.01" wire:model="rate_per_class" 
                                       class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-100">
                                @error('rate_per_class') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Fixed Amount (UGX)
                                </label>
                                <input type="number" step="0.01" wire:model="fixed_amount" 
                                       class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-100">
                                @error('fixed_amount') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="is_active" 
                                       class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600">
                                <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Active Assignment</span>
                            </label>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Notes
                            </label>
                            <textarea wire:model="notes" rows="3" 
                                      class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-100"></textarea>
                            @error('notes') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-600">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                {{ $editingId ? 'Update' : 'Create' }} Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            </div>
        </div>
            @endif
    </div>
</div>