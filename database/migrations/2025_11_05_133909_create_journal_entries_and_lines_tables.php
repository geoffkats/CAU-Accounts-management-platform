<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Journal Entries - Header table for each transaction
        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('reference')->unique(); // EXP-001, JE-001, etc.
                $table->string('type'); // expense, income, transfer, adjustment, opening_balance
                $table->text('description');
                $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
                // Keep income reference column for backward compatibility, but avoid invalid FK (no incomes table)
                $table->foreignId('income_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['draft', 'posted', 'void'])->default('posted');
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
                
                $table->index('date');
                $table->index('type');
                $table->index('status');
            });
        }

        // Journal Entry Lines - Detail table with debit/credit entries
        if (!Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
                $table->foreignId('account_id')->constrained()->restrictOnDelete();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
                
                $table->index(['journal_entry_id', 'account_id']);
                $table->index('account_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('journal_entry_lines')) {
            Schema::drop('journal_entry_lines');
        }
        if (Schema::hasTable('journal_entries')) {
            Schema::drop('journal_entries');
        }
    }
};
