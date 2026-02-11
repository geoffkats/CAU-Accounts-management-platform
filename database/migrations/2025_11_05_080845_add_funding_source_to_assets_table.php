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
        Schema::table('assets', function (Blueprint $table) {
            // Drop program_id foreign key and column
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
            
            // Add funding_source column
            $table->string('funding_source', 20)->after('asset_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Remove funding_source
            $table->dropColumn('funding_source');
            
            // Re-add program_id
            $table->foreignId('program_id')->nullable()->after('asset_category_id')->constrained()->nullOnDelete();
        });
    }
};
