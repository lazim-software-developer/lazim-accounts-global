<?php

namespace App\Jobs;

use App\Models\Tax;
use Illuminate\Bus\Queueable;
use App\Models\ChartOfAccount;
use App\Models\ProductService;
use App\Models\ProductServiceUnit;
use Illuminate\Support\Facades\Log;
use App\Models\ProductServiceCategory;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PopulatingProductServicesJob implements ShouldQueue
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

        $currentYear = date("Y");
                $product_services = [[
                    'name' =>'Legal Fee Reimbursement' ,
                    'sku' => 'SER'.random_int(111,999),
                    'sale_price' => 0,
                    'purchase_price' => 0,
                    'quantity' => 0,
                    'tax_id' => Tax::where(['building_id' => $building->id,'name' => 'VAT',])->first()->id,
                    'category_id' => ProductServiceCategory::where(['building_id'=> $building->id,'name' => 'Service Charges'])->first()?->id,
                    'unit_id' => ProductServiceUnit::where(['building_id'=> $building->id,'name' => 'Service'])->first()?->id,
                    'type' => 'Service',
                    'sale_chartaccount_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Legal Fee Reimbursement'])->first()?->id,
                    'expense_chartaccount_id' => 0,
                    'created_by' => $user->id,
                    'building_id' => $building->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' =>'Service Charges '.$currentYear.' (Non Tax) - General Fund' ,
                    'sku' => 'SER'.random_int(111,999),
                    'sale_price' => 0,
                    'purchase_price' => 0,
                    'quantity' => 0,
                    'tax_id' => Tax::where(['building_id' => $building->id,'name' => 'No Tax',])->first()->id,
                    'category_id' => ProductServiceCategory::where(['building_id'=> $building->id,'name' => 'Service Charges'])->first()?->id,
                    'unit_id' => ProductServiceUnit::where(['building_id'=> $building->id,'name' => 'Service'])->first()?->id,
                    'type' => 'Service',
                    'sale_chartaccount_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Service Charges '.$currentYear.' (Non Tax) - General Fund'])->first()?->id,
                    'expense_chartaccount_id' => 0,
                    'created_by' => $user->id,
                    'building_id' => $building->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' =>'Service Charges '.$currentYear.' (Non Tax) - Reserve Fund' ,
                    'sku' => 'SER'.random_int(111,999),
                    'sale_price' => 0,
                    'purchase_price' => 0,
                    'quantity' => 0,
                    'tax_id' => Tax::where(['building_id' => $building->id,'name' => 'No Tax',])->first()->id,
                    'category_id' => ProductServiceCategory::where(['building_id'=> $building->id,'name' => 'Service Charges'])->first()?->id,
                    'unit_id' => ProductServiceUnit::where(['building_id'=> $building->id,'name' => 'Service'])->first()?->id,
                    'type' => 'Service',
                    'sale_chartaccount_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Service Charges '.$currentYear.' (Non Tax) - Reserve Fund'])->first()?->id,
                    'expense_chartaccount_id' => 0,
                    'created_by' => $user->id,
                    'building_id' => $building->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' =>'Service Charges '.$currentYear.' (Non Tax) - Gen & Res Fund' ,
                    'sku' => 'SER'.random_int(111,999),
                    'sale_price' => 0,
                    'purchase_price' => 0,
                    'quantity' => 0,
                    'tax_id' => Tax::where(['building_id' => $building->id,'name' => 'No Tax',])->first()->id,
                    'category_id' => ProductServiceCategory::where(['building_id'=> $building->id,'name' => 'Service Charges'])->first()?->id,
                    'unit_id' => ProductServiceUnit::where(['building_id'=> $building->id,'name' => 'Service'])->first()?->id,
                    'type' => 'Service',
                    'sale_chartaccount_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Service Charges '.$currentYear.' (Non Tax) - Gen & Res Fund'])->first()?->id,
                    'expense_chartaccount_id' => 0,
                    'created_by' => $user->id,
                    'building_id' => $building->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' =>'Service Charges '.$currentYear.' (Tax) - Gen & Res Fund' ,
                    'sku' => 'SER'.random_int(111,999),
                    'sale_price' => 0,
                    'purchase_price' => 0,
                    'quantity' => 0,
                    'tax_id' => Tax::where(['building_id' => $building->id,'name' => 'VAT',])->first()->id,
                    'category_id' => ProductServiceCategory::where(['building_id'=> $building->id,'name' => 'Service Charges'])->first()?->id,
                    'unit_id' => ProductServiceUnit::where(['building_id'=> $building->id,'name' => 'Service'])->first()?->id,
                    'type' => 'Service',
                    'sale_chartaccount_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Service Charges '.$currentYear.' (Tax) - Gen & Res Fund'])->first()?->id,
                    'expense_chartaccount_id' => 0,
                    'created_by' => $user->id,
                    'building_id' => $building->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' =>'Service Charges '.$currentYear.'  (Non Tax)' ,
                    'sku' => 'SER'.random_int(111,999),
                    'sale_price' => 0,
                    'purchase_price' => 0,
                    'quantity' => 0,
                    'tax_id' => Tax::where(['building_id' => $building->id,'name' => 'No Tax',])->first()->id,
                    'category_id' => ProductServiceCategory::where(['building_id'=> $building->id,'name' => 'Service Charges'])->first()?->id,
                    'unit_id' => ProductServiceUnit::where(['building_id'=> $building->id,'name' => 'Service'])->first()?->id,
                    'type' => 'Service',
                    'sale_chartaccount_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Service Charges '.$currentYear.'  (Non Tax)'])->first()?->id,
                    'expense_chartaccount_id' => 0,
                    'created_by' => $user->id,
                    'building_id' => $building->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]];

        $productServices = ProductService::insert($product_services);
        Log::info("PopulatingProductServicesJob 142 productServices ---------".json_encode($productServices));
    }
}
