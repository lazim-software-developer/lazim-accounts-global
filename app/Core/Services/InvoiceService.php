<?php

namespace App\Core\Services;

use App\Core\Traits\AuthenticatedUserTrait;
use App\Core\Traits\PagingTrait;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceService
{
    use PagingTrait;
    use AuthenticatedUserTrait;
}
