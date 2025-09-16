<?php

namespace App\Jobs;

use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FetchAndSaveServices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $user,protected $building)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       $result = Utility::updateService($this->user, $this->building);
        Log::info("FetchAndSaveServices 18 services ---------".json_encode($result));
    }
}
