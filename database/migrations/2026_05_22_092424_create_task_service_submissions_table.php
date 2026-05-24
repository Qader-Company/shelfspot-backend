<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_service_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->json('form_data');
            $table->string('status')->default('draft');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('task_service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_service_submissions');
    }
};
