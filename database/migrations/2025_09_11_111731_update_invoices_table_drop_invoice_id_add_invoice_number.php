<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_id');

            $table->string('invoice_number')->unique()->after('id');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->dropColumn('invoice_number');
        });
    }
};
