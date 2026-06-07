<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_wallet_transactions', function (Blueprint $table) {
            $table->nullableMorphs('reference');
        });
    }

    public function down(): void
    {
        Schema::table('company_wallet_transactions', function (Blueprint $table) {
            $table->dropMorphs('reference');
        });
    }
};
