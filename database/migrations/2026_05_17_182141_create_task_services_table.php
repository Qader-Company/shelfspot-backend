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
        Schema::create('task_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->text('execution_instructions')->nullable();

            $table->json('request_details')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unique(['task_id', 'service_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_services');
    }
};
