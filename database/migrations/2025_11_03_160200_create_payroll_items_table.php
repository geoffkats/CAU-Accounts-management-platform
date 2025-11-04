<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->decimal('classes_taught', 8, 2)->nullable();
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('paye_amount', 15, 2)->default(0);
            $table->decimal('nssf_employee', 15, 2)->default(0);
            $table->decimal('nssf_employer', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('bonuses', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('transaction_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
