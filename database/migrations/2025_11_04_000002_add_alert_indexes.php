<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expenses: index program and date for budget window scans
        Schema::table('expenses', function (Blueprint $table) {
            if (!self::indexExists('expenses', ['program_id', 'expense_date'])) {
                $table->index(['program_id', 'expense_date']);
            }
        });

        // Sales: index program and sale date for income aggregates
        Schema::table('sales', function (Blueprint $table) {
            if (!self::indexExists('sales', ['program_id', 'sale_date'])) {
                $table->index(['program_id', 'sale_date']);
            }
        });

        // Program budgets: filter by status and join on program/date window
        Schema::table('program_budgets', function (Blueprint $table) {
            if (!self::indexExists('program_budgets', ['status'])) {
                $table->index(['status']);
            }
            if (!self::indexExists('program_budgets', ['program_id', 'start_date', 'end_date'])) {
                $table->index(['program_id', 'start_date', 'end_date']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (self::indexExists('expenses', ['program_id', 'expense_date'])) {
                $table->dropIndex(['program_id', 'expense_date']);
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (self::indexExists('sales', ['program_id', 'sale_date'])) {
                $table->dropIndex(['program_id', 'sale_date']);
            }
        });

        Schema::table('program_budgets', function (Blueprint $table) {
            if (self::indexExists('program_budgets', ['status'])) {
                $table->dropIndex(['status']);
            }
            if (self::indexExists('program_budgets', ['program_id', 'start_date', 'end_date'])) {
                $table->dropIndex(['program_id', 'start_date', 'end_date']);
            }
        });
    }

    private static function indexExists(string $table, array $columns): bool
    {
        // Build the default index name that Laravel would generate
        $name = $table . '_' . implode('_', $columns) . '_index';
        return Schema::hasIndex($table, $name);
    }
};
