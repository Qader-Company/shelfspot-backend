<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_wallet_transactions', function (Blueprint $table) {
            $table->index(['company_id', 'id'], 'company_wallet_company_id_id_idx');
        });

        Schema::table('worker_wallet_transactions', function (Blueprint $table) {
            $table->index(['worker_id', 'id'], 'worker_wallet_worker_id_id_idx');
        });
    }

//    public function down(): void
//    {
//        Schema::table('worker_wallet_transactions', function (Blueprint $table) {
//            $table->dropIndex('worker_wallet_worker_id_id_idx');
//        });
//
//        Schema::table('company_wallet_transactions', function (Blueprint $table) {
//            $table->dropIndex('company_wallet_company_id_id_idx');
//        });
//    }
};
