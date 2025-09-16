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
        Schema::create('revenue_customer_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_id');
            $table->integer('customer_id');
            $table->integer('invoice_id')->nullable();
            $table->decimal('amount', 15, 2)->default('0.0');
            $table->string('reference_type')->nullable();
            $table->text('reference_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_customer_detail');
    }
};
