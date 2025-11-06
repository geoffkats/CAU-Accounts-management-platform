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
        // Drop FK on income_id only if it exists in the database
        $fkName = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'journal_entries')
            ->where('COLUMN_NAME', 'income_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($fkName) {
            DB::statement('ALTER TABLE `journal_entries` DROP FOREIGN KEY `'.$fkName.'`');
        }

        Schema::table('journal_entries', function (Blueprint $table) {
            // Add proper foreign keys for sales and customer payments
            if (!Schema::hasColumn('journal_entries', 'sales_id')) {
                $table->foreignId('sales_id')->nullable()->after('income_id')->constrained('sales')->nullOnDelete();
            }
            if (!Schema::hasColumn('journal_entries', 'customer_payment_id')) {
                $table->foreignId('customer_payment_id')->nullable()->after('sales_id')->constrained('customer_payments')->nullOnDelete();
            }

            // Keep income_id column present without FK
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // Drop the new foreign keys
            try { $table->dropForeign(['sales_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['customer_payment_id']); } catch (\Throwable $e) {}
            if (Schema::hasColumn('journal_entries', 'sales_id')) {
                $table->dropColumn('sales_id');
            }
            if (Schema::hasColumn('journal_entries', 'customer_payment_id')) {
                $table->dropColumn('customer_payment_id');
            }
            
            // Restore the original (incorrect) constraint
            // Note: In fresh installs there is no 'incomes' table; skip restoring to avoid errors
        });
    }
};
