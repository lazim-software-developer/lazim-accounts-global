<?php

namespace App\Console\Commands;

use App\Jobs\FetchAndSaveReceipts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchReceiptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-receipt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::connection('mysql_lazim')->table('buildings')->orderBy('id')->chunk(100, function ($buildings) {
        foreach ($buildings as $building) {
            dispatch(new FetchAndSaveReceipts($building));
        }
        });

        $this->info('Receipt fetch jobs dispatched for all buildings.');
    }
}
