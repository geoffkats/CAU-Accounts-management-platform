<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // IMPORTANT: Migrate existing payment data BEFORE dropping columns
        $this->migratePaymentData();
        
        Schema::table('expenses', function (Blueprint $table) {
            // Remove payment-related fields (now handled by payments table)
            if (Schema::hasColumn('expenses', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('expenses', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
            if (Schema::hasColumn('expenses', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
            if (Schema::hasColumn('expenses', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('expenses', 'payment_account_id')) {
                $table->dropForeign(['payment_account_id']);
                $table->dropColumn('payment_account_id');
            }
            if (Schema::hasColumn('expenses', 'reference_number')) {
                $table->dropColumn('reference_number');
            }
        });
    }
    
    /**
     * Migrate existing payment data to payments table
     */
    private function migratePaymentData(): void
    {
        echo "Migrating existing payment data...\n";
        
        // Get expenses with payment data
        $expenses = DB::table('expenses')
            ->whereNotNull('payment_status')
            ->where('payment_status', '!=', 'unpaid')
            ->get();
        
        if ($expenses->isEmpty()) {
            echo "No payment data to migrate.\n";
            return;
        }
        
        echo "Found {$expenses->count()} expenses with payment data.\n";
        
        $migratedCount = 0;
        
        foreach ($expenses as $expense) {
            // Check if payment already exists
            $existingPayment = DB::table('payments')
                ->where('expense_id', $expense->id)
                ->exists();
            
            if ($existingPayment) {
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
                continue;
            }
            
            // Get payment account or default
            $paymentAccountId = $expense->payment_account_id ?? DB::table('accounts')
                ->where('type', 'asset')
                ->where('code', 'like', '10%')
                ->value('id');
            
            // Get next voucher number
            $lastVoucher = DB::table('payments')
                ->orderByDesc('id')
                ->value('voucher_number');
            
            $nextNumber = 1;
            if ($lastVoucher && preg_match('/PV-(\d+)/', $lastVoucher, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            }
            $voucherNumber = 'PV-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Create payment record (only use columns that exist in payments table)
            DB::table('payments')->insert([
                'expense_id' => $expense->id,
                'payment_account_id' => $paymentAccountId,
                'voucher_number' => $voucherNumber,
                'payment_date' => $expense->payment_date ?? $expense->expense_date,
                'amount' => $paymentAmount,
                'payment_method' => $expense->payment_method ?? 'cash',
                'payment_reference' => $expense->payment_reference ?? $expense->reference_number,
                'notes' => "Migrated payment for: " . substr($expense->description ?? '', 0, 200),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $migratedCount++;
        }
        
        echo "Successfully migrated {$migratedCount} payments.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Restore payment fields if needed
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->date('payment_date')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_date');
            $table->string('payment_method')->nullable()->after('payment_reference');
            $table->foreignId('payment_account_id')->nullable()->after('account_id')->constrained('accounts')->nullOnDelete();
            $table->string('reference_number')->nullable()->after('payment_method');
        });
    }
};
