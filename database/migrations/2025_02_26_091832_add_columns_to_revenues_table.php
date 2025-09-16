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
        Schema::table('revenues', function (Blueprint $table) {
            $table->date('transaction_date')->nullable()->after('id');
            $table->string('transaction_method')->nullable()->after('transaction_date');
            $table->string('transaction_number')->nullable()->after('transaction_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->dropColumn(['transaction_date', 'transaction_method', 'transaction_number']);
        });
    }
};
