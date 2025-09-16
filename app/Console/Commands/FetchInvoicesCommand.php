<?php

namespace App\Console\Commands;

use App\Jobs\FetchAndSaveInvoices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch invoices from external API and save to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::connection('mysql_lazim')->table('buildings')->orderBy('id')->chunk(100, function ($buildings) {
            foreach ($buildings as $building) {
                dispatch(new FetchAndSaveInvoices($building));
            }
        });

        $this->info('The invoices have been fetched and saved successfully.');
    }
}
