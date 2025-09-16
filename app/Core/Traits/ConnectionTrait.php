<?php

namespace App\Core\Traits;

use App\Core\Services\ResponseService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

trait ConnectionTrait
{


    /**
     * Get the custom MySQL connection for 'mysql_lazim'.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getLazimConnection()
    {
        // Use the custom connection name defined in config/database.php
        return \DB::connection('mysql_lazim');
    }

    public function getDefaultConnection()
    {
        // Use the custom connection name defined in config/database.php
        return \DB::connection();
    }
}
