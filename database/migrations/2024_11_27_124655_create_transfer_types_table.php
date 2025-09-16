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
        Schema::create('transfer_types', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_type');
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->unsignedBigInteger('transferable_id');
            $table->string('transferable_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_types');
    }
};
