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
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();

            $table->string('payment_status')->default('pending');
            $table->timestamp('charged_at')->nullable();

            $table->index(['company_id', 'status', 'date'], 'tasks_company_status_date_idx');
            $table->index(['status', 'date'], 'tasks_status_date_idx');
            $table->index(['assigned_worker_id', 'status'], 'tasks_assigned_worker_status_idx');
            $table->index(['latitude', 'longitude'], 'tasks_lat_lng_idx');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
