<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_id',
        'date',
        'account_id',
        'payment_method',
        'add_receipt',
        'reference',
        'description',
        'deleted_at',
    ];


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function getVendorTransactionLine(){
        return StakeholderTransactionLine::where('reference_id',  $this->id)->where('reference', 'Bill Payment')->first();
    }

    public function updateVendorBalance(){
        Utility::updateUserTransactionLine('vendor', $this->bill->vender_id, $this->amount, 'debit', 'Bill Payment', $this->id, $this->date);
    }

    public function deleteVendorTransactionLine(){
        if($this->getVendorTransactionLine()){
            $transactionCreatedAt = $this->getVendorTransactionLine()->created_at;
            $this->getVendorTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('vender_id', $this->bill->vender_id, $transactionCreatedAt);
        }
    }
}
