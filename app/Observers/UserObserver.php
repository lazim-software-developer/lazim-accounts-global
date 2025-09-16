<?php

namespace App\Observers;

use App\Jobs\PopulatingProductServicesJob;
use App\Jobs\PopulatingProductServiceUnitsJob;
use App\Jobs\PopulatingTaxesJob;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Tax;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // if($user->type == "company"){
        //     $connection = DB::connection('mysql_lazim');
        //     $buildings = $connection->table('buildings')->join('building_owner_association', 'buildings.id', '=', 'building_owner_association.building_id')
        //     ->where('building_owner_association.owner_association_id', $user->owner_association_id)
        //     ->select('buildings.*')
        //     ->get();

        //     foreach($buildings as $building){

        //         if(! ChartOfAccountType::where('building_id', $building->id)->exists()) {
        //             Utility::chartOfAccountTypeData($user->id, $building->id);
        //             // default chart of account for new company
        //             Utility::chartOfAccountData1($user->id, $building->id);
                
        //             $user->userDefaultDataRegister($user->id);
        //         }
        //         PopulatingTaxesJob::dispatch($user,$building);

        //         PopulatingProductServiceUnitsJob::dispatch($user,$building);
                
        //         PopulatingProductServicesJob::dispatch($user,$building);

        //     }  
        // }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
