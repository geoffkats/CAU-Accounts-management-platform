<?php

use App\Models\BudgetReallocation;
use App\Models\Currency;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $reallocation = BudgetReallocation::findOrFail($id);
        
        if ($reallocation->status !== 'pending') {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Only pending reallocations can be approved.'
            ]);
            return;
        }

        $reallocation->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Reallocation approved successfully.'
        ]);
    }

    public function reject(int $id, string $notes): void
    {
        $reallocation = BudgetReallocation::findOrFail($id);
        
        if ($reallocation->status !== 'pending') {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Only pending reallocations can be rejected.'
            ]);
            return;
        }

        $reallocation->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Reallocation rejected.'
        ]);
    }

    public function with(): array
    {
        $query = BudgetReallocation::query()
            ->with(['fromBudget.program', 'toBudget.program', 'requestedBy', 'reviewedBy']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('fromBudget.program', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('toBudget.program', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $reallocations = $query->orderBy('created_at', 'desc')->paginate(15);

        $pendingCount = BudgetReallocation::where('status', 'pending')->count();

        return [
            'reallocations' => $reallocations,
            'baseCurrency' => Currency::getBaseCurrency(),
            'pendingCount' => $pendingCount,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Budget Reallocations</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Manage budget transfers between programs</p>
        </div>
        
        <div class="flex items-center gap-3">
            @if($pendingCount > 0)
                <span class="px-3 py-1.5 rounded-full text-sm font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">
                    {{ $pendingCount }} Pending
                </span>
            @endif
            <a href="{{ route('budgets.reallocations.create') }}" 
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Reallocation
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Search Programs
                </label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Type to search..."
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Status
                </label>
                <select wire:model.live="status"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Reallocations List -->
    @if($reallocations->count() > 0)
        <div class="space-y-4">
            @foreach($reallocations as $reallocation)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-3 py-1 rounded text-sm font-semibold bg-{{ $reallocation->status_color }}-100 dark:bg-{{ $reallocation->status_color }}-900/30 text-{{ $reallocation->status_color }}-700 dark:text-{{ $reallocation->status_color }}-400">
                                    {{ ucfirst($reallocation->status) }}
                                </span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $reallocation->created_at->format('M d, Y') }}
                                </span>
                            </div>
                            
                            <!-- Reallocation Flow -->
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex-1 text-right">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">From</div>
                                    <div class="text-lg font-semibold text-zinc-900 dark:text-white">
                                        {{ $reallocation->fromBudget->program->name }}
                                    </div>
                                    <div class="text-sm text-zinc-500">
                                        {{ $reallocation->fromBudget->period_type }} • {{ $reallocation->fromBudget->start_date->format('M Y') }}
                                    </div>
                                </div>
                                
                                <div class="flex flex-col items-center">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                        {{ $baseCurrency->symbol }} {{ number_format($reallocation->amount, 0) }}
                                    </div>
                                </div>
                                
                                <div class="flex-1">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">To</div>
                                    <div class="text-lg font-semibold text-zinc-900 dark:text-white">
                                        {{ $reallocation->toBudget->program->name }}
                                    </div>
                                    <div class="text-sm text-zinc-500">
                                        {{ $reallocation->toBudget->period_type }} • {{ $reallocation->toBudget->start_date->format('M Y') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Category and Reason -->
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-3 mb-3">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Category:</span>
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                                        {{ ucfirst($reallocation->category) }}
                                    </span>
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <strong>Reason:</strong> {{ $reallocation->reason }}
                                </div>
                            </div>

                            <!-- Metadata -->
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                Requested by <strong>{{ $reallocation->requestedBy->name }}</strong>
                                @if($reallocation->reviewedBy)
                                    • Reviewed by <strong>{{ $reallocation->reviewedBy->name }}</strong> on {{ $reallocation->reviewed_at->format('M d, Y') }}
                                @endif
                            </div>

                            @if($reallocation->review_notes)
                                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded">
                                    <strong>Review Notes:</strong> {{ $reallocation->review_notes }}
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        @if($reallocation->status === 'pending' && in_array(auth()->user()->role, ['admin', 'accountant']))
                            <div class="flex flex-col gap-2 ml-4">
                                <button wire:click="approve({{ $reallocation->id }})"
                                        wire:confirm="Approve this reallocation?"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm">
                                    Approve
                                </button>
                                <button onclick="rejectReallocation({{ $reallocation->id }})"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm">
                                    Reject
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $reallocations->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="w-16 h-16 text-zinc-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">No Reallocations Yet</h3>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                Start by creating a budget reallocation request to transfer funds between programs.
            </p>
            <a href="{{ route('budgets.reallocations.create') }}" 
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Reallocation
            </a>
        </div>
    @endif
</div>

<script>
function rejectReallocation(id) {
    const notes = prompt('Please provide a reason for rejection:');
    if (notes) {
        @this.call('reject', id, notes);
    }
}
</script>
