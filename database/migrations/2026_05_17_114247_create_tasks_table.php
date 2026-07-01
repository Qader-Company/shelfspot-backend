<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('execution_time');
            $table->unsignedInteger('estimated_duration_minutes')->default(60);

            $table->decimal('longitude',10,7);
            $table->decimal('latitude',10,7);
            $table->string('location_name')->nullable();
            $table->text('address')->nullable();

            $table->decimal('total_price', 10, 2)->default(0);

            $table->foreignId('rescheduled_from_task_id')->nullable()->constrained('tasks')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('assigned_worker_id')->nullable()->constrained('workers')->nullOnDelete();

            $table->dateTime('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();


            $table->string('payment_status')->default('pending');
            $table->timestamp('charged_at')->nullable();

            $table->index(['company_id', 'status', 'date'], 'tasks_company_status_date_idx');
            $table->index(['status', 'date'], 'tasks_status_date_idx');
            $table->index(['assigned_worker_id', 'status'], 'tasks_assigned_worker_status_idx');
            $table->index(['latitude', 'longitude'], 'tasks_lat_lng_idx');


            $table->timestamps();
        });
        $this->add_lifecycle_fields_to_tasks_table();
        $this->add_review_lifecycle_fields_to_tasks_table();
        $this->add_start_deadline_extension_fields_to_tasks_table();
        $this->add_company_purged_at_to_tasks_table();

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }

    private function add_lifecycle_fields_to_tasks_table()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('start_deadline_at')
                ->nullable()
                ->after('accepted_at');
            $table->timestamp('worker_cancelled_at')
                ->nullable();
            $table->text('worker_cancel_reason')
                ->nullable()
                ->after('worker_cancelled_at');
            $table->timestamp('company_deleted_at')
                ->nullable()
                ->after('charged_at');

            $table->index(['status', 'payment_status', 'date'], 'tasks_status_payment_date_idx');
            $table->index(['company_deleted_at', 'company_id'], 'tasks_company_deleted_company_idx');
        });
    }
    private function add_review_lifecycle_fields_to_tasks_table()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('rejected_at')
                ->nullable()
                ->after('completed_at');
            $table->text('rejection_reason')
                ->nullable()
                ->after('rejected_at');
            $table->timestamp('company_accepted_at')
                ->nullable()
                ->after('rejection_reason');
            $table->timestamp('auto_accept_at')
                ->nullable()
                ->after('company_accepted_at');
            $table->timestamp('reopened_at')
                ->nullable()
                ->after('auto_accept_at');
            $table->text('reopen_reason')
                ->nullable()
                ->after('reopened_at');
            $table->index(['status', 'auto_accept_at'], 'tasks_status_auto_accept_idx');
        });
    }
    private function add_start_deadline_extension_fields_to_tasks_table()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedTinyInteger('start_deadline_extension_minutes')
                ->nullable()
                ->after('start_deadline_at');
            $table->timestamp('start_deadline_extended_at')
                ->nullable()
                ->after('start_deadline_extension_minutes');
        });
    }
    private function add_company_purged_at_to_tasks_table()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('company_purged_at')
                ->nullable()
                ->after('company_deleted_at');
            $table->index(['company_purged_at', 'company_id'], 'tasks_company_purged_company_idx');
        });
    }

};
