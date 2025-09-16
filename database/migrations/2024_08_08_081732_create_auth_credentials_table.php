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
        Schema::create('auth_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('client_id');
            $table->string('api_key');
            $table->string('module');
            $table->unsignedBigInteger('owner_association_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_credentials');
    }
};
