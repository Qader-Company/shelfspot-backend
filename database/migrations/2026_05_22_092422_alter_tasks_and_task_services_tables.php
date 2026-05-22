<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tasks MODIFY latitude DECIMAL(10,7) NOT NULL');
        DB::statement('ALTER TABLE tasks MODIFY longitude DECIMAL(10,7) NOT NULL');

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft')->after('execution_time');
            $table->foreignId('assigned_worker_id')->nullable()->after('status')->constrained('workers')->nullOnDelete();
            $table->unsignedInteger('estimated_duration_minutes')->default(60)->after('assigned_worker_id');
            $table->dateTime('expires_at')->nullable()->after('estimated_duration_minutes');
            $table->string('location_name')->nullable()->after('longitude');
            $table->text('address')->nullable()->after('location_name');
            $table->decimal('subtotal', 10, 2)->default(0)->after('address');
            $table->decimal('total_price', 10, 2)->default(0)->after('subtotal');
            $table->text('notes')->nullable()->after('total_price');
            $table->foreignId('rescheduled_from_task_id')->nullable()->after('notes')->constrained('tasks')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable()->after('rescheduled_from_task_id');
            $table->timestamp('started_at')->nullable()->after('accepted_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('declined_at')->nullable()->after('completed_at');
            $table->text('decline_reason')->nullable()->after('declined_at');
            $table->string('payment_status')->default('pending')->after('decline_reason');
            $table->timestamp('charged_at')->nullable()->after('payment_status');

            $table->index(['company_id', 'status', 'date'], 'tasks_company_status_date_idx');
            $table->index(['status', 'date'], 'tasks_status_date_idx');
            $table->index(['assigned_worker_id', 'status'], 'tasks_assigned_worker_status_idx');
            $table->index(['latitude', 'longitude'], 'tasks_lat_lng_idx');
        });

        Schema::table('task_services', function (Blueprint $table) {
            $table->json('request_details')->nullable()->after('execution_instructions');
            $table->decimal('unit_price', 10, 2)->default(0)->after('request_details');
            $table->string('status')->default('pending')->after('unit_price');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('status');
            $table->unique(['task_id', 'service_id'], 'task_services_task_service_unique');
        });

        Schema::table('task_service_products', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('product_details');
            $table->unique(['task_service_id', 'product_id'], 'task_service_products_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::table('task_service_products', function (Blueprint $table) {
            $table->dropUnique('task_service_products_unique_pair');
            $table->dropColumn('quantity');
        });

        Schema::table('task_services', function (Blueprint $table) {
            $table->dropUnique('task_services_task_service_unique');
            $table->dropColumn(['request_details', 'unit_price', 'status', 'sort_order']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_company_status_date_idx');
            $table->dropIndex('tasks_status_date_idx');
            $table->dropIndex('tasks_assigned_worker_status_idx');
            $table->dropIndex('tasks_lat_lng_idx');

            $table->dropForeign(['company_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['assigned_worker_id']);
            $table->dropForeign(['rescheduled_from_task_id']);

            $table->dropColumn([
                'company_id', 'created_by', 'status', 'assigned_worker_id', 'estimated_duration_minutes',
                'expires_at', 'location_name', 'address', 'subtotal', 'total_price', 'notes',
                'rescheduled_from_task_id', 'accepted_at', 'started_at', 'completed_at', 'declined_at',
                'decline_reason', 'payment_status', 'charged_at',
            ]);
        });

        DB::statement('ALTER TABLE tasks MODIFY latitude VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE tasks MODIFY longitude VARCHAR(255) NOT NULL');
    }
};
