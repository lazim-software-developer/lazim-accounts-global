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
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->unsignedInteger('vat_percentage')->nullable(); // Use unsigned for percentages
            $table->unsignedBigInteger('building_id')->nullable()->index(); // Adding index for potential foreign key
            $table->unsignedBigInteger('created_by')->nullable()->index(); // Adding index for potential foreign key
            $table->decimal('total_amount', 15, 2)->default(0); // Default 0 is fine for total_amount
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropColumn(['vat_percentage', 'building_id', 'created_by', 'total_amount']); // Remove all added columns
        });
    }
};
