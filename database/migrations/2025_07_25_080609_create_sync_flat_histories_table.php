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
        Schema::create('sync_flat_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('flat_id');
            $table->integer('building_id');
            $table->integer('sync_by');
            $table->string('sync_date');
            $table->integer('sync_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_flat_histories');
    }
};
