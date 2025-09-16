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
        Schema::create('stakeholder_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vender_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('reference_sub_id')->nullable();
            $table->date('date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->timestamps(); // created_at and updated_at columns

            // Foreign key constraints
            $table->foreign('vender_id')->references('id')->on('venders')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stakeholder_transaction_lines');
    }
};
