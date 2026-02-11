<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'consultant'])->default('contract');
            $table->enum('payment_method', ['bank', 'mobile_money', 'cash'])->default('mobile_money');
            $table->string('mobile_money_provider')->nullable(); // MTN, Airtel
            $table->string('mobile_money_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('nssf_number')->nullable();
            $table->string('tin_number')->nullable(); // Tax Identification Number
            $table->decimal('base_salary', 15, 2)->nullable(); // For full-time staff
            $table->decimal('hourly_rate', 10, 2)->nullable(); // For contract/part-time
            $table->boolean('is_active')->default(true);
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
