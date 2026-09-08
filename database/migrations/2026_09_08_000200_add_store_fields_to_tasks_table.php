<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('company_id')
                ->constrained('stores')
                ->nullOnDelete();
            $table->string('store_number', 100)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn('store_number');
        });
    }
};
