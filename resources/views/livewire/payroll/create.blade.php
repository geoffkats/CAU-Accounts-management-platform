<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Models\Program;
use App\Models\InstructorAssignment;
use App\Models\PayrollItem;
use Illuminate\Support\Facades\DB;

state([
    'period_start' => '',
    'period_end' => '',
    'payment_date' => '',
    'notes' => '',
    'selectedStaff' => [],
    'staffHours' => [],
    'staffClasses' => [],
    'staffBonuses' => [],
    'staffDeductions' => [],
]);

mount(function () {
    // Default to current month
    $this->period_start = now()->startOfMonth()->toDateString();
    $this->period_end = now()->endOfMonth()->toDateString();
    $this->payment_date = now()->addDays(5)->toDateString();
});

$activeStaff = computed(function () {
    return Staff::active()->orderBy('last_name')->get();
});

$toggleStaff = function ($staffId) {
    if (in_array($staffId, $this->selectedStaff)) {
        $this->selectedStaff = array_values(array_diff($this->selectedStaff, [$staffId]));
        unset($this->staffHours[$staffId]);
        unset($this->staffClasses[$staffId]);
        unset($this->staffBonuses[$staffId]);
        unset($this->staffDeductions[$staffId]);
    } else {
        $this->selectedStaff[] = $staffId;
        $this->staffHours[$staffId] = 0;
        $this->staffClasses[$staffId] = 0;
        $this->staffBonuses[$staffId] = 0;
        $this->staffDeductions[$staffId] = 0;
    }
};

$calculatePayroll = computed(function () {
    $calculations = [];
    $totals = [
        'gross' => 0,
        'paye' => 0,
        'nssf' => 0,
        'deductions' => 0,
        'bonuses' => 0,
        'net' => 0,
    ];

    foreach ($this->selectedStaff as $staffId) {
        $staff = Staff::find($staffId);
        if (!$staff) continue;

        $hours = floatval($this->staffHours[$staffId] ?? 0);
        $classes = intval($this->staffClasses[$staffId] ?? 0);
        $bonus = floatval($this->staffBonuses[$staffId] ?? 0);
        $otherDeductions = floatval($this->staffDeductions[$staffId] ?? 0);

        // Calculate gross based on employment type
        $gross = 0;
        if ($staff->employment_type === 'full_time') {
            $gross = $staff->base_salary ?? 0;
        } elseif (in_array($staff->employment_type, ['part_time', 'contract', 'consultant'])) {
            $gross = ($staff->hourly_rate ?? 0) * $hours;
        }

        // Calculate taxes
        $paye = PayrollItem::calculatePAYE($gross);
        $nssf = PayrollItem::calculateNSSF($gross);
        
        $net = $gross - $paye - $nssf['employee'] - $otherDeductions + $bonus;

        $calculations[$staffId] = [
            'staff' => $staff,
            'hours' => $hours,
            'classes' => $classes,
            'gross' => $gross,
            'paye' => $paye,
            'nssf_employee' => $nssf['employee'],
            'nssf_employer' => $nssf['employer'],
            'bonus' => $bonus,
            'other_deductions' => $otherDeductions,
            'net' => $net,
        ];

        $totals['gross'] += $gross;
        $totals['paye'] += $paye;
        $totals['nssf'] += $nssf['employee'] + $nssf['employer'];
        $totals['deductions'] += $otherDeductions;
        $totals['bonuses'] += $bonus;
        $totals['net'] += $net;
    }

    return [
        'items' => $calculations,
        'totals' => $totals,
    ];
});

$createPayrollRun = function () {
    $this->validate([
        'period_start' => 'required|date',
        'period_end' => 'required|date|after_or_equal:period_start',
        'payment_date' => 'required|date',
        'selectedStaff' => 'required|array|min:1',
    ], [
        'selectedStaff.required' => 'Please select at least one staff member.',
        'selectedStaff.min' => 'Please select at least one staff member.',
    ]);

    DB::transaction(function () {
        // Generate run number
        $lastRun = PayrollRun::latest('id')->first();
        $runNumber = 'PR-' . date('Ym') . '-' . str_pad(($lastRun ? $lastRun->id + 1 : 1), 4, '0', STR_PAD_LEFT);

        // Create payroll run
        $payrollRun = PayrollRun::create([
            'run_number' => $runNumber,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'payment_date' => $this->payment_date,
            'status' => 'draft',
            'notes' => $this->notes,
            'total_gross' => 0,
            'total_paye' => 0,
            'total_nssf' => 0,
            'total_net' => 0,
        ]);

        // Create payroll items
        $calculations = $this->calculatePayroll;
        foreach ($calculations['items'] as $staffId => $calc) {
            PayrollItem::create([
                'payroll_run_id' => $payrollRun->id,
                'staff_id' => $staffId,
                'hours_worked' => $calc['hours'],
                'classes_taught' => $calc['classes'],
                'gross_amount' => $calc['gross'],
                'paye_amount' => $calc['paye'],
                'nssf_employee' => $calc['nssf_employee'],
                'nssf_employer' => $calc['nssf_employer'],
                'other_deductions' => $calc['other_deductions'],
                'bonuses' => $calc['bonus'],
                'net_amount' => $calc['net'],
                'payment_status' => 'pending',
            ]);
        }

        // Recalculate totals
        $payrollRun->recalculateTotals();

        session()->flash('success', 'Payroll run created successfully.');
        $this->redirect(route('payroll.show', $payrollRun->id), navigate: true);
    });
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('payroll.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 bg-clip-text text-transparent">
                Create Payroll Run
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Process payroll for selected staff members</p>
        </div>
    </div>

    <form wire:submit="createPayrollRun" class="space-y-6">
        <!-- Period Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6">
                <h2 class="text-xl font-bold text-white">Payroll Period</h2>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Period Start <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               wire:model.live="period_start" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('period_start')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Period End <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               wire:model.live="period_end" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('period_end')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Payment Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               wire:model="payment_date" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        @error('payment_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Notes (Optional)
                    </label>
                    <textarea wire:model="notes" 
                              rows="2"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <!-- Staff Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6">
                <h2 class="text-xl font-bold text-white">Select Staff</h2>
            </div>
            
            <div class="p-6">
                @error('selectedStaff')
                    <p class="mb-4 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            
                <div class="space-y-2">
                    @foreach($this->activeStaff as $staff)
                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <input type="checkbox" 
                                   wire:click="toggleStaff({{ $staff->id }})"
                                   @checked(in_array($staff->id, $selectedStaff))
                                   class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $staff->full_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ ucfirst(str_replace('_', ' ', $staff->employment_type)) }} •
                                    @if($staff->employment_type === 'full_time')
                                        UGX {{ number_format($staff->base_salary) }}/month
                                    @else
                                        UGX {{ number_format($staff->hourly_rate) }}/hour
                                    @endif
                                </div>
                            </div>

                            @if(in_array($staff->id, $selectedStaff))
                                <div class="grid gap-2 md:grid-cols-4">
                                    @if(in_array($staff->employment_type, ['part_time', 'contract', 'consultant']))
                                        <input type="number" 
                                               step="0.5"
                                               wire:model.live="staffHours.{{ $staff->id }}"
                                               placeholder="Hours"
                                               class="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                    @endif
                                    
                                    <input type="number"
                                           wire:model.live="staffClasses.{{ $staff->id }}"
                                           placeholder="Classes"
                                           class="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                    
                                    <input type="number"
                                           step="0.01"
                                           wire:model.live="staffBonuses.{{ $staff->id }}"
                                           placeholder="Bonus"
                                           class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                    
                                    <input type="number"
                                           step="0.01"
                                           wire:model.live="staffDeductions.{{ $staff->id }}"
                                           placeholder="Deductions"
                                           class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Payroll Summary -->
        @if(count($selectedStaff) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6">
                    <h2 class="text-xl font-bold text-white">Payroll Summary</h2>
                </div>
                
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Staff</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold">Gross</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold">PAYE</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">NSSF</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Bonuses</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Deductions</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->calculatePayroll['items'] as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $item['staff']->full_name }}</td>
                                    <td class="px-4 py-3 text-right text-sm">{{ number_format($item['gross']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($item['paye']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($item['nssf_employee']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-green-600">{{ number_format($item['bonus']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($item['other_deductions']) }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold">{{ number_format($item['net']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-semibold">
                                <td class="px-4 py-3 text-sm">TOTAL</td>
                                <td class="px-4 py-3 text-right text-sm">{{ number_format($this->calculatePayroll['totals']['gross']) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($this->calculatePayroll['totals']['paye']) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($this->calculatePayroll['totals']['nssf']) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-green-600">{{ number_format($this->calculatePayroll['totals']['bonuses']) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($this->calculatePayroll['totals']['deductions']) }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ number_format($this->calculatePayroll['totals']['net']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('payroll.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                Create Payroll Run
            </button>
        </div>
    </form>
</div>
