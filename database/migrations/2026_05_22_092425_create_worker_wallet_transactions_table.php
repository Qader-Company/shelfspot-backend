<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->nullableMorphs('reference');
            $table->index(['worker_id', 'id'], 'worker_wallet_worker_id_id_idx');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_wallet_transactions');
    }
};
