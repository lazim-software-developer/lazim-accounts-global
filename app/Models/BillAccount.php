<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'chart_account_id',
        'price',
        'description',
        'type',
        'ref_id',
        'vat_chart_of_account_id',
        'vat_amount',
        'total_amount',
    ];
}
