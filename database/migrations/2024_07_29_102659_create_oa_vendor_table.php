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
        Schema::create('oa_vendor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lazim_owner_association_id');
            $table->foreignId('vendor_id')->constrained('venders');
            $table->boolean('active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oa_vendor');
    }
};
