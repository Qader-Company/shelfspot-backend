<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'sub_brands',
        'categories',
        'sub_categories',
        'products',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('deleted_by_catalog_parent_type')->nullable()->after('deleted_at');
                $table->unsignedBigInteger('deleted_by_catalog_parent_id')->nullable()->after('deleted_by_catalog_parent_type');
                $table->index(
                    ['deleted_by_catalog_parent_type', 'deleted_by_catalog_parent_id'],
                    $table->getTable().'_catalog_parent_delete_idx'
                );
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropIndex($table->getTable().'_catalog_parent_delete_idx');
                $table->dropColumn([
                    'deleted_by_catalog_parent_type',
                    'deleted_by_catalog_parent_id',
                ]);
            });
        }
    }
};
