<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->nullableMorphs('reference');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->index(['company_id', 'id'], 'company_wallet_company_id_id_idx');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_wallet_transactions');
    }
};
