<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\ProductServiceUnit;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PopulatingProductServiceUnitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $user, protected $building)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->user;
        $building = $this->building;

        $units = [[
            'name' => 'Service',
            'created_by' => $user->id,
            'building_id' =>$building->id,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Expense',
            'created_by' => $user->id,
            'building_id' =>$building->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]];

        $productServiceUnits = ProductServiceUnit::insert($units);
        Log::info("PopulatingProductServiceUnitsJob 47 units ---------".json_encode($productServiceUnits));
    }
}
