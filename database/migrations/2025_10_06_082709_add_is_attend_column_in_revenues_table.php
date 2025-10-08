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
        if (!Schema::hasColumn('revenues', 'is_attend')) {
            Schema::table('revenues', function (Blueprint $table) {
                $table->integer('is_attend')->default(0)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('revenues', 'is_attend')) {
            Schema::table('revenues', function (Blueprint $table) {
                $table->dropColumn('is_attend');
            });
        }
    }
};
