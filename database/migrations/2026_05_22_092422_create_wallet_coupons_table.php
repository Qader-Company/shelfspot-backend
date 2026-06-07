<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redemptions_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_coupons');
    }
};
