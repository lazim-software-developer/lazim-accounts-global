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
        Schema::table('invoices', function (Blueprint $table) {
            $table->longText('invoice_pdf_link')->nullable();
            $table->longText('invoice_detail_link')->nullable();
            $table->longText('payment_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_pdf_link');
            $table->dropColumn('invoice_detail_link');
            $table->dropColumn('payment_url');
        });
    }
};
