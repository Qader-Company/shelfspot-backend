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
        'brands',
        'sub_brands',
        'categories',
        'sub_categories',
        'products',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('purge_status')->nullable()->after('deleted_at')->index();
                $table->text('purge_failure_reason')->nullable()->after('purge_status');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropIndex($table->getTable().'_purge_status_index');
                $table->dropColumn(['purge_status', 'purge_failure_reason']);
            });
        }
    }
};
