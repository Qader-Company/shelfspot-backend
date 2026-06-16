<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
            $table->string('portal')->nullable()->after('guard_name')->index();
            $table->foreignId('company_id')->nullable()->after('portal')->constrained()->cascadeOnDelete();
            $table->index(['portal', 'company_id']);
            $table->unique(['name', 'guard_name', 'portal', 'company_id']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
            $table->string('portal')->nullable()->after('guard_name')->index();
            $table->foreignId('company_id')->nullable()->after('portal')->constrained()->cascadeOnDelete();
            $table->index(['portal', 'company_id']);
            $table->unique(['name', 'guard_name', 'portal', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name', 'portal', 'company_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('portal');
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name', 'portal', 'company_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('portal');
            $table->unique(['name', 'guard_name']);
        });
    }
};
