<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_review_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_role');
            $table->text('message');
            $table->timestamps();

            $table->index(['task_id', 'created_at'], 'task_review_messages_task_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_review_messages');
    }
};
