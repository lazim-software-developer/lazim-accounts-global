<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountType extends Model
{
    const DEBIT_ACCOUNT_TYPE = ['assets', 'expenses'];
    const CREDIT_ACCOUNT_TYPE = ['liabilities', 'income', 'equity'];

    protected $fillable = [
        'name',
        'created_by',
        'building_id'
    ];

}
