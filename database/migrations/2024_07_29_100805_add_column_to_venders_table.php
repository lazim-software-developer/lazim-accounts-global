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
        Schema::table('venders', function (Blueprint $table) {
            $table->unsignedBigInteger('lazim_vendor_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venders', function (Blueprint $table) {
            $table->dropColumn('lazim_vendor_id');
        });
    }
};
