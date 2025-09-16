<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevenueBankAllocation extends Model
{
    use HasFactory;

    protected $fillable = ['revenue_id', 'bank_account_id', 'amount'];

    public function revenue()
    {
        return $this->belongsTo(Revenue::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

}
