<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedTinyInteger('start_deadline_extension_minutes')->nullable()->after('start_deadline_at');
            $table->timestamp('start_deadline_extended_at')->nullable()->after('start_deadline_extension_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'start_deadline_extension_minutes',
                'start_deadline_extended_at',
            ]);
        });
    }
};
