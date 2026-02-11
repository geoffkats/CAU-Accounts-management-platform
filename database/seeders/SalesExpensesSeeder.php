<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Account;
use App\Models\Sale;
use App\Models\Expense;
use Illuminate\Support\Str;

class SalesExpensesSeeder extends Seeder
{
    public function run(): void
    {
        $baseCurrency = 'UGX';
        $usd = 'USD';

        $programs = Program::take(2)->get();
        if ($programs->count() < 1) {
            return; // nothing to seed
        }

        $customers = Customer::all();
        $vendors = Vendor::all();
        $incomeAccount = Account::where('type', 'income')->orderBy('code')->first();
        $expenseAccounts = Account::where('type', 'expense')->get();

        // Guard: ensure core records exist
        if (!$incomeAccount || $vendors->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $dates = [
            now()->subDays(20),
            now()->subDays(10),
            now()->subDays(5),
            now()->subDays(2),
            now(),
        ];

        foreach ($programs as $idx => $program) {
            // Seed Sales (some in UGX, some in USD)
            foreach ($dates as $i => $date) {
                $isUsd = $i % 2 === 1; // alternate currencies
                $currency = $isUsd ? $usd : $baseCurrency;
                $amount = $isUsd ? 1500 + $i * 250 : 1_000_000 + $i * 250_000; // USD/UGX amounts

                Sale::create([
                    'program_id' => $program->id,
                    'customer_id' => $customers->random()->id,
                    'account_id' => $incomeAccount->id,
                    'invoice_number' => 'INV-' . $program->id . '-' . $date->format('Ymd') . '-' . Str::padLeft((string)($i + 1), 3, '0'),
                    'sale_date' => $date->toDateString(),
                    'amount' => $amount,
                    'currency' => $currency,
                    'amount_paid' => $i % 3 === 0 ? $amount : ($amount * 0.5),
                    'status' => $i % 3 === 0 ? Sale::STATUS_PAID : Sale::STATUS_PARTIALLY_PAID,
                    'description' => 'Seed sale for program ' . $program->code,
                ]);
            }

            // Seed Expenses across categories
            foreach ($dates as $i => $date) {
                $account = $expenseAccounts->random();
                $isUsd = $i % 3 === 0; // some USD expenses
                $currency = $isUsd ? $usd : $baseCurrency;
                $amount = $isUsd ? 800 + $i * 120 : 600_000 + $i * 180_000;

                Expense::create([
                    'program_id' => $program->id,
                    'vendor_id' => $vendors->random()->id,
                    'account_id' => $account->id,
                    'expense_date' => $date->toDateString(),
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => 'Seed expense: ' . $account->name,
                    'category' => $account->name,
                ]);
            }
        }
    }
}
