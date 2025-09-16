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
        Schema::create('tally_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('building_id');
            $table->date('date');
            $table->enum('type', ['master', 'voucher']);
            $table->enum('subtype', [
                'group', 'ledger', 'costcategory', 'costcentre', 'budget',
                'sales', 'receipt', 'purchase', 'payment'
            ]);
            $table->string('voucher_number')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tally_acknowledgements');
    }
};
