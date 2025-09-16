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
        Schema::table('invoice_revenue', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_revenue', 'invoice_id')) {
                $table->dropColumn('invoice_id');
            }

            $table->string('invoice_number')->after('id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_revenue', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_revenue', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }

            // ✅ rollback ke time invoice_id wapas add karna
            $table->unsignedBigInteger('invoice_id')->after('id');
        });
    }
};
