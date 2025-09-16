<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InvoiceRevenue extends Model
{
    protected $table = 'invoice_revenue';
    protected $fillable = [
        'invoice_number',
        'revenue_id',
        'adjusted_amount',
    ];
}
