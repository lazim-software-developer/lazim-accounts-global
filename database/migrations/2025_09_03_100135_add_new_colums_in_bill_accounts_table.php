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
        Schema::table('bill_accounts', function (Blueprint $table) {
            $table->integer('vat_chart_of_account_id')->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_accounts', function (Blueprint $table) {
            $table->dropColumn('vat_chart_of_account_id');
            $table->dropColumn('vat_amount');
            $table->dropColumn('total_amount');
        });
    }
};
