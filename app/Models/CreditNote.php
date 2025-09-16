<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    const TYPE_CREDIT_NOTE = "Credit Note";
    protected $fillable = [
        'invoice',
        'customer',
        'amount',
        'date',
        'reference',
        'description',
        'vat_amount',
        'building_id',
        'created_by',
        'total_amount'
    ];

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'customer_id', 'customer');
    }

    public function getCustomerTransactionLine(){
        return StakeholderTransactionLine::where('reference_id',  $this->id)->where('reference', self::TYPE_CREDIT_NOTE)->first();
    }

    public function updateCustomerBalance(){
        Utility::updateUserTransactionLine('customer', $this->customer, $this->amount, 'credit', self::TYPE_CREDIT_NOTE, $this->id, $this->date);
    }

    public function deleteCustomerTransactionLine(){
        if($this->getCustomerTransactionLine()){
            $transactionCreatedAt = $this->getCustomerTransactionLine()->created_at;
            $this->getCustomerTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('customer_id', $this->customer, $transactionCreatedAt);
        }
    }


    protected static function boot()
    {
        parent::boot();

        // Add the deleting event hook
        static::deleting(function ($creditNote) {
            $creditNote->deleteCustomerTransactionLine();
            TransactionLines::deleteAndRecalculateTransactionBalance($creditNote, self::TYPE_CREDIT_NOTE);
        });
    }
}
