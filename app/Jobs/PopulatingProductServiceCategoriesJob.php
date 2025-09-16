<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\ChartOfAccount;
use App\Models\ProductService;
use Illuminate\Support\Facades\Log;
use App\Models\ProductServiceCategory;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PopulatingProductServiceCategoriesJob implements ShouldQueue
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

        $category = [[
            'name' => 'Service Charges',
            'type' => 'income',
            'created_by' => $user->id,
            'building_id' => $building->id,
            'chart_account_id' => ChartOfAccount::where('building_id',$building->id)->where('name', 'Service Charges '.date('Y').' (Tax) - Gen & Res Fund')->first(),
            'created_at' => now(),
            'updated_at' => now(),
        ]];

        $productServiceCategories = ProductServiceCategory::insert($category);
        Log::info("PopulatingProductServiceCategoriesJob 47 categories ---------".json_encode($productServiceCategories));
    }
}
