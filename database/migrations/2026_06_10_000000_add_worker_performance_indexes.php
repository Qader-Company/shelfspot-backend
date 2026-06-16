<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->unique('user_id', 'workers_user_id_unique');
            $table->index(['is_active', 'last_latitude', 'last_longitude'], 'workers_active_location_idx');
        });
    }
};
