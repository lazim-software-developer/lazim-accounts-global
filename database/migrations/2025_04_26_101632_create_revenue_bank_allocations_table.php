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
        if (Schema::hasTable('revenue_bank_allocations')) {
            return;
        }else{
            Schema::create('revenue_bank_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->decimal('amount', 15, 2);
            $table->foreign('revenue_id')->references('id')->on('revenues')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('restrict');

            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('revenue_bank_allocations')) {
            Schema::dropIfExists('revenue_bank_allocations');
        }
    }
};
