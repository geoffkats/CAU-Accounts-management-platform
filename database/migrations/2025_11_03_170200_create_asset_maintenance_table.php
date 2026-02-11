<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['preventive', 'corrective', 'inspection', 'upgrade'])->default('preventive');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            
            $table->text('description');
            $table->text('work_performed')->nullable();
            $table->string('performed_by')->nullable(); // Technician or company name
            
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('invoice_number')->nullable();
            
            $table->integer('downtime_hours')->default(0); // Hours asset was unavailable
            
            $table->text('notes')->nullable();
            $table->text('parts_replaced')->nullable();
            
            // Next maintenance
            $table->date('next_maintenance_date')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance');
    }
};
