<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'subtotal')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('subtotal');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'subtotal')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('address');
        });
    }
};
