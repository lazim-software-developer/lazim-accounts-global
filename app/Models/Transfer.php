<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'from_account',
        'to_account',
        'amount',
        'date',
        'payment_method',
        'reference',
        'description',
        'created_by',
    ];

    public function fromBankAccount()
    {
        return $this->belongsTo('App\Models\BankAccount', 'from_account');
    }

    public function toBankAccount()
    {
        return $this->belongsTo('App\Models\BankAccount', 'to_account');
    }

}
