<?php

namespace App\Jobs;

use App\Models\Tax;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PopulatingTaxesJob implements ShouldQueue
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
        $taxes = Tax::insert([[
            'name' => 'VAT',
            'rate' => 5,
            'created_by' => $user->id,
            'building_id' => $building->id,
            'created_at' => now(),
            'updated_at' => now(),
        ],[
            'name' => 'No Tax',
            'rate' => 0,
            'created_by' => $user->id,
            'building_id' => $building->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);
        Log::info("PopulatingTaxesJob 47 taxes ---------".json_encode($taxes));
    }
}
