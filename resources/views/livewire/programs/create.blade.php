<?php

use App\Models\Program;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $programId = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public string $start_date = '';
    public string $end_date = '';
    public ?int $manager_id = null;
    public string $status = 'planned';
    public string $budget = '';

    public function mount(): void
    {
        $this->programId = request('id');
        
        if ($this->programId) {
            $program = Program::findOrFail($this->programId);
            $this->name = $program->name;
            $this->code = $program->code;
            $this->description = $program->description ?? '';
            $this->start_date = $program->start_date?->format('Y-m-d') ?? '';
            $this->end_date = $program->end_date?->format('Y-m-d') ?? '';
            $this->manager_id = $program->manager_id;
            $this->status = $program->status;
            $this->budget = $program->budget ?? '';
        }
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:programs,code' . ($this->programId ? ',' . $this->programId : ''),
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'required|in:planned,active,completed',
            'budget' => 'nullable|numeric|min:0',
        ];

        $validated = $this->validate($rules);

        if ($this->programId) {
            $program = Program::findOrFail($this->programId);
            $program->update($validated);
            $message = 'Program updated successfully.';
        } else {
            Program::create($validated);
            $message = 'Program created successfully.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);

        $this->redirect(route('programs.index'));
    }

    public function with(): array
    {
        return [
            'managers' => User::whereIn('role', ['admin', 'accountant'])->get(),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $programId ? 'Edit Program' : 'Create New Program' }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $programId ? 'Update program details' : 'Add a new program or project to track' }}</p>
    </div>

    <form wire:submit="save">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program Name *</label>
                    <input type="text" wire:model="name" placeholder="e.g., Code Camp 2025" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('name') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program Code *</label>
                    <input type="text" wire:model="code" placeholder="e.g., CC2025" {{ $programId ? 'disabled' : '' }} class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent {{ $programId ? 'opacity-60 cursor-not-allowed' : '' }}">
                    @if($programId)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Program code cannot be changed after creation</p>
                    @endif
                    @error('code') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea wire:model="description" rows="3" placeholder="Program description..." class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                    @error('description') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date *</label>
                    <input type="date" wire:model="start_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('start_date') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date *</label>
                    <input type="date" wire:model="end_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('end_date') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Manager</label>
                    <select wire:model="manager_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Select Manager</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                        @endforeach
                    </select>
                    @error('manager_id') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status *</label>
                    <select wire:model="status" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="planned">Planned</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                    @error('status') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Budget (UGX)</label>
                    <input type="number" step="0.01" wire:model="budget" placeholder="0.00" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('budget') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('programs.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                    {{ $programId ? 'Update Program' : 'Create Program' }}
                </button>
            </div>
        </div>
    </form>
</div>
