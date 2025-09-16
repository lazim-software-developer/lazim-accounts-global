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
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->string('reference')->nullable(); // Replace 'column_name' with the correct column after which you want to add 'reference'
        });

        Schema::table('debit_notes', function (Blueprint $table) {
            $table->string('reference')->nullable(); // Replace 'column_name' with the correct column after which you want to add 'reference'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
