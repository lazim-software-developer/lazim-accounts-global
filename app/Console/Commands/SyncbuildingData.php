<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Utility;
use Illuminate\Console\Command;
use App\Jobs\PopulatingTaxesJob;
use App\Jobs\FetchAndSaveServices;
use App\Models\ChartOfAccountType;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Jobs\PopulatingBankAccountsJob;
use App\Jobs\PopulatingProductServicesJob;
use App\Jobs\PopulatingProductServiceUnitsJob;
use App\Jobs\PopulatingProductServiceCategoriesJob;

class SyncbuildingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-building-data {--building_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync building data from OA DB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $buildingIds = $this->option('building_id');

        // Build the user query conditionally
        $userQuery = User::where('type', 'building')
            ->where('processed', 0);
        if (!empty($buildingIds)) {
            $userQuery->whereIn('id', (array)$buildingIds);
        }
        $users = $userQuery->get();

        if ($users->isEmpty()) {
            $this->error('No buildings found to sync.');
            return;
        }

        foreach ($users as $user) {
            $role_r = Role::findByName('company');
            $user->assignRole($role_r);
            $ownerAssociationId = $user->owner_association_id;
            $buildingId = $user->building_id;

            $buildingQuery = DB::connection('mysql_lazim')->table('buildings')
                ->join('building_owner_association', 'buildings.id', '=', 'building_owner_association.building_id')
                ->where('building_owner_association.owner_association_id', $ownerAssociationId)
                ->where('buildings.id', $buildingId)
                ->whereNull('buildings.deleted_at')
                ->select('buildings.*');

            $building = $buildingQuery->first();

            if ($building) {
                $this->info("Syncing building data for building ID: {$building->id}");
                // Place your building sync logic here
                try {
                    if(! ChartOfAccountType::where('building_id', $building->id)->exists()) {
                        Utility::chartOfAccountTypeData($user->id, $building->id);
                        // default chart of account for new company
                        Utility::chartOfAccountData1($user->id, $building->id);
                        Utility::updateCategory($user->id, $building->id);

                        $user->userDefaultDataRegister($user->id);
                    }
                    PopulatingTaxesJob::dispatch($user,$building);

                    PopulatingProductServiceUnitsJob::dispatch($user,$building);

                    PopulatingProductServiceCategoriesJob::dispatch($user,$building);

                    PopulatingProductServicesJob::dispatch($user,$building);

                    FetchAndSaveServices::dispatch($user->id, $building->id);

                    PopulatingBankAccountsJob::dispatch($user,$building);
                } catch (\Exception $e) {
                    $this->error("Failed to sync building data for building ID: {$building->id}");
                    Log::error("Failed to sync building data for building ID: {$building->id}", ['error' => $e->getMessage()]);
                }

                $user->update(['processed' => 1]);
                $this->info("Syncing building data for building ID: {$building->id} completed");
            } else {
                $this->warn("No building found in second database for building ID: {$buildingId}");
            }
        }
    }
}
