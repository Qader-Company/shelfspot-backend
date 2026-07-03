<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['status', 'start_deadline_at'], 'tasks_status_start_deadline_idx');
            $table->index(['status', 'expires_at'], 'tasks_status_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_start_deadline_idx');
            $table->dropIndex('tasks_status_expires_idx');
        });
    }
};
