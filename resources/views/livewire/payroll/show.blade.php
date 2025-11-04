<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\PayrollRun;
use App\Models\PayrollItem;
use Illuminate\Support\Facades\DB;

state(['payrollRunId']);

mount(function ($id) {
    $this->payrollRunId = $id;
});

$payrollRun = computed(function () {
    return PayrollRun::with(['items.staff', 'approvedBy'])->findOrFail($this->payrollRunId);
});

$approvePayrollRun = function () {
    $payrollRun = $this->payrollRun;
    
    if ($payrollRun->status !== 'draft') {
        session()->flash('error', 'Only draft payroll runs can be approved.');
        return;
    }
    
    $payrollRun->approve(auth()->user());
    
    session()->flash('success', 'Payroll run approved successfully.');
};

$processPayrollRun = function () {
    $payrollRun = $this->payrollRun;
    
    if ($payrollRun->status !== 'approved') {
        session()->flash('error', 'Only approved payroll runs can be processed.');
        return;
    }
    
    $payrollRun->update(['status' => 'processed']);
    
    session()->flash('success', 'Payroll run processed successfully.');
};

$markAsPaid = function () {
    $payrollRun = $this->payrollRun;
    
    if (!in_array($payrollRun->status, ['approved', 'processed'])) {
        session()->flash('error', 'Only approved or processed payroll runs can be marked as paid.');
        return;
    }
    
    DB::transaction(function () use ($payrollRun) {
        $payrollRun->update(['status' => 'paid']);
        
        foreach ($payrollRun->items as $item) {
            $item->markAsPaid('BULK-' . now()->format('YmdHis'));
        }
    });
    
    session()->flash('success', 'Payroll run marked as paid successfully.');
};

$cancelPayrollRun = function () {
    $payrollRun = $this->payrollRun;
    
    if ($payrollRun->status === 'paid') {
        session()->flash('error', 'Cannot cancel a paid payroll run.');
        return;
    }
    
    $payrollRun->update(['status' => 'cancelled']);
    
    session()->flash('success', 'Payroll run cancelled.');
};

?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Payroll Run: {{ $this->payrollRun->run_number }}</flux:heading>
            <div class="mt-1 text-sm text-zinc-500">
                Period: {{ $this->payrollRun->period_start->format('M d') }} - {{ $this->payrollRun->period_end->format('M d, Y') }}
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('payroll.index')" wire:navigate icon="arrow-left">
                Back to List
            </flux:button>
            
            @if($this->payrollRun->status === 'draft')
                <flux:button wire:click="approvePayrollRun" wire:confirm="Are you sure you want to approve this payroll run?" variant="primary">
                    Approve
                </flux:button>
            @endif
            
            @if($this->payrollRun->status === 'approved')
                <flux:button wire:click="processPayrollRun" variant="primary">
                    Process
                </flux:button>
            @endif
            
            @if(in_array($this->payrollRun->status, ['approved', 'processed']))
                <flux:button wire:click="markAsPaid" wire:confirm="Confirm all payments have been made?" variant="primary">
                    Mark as Paid
                </flux:button>
            @endif
            
            @if($this->payrollRun->status !== 'paid')
                <flux:button wire:click="cancelPayrollRun" wire:confirm="Are you sure you want to cancel this payroll run?" variant="danger">
                    Cancel Run
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Status and Summary -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Status</div>
            @php
                $statusColors = [
                    'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                    'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'processed' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                    'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                ];
            @endphp
            <flux:badge class="mt-1 {{ $statusColors[$this->payrollRun->status] ?? '' }}">
                {{ ucfirst($this->payrollRun->status) }}
            </flux:badge>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Gross Amount</div>
            <div class="mt-1 text-xl font-bold">UGX {{ number_format($this->payrollRun->total_gross) }}</div>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">PAYE</div>
            <div class="mt-1 text-xl font-bold text-red-600">UGX {{ number_format($this->payrollRun->total_paye) }}</div>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">NSSF</div>
            <div class="mt-1 text-xl font-bold text-red-600">UGX {{ number_format($this->payrollRun->total_nssf) }}</div>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Net Amount</div>
            <div class="mt-1 text-xl font-bold text-green-600">UGX {{ number_format($this->payrollRun->total_net) }}</div>
        </div>
    </div>

    <!-- Approval Info -->
    @if($this->payrollRun->approvedBy)
        <div class="mb-6 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <flux:icon.check-circle class="h-5 w-5 text-green-600" />
                <div class="text-sm">
                    Approved by <span class="font-semibold">{{ $this->payrollRun->approvedBy->name }}</span>
                    on {{ $this->payrollRun->approved_at->format('M d, Y \a\t h:i A') }}
                </div>
            </div>
        </div>
    @endif

    <!-- Payroll Items -->
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
        <flux:heading size="lg" class="mb-4">Payroll Items ({{ $this->payrollRun->items->count() }} staff)</flux:heading>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left text-sm font-semibold">Staff</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Employment Type</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Hours/Classes</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Gross</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">PAYE</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">NSSF (Emp)</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">NSSF (Emr)</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Bonuses</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Deductions</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Net</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->payrollRun->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $item->staff->full_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $item->staff->employee_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ ucfirst(str_replace('_', ' ', $item->staff->employment_type)) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if($item->hours_worked > 0)
                                    {{ $item->hours_worked }}h
                                @endif
                                @if($item->classes_taught > 0)
                                    {{ $item->classes_taught }}c
                                @endif
                                @if($item->hours_worked == 0 && $item->classes_taught == 0)
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium">
                                {{ number_format($item->gross_amount) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-red-600">
                                {{ number_format($item->paye_amount) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-red-600">
                                {{ number_format($item->nssf_employee) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-orange-600">
                                {{ number_format($item->nssf_employer) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-green-600">
                                {{ number_format($item->bonuses) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-red-600">
                                {{ number_format($item->other_deductions) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-semibold">
                                {{ number_format($item->net_amount) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm">{{ ucfirst($item->staff->payment_method) }}</div>
                                @if($item->staff->payment_method === 'mobile_money')
                                    <div class="text-xs text-zinc-500">
                                        {{ $item->staff->mobile_money_provider }}: {{ $item->staff->mobile_money_number }}
                                    </div>
                                @elseif($item->staff->payment_method === 'bank')
                                    <div class="text-xs text-zinc-500">
                                        {{ $item->staff->bank_name }} ({{ $item->staff->bank_account }})
                                    </div>
                                @endif
                                
                                @if($item->payment_status === 'paid')
                                    <flux:badge size="sm" class="mt-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        Paid
                                    </flux:badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if($this->payrollRun->notes)
        <div class="mt-6 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <flux:heading size="lg" class="mb-2">Notes</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $this->payrollRun->notes }}</p>
        </div>
    @endif
</div>
