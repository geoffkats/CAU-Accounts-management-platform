<?php

use function Livewire\Volt\{state, computed};
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

state([
    'search' => '',
    'status' => 'all',
    'startDate' => '',
    'endDate' => '',
]);

$payrollRuns = computed(function () {
    return PayrollRun::with(['approvedBy'])
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('run_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('items.staff', function ($sq) {
                      $sq->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%');
                  });
            });
        })
        ->when($this->status !== 'all', fn($query) => $query->where('status', $this->status))
        ->when($this->startDate, fn($query) => $query->whereDate('period_start', '>=', $this->startDate))
        ->when($this->endDate, fn($query) => $query->whereDate('period_end', '<=', $this->endDate))
        ->latest('period_end')
        ->paginate(15);
});

$stats = computed(function () {
    $currentMonth = now()->startOfMonth();
    $currentMonthEnd = now()->endOfMonth();
    
    return [
        'total_runs' => PayrollRun::count(),
        'pending_approval' => PayrollRun::where('status', 'draft')->count(),
        'current_month_amount' => PayrollRun::whereBetween('period_start', [$currentMonth, $currentMonthEnd])
            ->where('status', '!=', 'cancelled')
            ->sum('total_net'),
        'last_run' => PayrollRun::latest('period_end')->first(),
    ];
});

$deletePayrollRun = function ($id) {
    $payrollRun = PayrollRun::findOrFail($id);
    
    if (!in_array($payrollRun->status, ['draft', 'cancelled'])) {
        session()->flash('error', 'Cannot delete payroll run that has been approved or processed.');
        return;
    }
    
    DB::transaction(function () use ($payrollRun) {
        $payrollRun->items()->delete();
        $payrollRun->delete();
    });
    
    session()->flash('success', 'Payroll run deleted successfully.');
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 bg-clip-text text-transparent">
                Payroll Runs
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage staff payroll and payments</p>
        </div>
        <a href="{{ route('payroll.create') }}" 
           class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Payroll Run
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Runs</div>
                    <div class="text-2xl font-bold">{{ number_format($this->stats['total_runs']) }}</div>
                </div>
                <div class="text-gray-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pending Approval</div>
                    <div class="text-2xl font-bold text-amber-600">{{ number_format($this->stats['pending_approval']) }}</div>
                </div>
                <div class="text-amber-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">This Month</div>
                    <div class="text-2xl font-bold">UGX {{ number_format($this->stats['current_month_amount']) }}</div>
                </div>
                <div class="text-green-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Last Run</div>
                    <div class="text-sm font-semibold">
                        @if($this->stats['last_run'])
                            {{ $this->stats['last_run']->period_end->format('M d, Y') }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="text-blue-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6">
            <h2 class="text-xl font-bold text-white">All Payroll Runs</h2>
        </div>

        <div class="p-6 space-y-6">
            <!-- Filters -->
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <input type="text"
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search run number or staff..."
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div>
                    <select wire:model.live="status"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="approved">Approved</option>
                        <option value="processed">Processed</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div>
                    <input type="date" 
                           wire:model.live="startDate" 
                           placeholder="Start Date"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <input type="date" 
                           wire:model.live="endDate" 
                           placeholder="End Date"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

        <!-- Payroll Runs Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">Run Number</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Period</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Payment Date</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Gross Amount</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Deductions</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Net Amount</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Approved By</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->payrollRuns as $run)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-4 py-3">
                                <a href="{{ route('payroll.show', $run->id) }}" 
                                   class="font-mono text-sm text-blue-600 hover:underline dark:text-blue-400"
                                   wire:navigate>
                                    {{ $run->run_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div>{{ $run->period_start->format('M d') }} - {{ $run->period_end->format('M d, Y') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $run->payment_date ? $run->payment_date->format('M d, Y') : 'Not set' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium">
                                UGX {{ number_format($run->total_gross, 0) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-red-600">
                                UGX {{ number_format($run->total_paye + $run->total_nssf, 0) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold">
                                UGX {{ number_format($run->total_net, 0) }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                        'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'processed' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$run->status] ?? '' }}">
                                    {{ ucfirst($run->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($run->approvedBy)
                                    <div>{{ $run->approvedBy->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $run->approved_at->format('M d, Y') }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('payroll.show', $run->id) }}"
                                       wire:navigate
                                       class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                       title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    
                                    @if($run->status === 'draft')
                                        <button wire:click="deletePayrollRun({{ $run->id }})"
                                                wire:confirm="Are you sure you want to delete this payroll run?"
                                                class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No payroll runs found. Create your first payroll run to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $this->payrollRuns->links() }}
        </div>
        </div>
    </div>
</div>
