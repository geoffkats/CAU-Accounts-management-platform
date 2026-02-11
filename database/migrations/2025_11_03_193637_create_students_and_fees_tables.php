<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Students table
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique(); // e.g., STU-2025-001
            $table->foreignId('program_id')->constrained()->onDelete('restrict');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_email')->nullable();
            $table->date('enrollment_date');
            $table->enum('status', ['active', 'graduated', 'suspended', 'withdrawn'])->default('active');
            $table->string('class_level')->nullable(); // Year 1, Year 2, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['program_id', 'status']);
            $table->index('student_id');
        });

        // Fee structures table
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Tuition Fee - Term 1"
            // Keep indexed string columns short to avoid MySQL index length limits
            $table->string('term', 32); // Term 1, Term 2, Term 3, Annual
            $table->string('academic_year', 12); // e.g., 2025 or 2025/2026
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('UGX');
            $table->boolean('is_mandatory')->default(true);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['program_id', 'term', 'academic_year']);
        });

        // Student invoices table
        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('student_id')->constrained()->onDelete('restrict');
            $table->foreignId('program_id')->constrained()->onDelete('restrict');
            $table->string('term', 32);
            $table->string('academic_year', 12);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            $table->string('currency', 3)->default('UGX');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->decimal('amount_base', 10, 2)->nullable(); // Base currency
            $table->enum('status', ['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['student_id', 'status']);
            $table->index(['term', 'academic_year']);
            $table->index('invoice_number');
        });

        // Invoice items table
        Schema::create('student_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_structure_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        // Student payments table
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('student_id')->constrained()->onDelete('restrict');
            $table->foreignId('student_invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->date('payment_date');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('UGX');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->decimal('amount_base', 10, 2)->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'card'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['student_id', 'payment_date']);
            $table->index('payment_number');
        });

        // Payment allocations table (for splitting payments across multiple invoices)
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        // Scholarships table
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['full', 'partial', 'percentage'])->default('partial');
            $table->decimal('amount', 10, 2)->nullable(); // For fixed amount
            $table->decimal('percentage', 5, 2)->nullable(); // For percentage-based
            $table->string('currency', 3)->default('UGX');
            $table->string('sponsor_name')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Student scholarships (assignment)
        Schema::create('student_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('scholarship_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['student_id', 'status']);
        });

        // Payment plans table
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->integer('number_of_installments');
            $table->decimal('installment_amount', 10, 2);
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->date('start_date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });

        // Payment plan installments table
        Schema::create('payment_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->timestamps();
            
            $table->index(['payment_plan_id', 'status']);
        });

        // Payment reminders table
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['email', 'sms', 'both'])->default('email');
            $table->date('scheduled_date');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['scheduled_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
        Schema::dropIfExists('payment_plan_installments');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('student_scholarships');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('student_payments');
        Schema::dropIfExists('student_invoice_items');
        Schema::dropIfExists('student_invoices');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('students');
    }
};
