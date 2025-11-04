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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_website')->nullable();
            $table->text('company_address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency')->default('UGX');
            $table->string('currency_symbol')->default('UGX');
            $table->date('fiscal_year_start')->nullable();
            $table->date('fiscal_year_end')->nullable();
            $table->string('date_format')->default('d/m/Y');
            $table->string('timezone')->default('Africa/Kampala');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
