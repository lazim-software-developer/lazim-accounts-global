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
        Schema::table('bill_products', function (Blueprint $table) {
            $table->unsignedBigInteger('bill_account_id')->nullable();
            $table->foreign('bill_account_id')->references('id')->on('bill_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->dropColumn('bill_account_id');
        });
    }
};
