<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained()->onDelete('restrict');
            $table->foreignId('program_id')->nullable()->constrained()->onDelete('set null');
            $table->string('asset_tag')->unique(); // Unique identifier (e.g., LAP-001, PROJ-025)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            
            // Financial Information
            $table->decimal('purchase_price', 12, 2);
            $table->date('purchase_date');
            $table->string('supplier')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('salvage_value', 12, 2)->default(0);
            
            // Depreciation
            $table->decimal('depreciation_rate', 5, 2); // Percentage per year
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'units_of_production'])->default('straight_line');
            $table->integer('useful_life_years');
            $table->decimal('accumulated_depreciation', 12, 2)->default(0);
            $table->decimal('current_book_value', 12, 2);
            
            // Status & Location
            $table->enum('status', ['active', 'in_maintenance', 'retired', 'disposed', 'lost', 'stolen'])->default('active');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            
            // Warranty
            $table->date('warranty_expiry')->nullable();
            
            // Assignment
            $table->foreignId('assigned_to_staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('assigned_to_student')->nullable(); // Student name or ID
            $table->date('assigned_date')->nullable();
            
            // Disposal
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_value', 12, 2)->nullable();
            $table->text('disposal_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
