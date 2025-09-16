<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ChartOfAccountType;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Jobs\{
    PopulatingTaxesJob,
    FetchAndSaveServices,
    PopulatingBankAccountsJob,
    PopulatingProductServicesJob,
    PopulatingProductServiceUnitsJob,
    PopulatingProductServiceCategoriesJob
};

class SyncAccountingData extends Command
{
    protected $signature = 'app:sync-accounting-data';
    protected $description = 'Sync OA role users from DB A into company users in DB B and run jobs';

    public function handle(): void
    {
        try {
            DB::beginTransaction();

            $oaUsers = $this->getOAUsers();
            if ($oaUsers->isEmpty()) {
                $this->info("No OA users found to sync.");
                return;
            }

            $existingCompanyUsers = User::where('type', 'company')->get()->keyBy('email');
            $oaEmails = $oaUsers->pluck('email')->filter()->unique()->toArray();

            foreach ($oaUsers as $oaUser) {
                $this->createOrUpdateCompanyUser($oaUser, $existingCompanyUsers);
            }

            $this->disableMissingCompanyUsers($oaEmails, $existingCompanyUsers);

            DB::commit();
            $this->info("✅ Company sync completed.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("❌ Error syncing companies: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error("❌ Command failed: " . $e->getMessage());
        }
    }

    private function getOAUsers()
    {
        try {
            $oaConnection = DB::connection('mysql_lazim');
            $roleId = $oaConnection->table('roles')->where('name', 'OA')->value('id');

            if (!$roleId) {
                throw new \Exception("Role 'OA' not found in mysql_lazim.");
            }

            return $oaConnection->table('users')
                ->where('role_id', $roleId)
                ->join('owner_associations', 'users.owner_association_id', '=', 'owner_associations.id')
                ->get();
        } catch (\Throwable $e) {
            Log::error("Failed to fetch OA users: " . $e->getMessage());
            $this->error("Error fetching OA users: " . $e->getMessage());
            return collect(); // Empty collection to prevent crashes
        }
    }

    private function createOrUpdateCompanyUser($oaUser, $existingCompanyUsers)
    {
        // $this->info("Processing OA user: " . json_encode($oaUser));
        if (empty($oaUser->email)) {
            Log::warning("Skipped OA user with missing email. ID: {$oaUser->id}");
            return;
        }

        if ($existingCompanyUsers->has($oaUser->email)) {
            $this->info("User already exists: {$oaUser->email}");
            return;
        }

        try {
            $newUser = User::create([
                'name' => $oaUser->name ?? 'Unnamed Company',
                'email' => $oaUser->email,
                'email_verified_at' => now(),
                'type' => 'company',
                'lang' => 'en',
                'created_by' => 1,
                'plan' => 1,
                'owner_association_id' => $oaUser->owner_association_id ?? null,
                'password' => Hash::make('defaultPassword123'),
                'processed' => 1,
                'is_enable_login' => 1,
            ]);

            Utility::chartOfAccountData($newUser);
            $newUser->userDefaultDataRegister($newUser->id);

            $role = Role::where('name', 'company')->first();
            if ($role) {
                $newUser->assignRole($role);
            }

            $this->info("✅ Company user created: {$newUser->email}");
            $this->syncBuildingsAndRunJobs($newUser);
        } catch (\Throwable $e) {
            Log::error("Failed to create company user: " . $oaUser->email, ['error' => $e->getMessage()]);
            $this->error("Failed to create user: {$oaUser->email} — " . $e->getMessage());
        }
    }

    private function syncBuildingsAndRunJobs(User $user)
    {
        try {
            DB::connection('mysql_lazim')->enableQueryLog();
            $oaConnection = DB::connection('mysql_lazim');
            $buildings = $oaConnection->table('buildings')
                ->where('owner_association_id', $user->owner_association_id)
                ->whereNull('buildings.deleted_at')
                ->select('buildings.*')
                ->get();
            $queries = DB::connection('mysql_lazim')->getQueryLog();

            Log::info("query " . json_encode($queries));
            Log::info("Syncing buildings for user: {$user->email}", ['buildings' => $buildings]);
            $companyRole = Role::where('name', 'company')->first();
            $this->info("🏢 Found " . count($buildings) . " buildings for user: {$user->email}");
            // die;
            foreach ($buildings as $building) {
                // $this->info("Processing building: ". json_encode($building->id) ." for user: ". json_encode($user->email));
                $email = Utility::generateUniqueEmail($building->property_group_id);
                $buildingUser = User::firstOrNew(['email' => $email, 'type' => 'building']);

                if (!$buildingUser->exists) {
                    $buildingUser->fill([
                        'name' => $building->name,
                        'email_verified_at' => now(),
                        'password' => Hash::make('password'),
                        'lang' => 'en',
                        'created_by' => $user->id,
                        'plan' => 1,
                        'is_enable_login' => 1,
                        'building_id' => $building->id,
                        'owner_association_id' => $building->owner_association_id,
                        'referral_code' => Utility::generateReferralCode(),
                        'processed' => 0,
                    ])->save();

                    $this->info("🏢 Building user created: {$buildingUser->email} for building name: {$building->name}");
                }

                if ($companyRole) {
                    $buildingUser->assignRole($companyRole);
                }

                if (!ChartOfAccountType::where('building_id', $building->id)->exists()) {
                    $this->info("161  ------------  Setting up chart of accounts for building: {$building->name}");
                    Utility::chartOfAccountTypeData($buildingUser->id, $building->id);
                    Utility::chartOfAccountData1($buildingUser->id, $building->id);
                    Utility::updateCategory($buildingUser->id, $building->id);
                    $buildingUser->userDefaultDataRegister($buildingUser->id);
                }

                PopulatingTaxesJob::dispatch($buildingUser, $building);
                PopulatingProductServiceUnitsJob::dispatch($buildingUser, $building);
                PopulatingProductServiceCategoriesJob::dispatch($buildingUser, $building);
                PopulatingProductServicesJob::dispatch($buildingUser, $building);
                FetchAndSaveServices::dispatch($buildingUser->id, $building->id);
                PopulatingBankAccountsJob::dispatch($buildingUser, $building);

                $buildingUser->update(['processed' => 1]);
            }
        } catch (\Throwable $e) {
            Log::error("Error syncing buildings for user {$user->email}: " . $e->getMessage());
            $this->error("❌ Failed syncing buildings for {$user->email}");
        }
    }

    private function disableMissingCompanyUsers(array $oaEmails, $existingUsers)
    {
        $extraUsers = $existingUsers->filter(fn($user) => !in_array($user->email, $oaEmails));

        foreach ($extraUsers as $user) {
            try {
                $user->update(['is_enable_login' => 0]);
                $this->warn("⛔ Disabled: {$user->email}");
            } catch (\Throwable $e) {
                Log::warning("Failed to disable user: {$user->email}. Reason: " . $e->getMessage());
            }
        }
    }
}
