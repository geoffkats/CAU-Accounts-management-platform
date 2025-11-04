<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramBudget;
use App\Models\User;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $approver = User::first();
        $programs = Program::take(2)->get();
        if ($programs->isEmpty()) return;

        foreach ($programs as $program) {
            // Current quarter budget covering the seeded transaction dates
            $start = now()->startOfMonth()->subMonth();
            $end = now()->endOfMonth()->addMonth();

            ProgramBudget::create([
                'program_id' => $program->id,
                'period_type' => 'quarterly',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'income_budget' => 30_000_000, // 30M UGX
                'expense_budget' => 18_000_000, // 18M UGX
                'currency' => 'UGX',
                'status' => 'active',
                'approved_by' => $approver?->id,
                'approved_at' => now(),
                'notes' => 'Seed budget for demo transactions',
            ]);
        }
    }
}
