<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('assigned_to_student')->nullable();
            $table->date('assigned_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->text('assignment_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->enum('condition_on_return', ['good', 'fair', 'poor', 'damaged'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
