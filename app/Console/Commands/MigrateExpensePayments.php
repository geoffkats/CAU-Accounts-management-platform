<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class MigrateExpensePayments extends Command
{
    protected $signature = 'expenses:migrate-payments';
    protected $description = 'Migrate existing expense payment data to the new payments table';

    public function handle()
    {
        $this->info('Starting migration of expense payments...');
        
        // Get all expenses that have payment data in the old fields
        $expenses = Expense::whereNotNull('payment_status')
            ->where('payment_status', '!=', 'unpaid')
            ->get();
        
        if ($expenses->isEmpty()) {
            $this->info('No expenses with payment data found.');
            return 0;
        }
        
        $this->info("Found {$expenses->count()} expenses with payment data.");
        
        $migratedCount = 0;
        $skippedCount = 0;
        
        foreach ($expenses as $expense) {
            // Check if payment already exists for this expense
            $existingPayment = Payment::where('expense_id', $expense->id)->first();
            
            if ($existingPayment) {
                $this->line("Skipping expense #{$expense->id} - payment already exists");
                $skippedCount++;
                continue;
            }
            
            // Determine payment amount
            $paymentAmount = 0;
            if ($expense->payment_status === 'paid') {
                $paymentAmount = $expense->amount;
            } elseif ($expense->payment_status === 'partial' && isset($expense->amount_paid)) {
                $paymentAmount = $expense->amount_paid;
            }
            
            if ($paymentAmount <= 0) {
                $this->line("Skipping expense #{$expense->id} - no payment amount");
                $skippedCount++;
                continue;
            }
            
            // Get exchange rate
            $baseCurrency = Currency::getBaseCurrency();
            $rate = 1.0;
            if ($expense->currency && $expense->currency !== $baseCurrency->code) {
                $rate = $expense->exchange_rate ?? 1.0;
            }
            
            // Determine payment account (use the one from expense or default)
            $paymentAccountId = $expense->payment_account_id ?? null;
            if (!$paymentAccountId) {
                // Default to first cash/bank account
                $paymentAccountId = DB::table('accounts')
                    ->where('type', 'asset')
                    ->where('code', 'like', '10%')
                    ->first()?->id;
            }
            
            // Create payment record
            try {
                $payment = Payment::create([
                    'expense_id' => $expense->id,
                    'payment_account_id' => $paymentAccountId,
                    'payment_date' => $expense->payment_date ?? $expense->expense_date,
                    'amount' => $paymentAmount,
                    'currency' => $expense->currency ?? 'UGX',
                    'exchange_rate' => $rate,
                    'payment_method' => $this->guessPaymentMethod($expense),
                    'reference_number' => $expense->payment_reference ?? null,
                    'description' => "Migrated payment for expense: {$expense->description}",
                    'status' => 'approved',
                ]);
                
                $this->info("✓ Migrated payment for expense #{$expense->id} - Amount: {$paymentAmount}");
                $migratedCount++;
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to migrate expense #{$expense->id}: " . $e->getMessage());
            }
        }
        
        $this->info("\n=== Migration Summary ===");
        $this->info("Total expenses processed: {$expenses->count()}");
        $this->info("Successfully migrated: {$migratedCount}");
        $this->info("Skipped: {$skippedCount}");
        
        return 0;
    }
    
    private function guessPaymentMethod($expense): string
    {
        // Try to guess payment method from reference or default to cash
        if ($expense->payment_reference) {
            $ref = strtolower($expense->payment_reference);
            if (str_contains($ref, 'check') || str_contains($ref, 'cheque')) {
                return 'check';
            }
            if (str_contains($ref, 'bank') || str_contains($ref, 'transfer')) {
                return 'bank_transfer';
            }
            if (str_contains($ref, 'mobile') || str_contains($ref, 'momo')) {
                return 'mobile_money';
            }
        }
        
        return 'cash'; // Default
    }
}
