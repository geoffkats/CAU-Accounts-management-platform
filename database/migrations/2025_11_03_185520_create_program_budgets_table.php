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
        Schema::create('program_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->string('period_type'); // quarterly, annual
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('income_budget', 15, 2)->default(0);
            $table->decimal('expense_budget', 15, 2)->default(0);
            $table->string('currency', 10)->default('UGX');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['program_id', 'start_date', 'end_date']);
        });

        // Budget reallocation requests
        Schema::create('budget_reallocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_budget_id')->constrained('program_budgets')->onDelete('cascade');
            $table->foreignId('to_budget_id')->nullable()->constrained('program_budgets')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('category'); // income, expense
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_budgets');
    }
};
