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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->decimal('initial_balance', 15, 2)->default(0); // Replace 'some_column' with the correct column name
        });

        Schema::table('venders', function (Blueprint $table) {
            $table->decimal('initial_balance', 15, 2)->default(0); // Replace 'some_column' with the correct column name
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('initial_balance', 15, 2)->default(0); // Replace 'some_column' with the correct column name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('initial_balance');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('initial_balance');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('initial_balance');
        });
    }
};
