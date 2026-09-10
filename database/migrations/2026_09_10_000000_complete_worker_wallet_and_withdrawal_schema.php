<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('worker_share_amount', 10, 2)->nullable()->after('total_price');
            $table->decimal('platform_share_amount', 10, 2)->nullable()->after('worker_share_amount');
            $table->unsignedTinyInteger('worker_share_percentage')->nullable()->after('platform_share_amount');
            $table->timestamp('settled_at')->nullable()->after('worker_share_percentage')->index();
        });

        Schema::table('worker_wallet_transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 191)->nullable()->after('balance_after')->unique();
            $table->text('description')->nullable()->after('reference_id');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->string('method')->nullable()->after('amount');
            $table->index(['status', 'created_at'], 'withdrawals_status_created_idx');
            $table->index(['worker_id', 'status'], 'withdrawals_worker_status_idx');
            $table->index('method', 'withdrawals_method_idx');
        });

        DB::table('withdrawal_requests')
            ->where('status', 'approved')
            ->update([
                'status' => 'paid',
                'processed_at' => DB::raw('COALESCE(processed_at, updated_at, created_at)'),
                'paid_at' => DB::raw('COALESCE(paid_at, processed_at, updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropIndex('withdrawals_status_created_idx');
            $table->dropIndex('withdrawals_worker_status_idx');
            $table->dropIndex('withdrawals_method_idx');
            $table->dropColumn('method');
        });

        Schema::table('worker_wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'description']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['settled_at']);
            $table->dropColumn([
                'worker_share_amount',
                'platform_share_amount',
                'worker_share_percentage',
                'settled_at',
            ]);
        });
    }
};
