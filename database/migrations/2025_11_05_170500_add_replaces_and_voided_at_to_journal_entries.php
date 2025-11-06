<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('replaces_entry_id')->nullable()->after('reference')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['replaces_entry_id']);
            $table->dropColumn(['replaces_entry_id', 'voided_at']);
        });
    }
};
