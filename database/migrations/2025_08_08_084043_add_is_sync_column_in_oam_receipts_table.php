<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::connection(env('SECOND_DB_CONNECTION'))->hasColumn('oam_receipts', 'is_sync')) {
            Schema::connection(env('SECOND_DB_CONNECTION'))->table('oam_receipts', function (Blueprint $table) {
                $table->integer('is_sync')->default(0);
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql2')->table('oam_receipts', function (Blueprint $table) {
            $table->dropColumn('is_sync');
        });
    }
};
