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
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('UGX');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->decimal('amount_base', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid');
            $table->enum('payment_terms', ['immediate', 'net_7', 'net_15', 'net_30', 'net_60', 'net_90'])->default('net_30');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('vendor_reference')->nullable(); // Vendor's invoice number
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_invoices');
    }
};
