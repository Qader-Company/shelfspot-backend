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
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone')->unique();
            $table->boolean('is_active')->default(true);
            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->softDeletes();
            $table->unique('user_id', 'workers_user_id_unique');
            $table->index(['is_active', 'last_latitude', 'last_longitude'], 'workers_active_location_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
