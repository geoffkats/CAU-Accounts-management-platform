<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('prev_hash', 64)->nullable()->after('user_agent');
            $table->string('hash', 64)->nullable()->after('prev_hash');
            $table->index('hash');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['hash']);
            $table->dropColumn(['prev_hash', 'hash']);
        });
    }
};
