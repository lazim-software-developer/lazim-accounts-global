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
            $table->enum('reference_type', ['new_ref', 'advance', 'against_ref', 'on_account'])
                ->after('account_id');
            $table->string('ref_details')->nullable()->after('reference_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->dropColumn('reference_type');
            $table->dropColumn('ref_details');
        });
    }
};
