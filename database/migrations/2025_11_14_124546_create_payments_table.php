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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('expense_id')->constrained()->onDelete('cascade');
        $table->foreignId('payment_account_id')
              ->constrained('accounts')
              ->comment('Bank, Cash, Mobile Money account')
              ->onDelete('restrict');
        $table->date('payment_date');
        $table->decimal('amount', 15, 2);
        $table->string('payment_method', 50)->nullable();
        $table->string('payment_reference', 100)->nullable()->comment('Transaction ID, Check number, etc.');
        $table->text('notes')->nullable();
        $table->timestamps();

        $table->index(['expense_id', 'payment_account_id']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
