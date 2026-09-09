<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->time('execution_window_from')->nullable()->after('execution_time');
            $table->time('execution_window_to')->nullable()->after('execution_window_from');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['execution_window_from', 'execution_window_to']);
        });
    }
};
