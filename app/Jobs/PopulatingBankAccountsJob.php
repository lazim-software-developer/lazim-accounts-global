<?php

namespace App\Jobs;

use App\Models\BankAccount;
use Illuminate\Bus\Queueable;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PopulatingBankAccountsJob implements ShouldQueue
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

        $accounts = [
            [
                'holder_name' => 'General Fund',
                'bank_name' => 'ADCB Bank',
                'account_number' => '-',
                'chart_account_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'General Fund - Bank Account'])->first()?->id,
                'created_by' => $user->id,
                'building_id' => $building->id,
            ],
            [
                'holder_name' => 'Reserve Fund',
                'bank_name' => 'ADCB Bank',
                'account_number' => '-',
                'chart_account_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Reserve Fund - Bank Account'])->first()?->id,
                'created_by' => $user->id,
                'building_id' => $building->id,
            ],
            [
                'holder_name' => 'General Fund + Reserve Fund',
                'bank_name' => 'ADCB Bank',
                'account_number' => '-',
                'chart_account_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'General Fund + Reserve Fund - Bank Account'])->first()?->id,
                'created_by' => $user->id,
                'building_id' => $building->id,
            ],
            [
                'holder_name' => 'Cash',
                'bank_name' => '-',
                'account_number' => '-',
                'chart_account_id' => ChartOfAccount::where(['building_id'=> $building->id,'name' => 'Cash'])->first()?->id,
                'created_by' => $user->id,
                'building_id' => $building->id,
            ],

        ];

        $bankAccount = BankAccount::insert($accounts);
        Log::info("PopulatingBankAccountsJob 70 bank accounts ---------".json_encode($bankAccount));
    }
}
