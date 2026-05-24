<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_coupon_id')->constrained('wallet_coupons')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->unique(['wallet_coupon_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_coupon_redemptions');
    }
};
