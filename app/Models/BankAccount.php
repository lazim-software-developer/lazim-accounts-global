<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'holder_name',
        'bank_name',
        'account_number',
        'chart_account_id',
        'opening_balance',
        'contact_number',
        'bank_address',
        'created_by',
    ];

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }

    // public function getOpeningBalanceAttribute()
    // {
    //     return TransactionLines::where('account_id', $this->chart_account_id)->orderBy('id', 'desc')->first()
    //     ? TransactionLines::where('account_id', $this->chart_account_id)->orderBy('id', 'desc')->first()->closing_balance
    //     : 0;
    // }
}

