<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'date',
        'amount',
        'account_id',
        'payment_method',
        'order_id',
        'currency',
        'txn_id',
        'payment_type',
        'receipt',
        'add_receipt',
        'reference',
        'description',

    ];


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getCustomerTransactionLine(){
        return StakeholderTransactionLine::where('reference_id',  $this->id)->where('reference', "Invoice Payment")->first();
    }

    public function updateCustomerBalance(){
        Utility::updateUserTransactionLine('customer', $this->invoice->customer_id, $this->amount, 'credit', "Invoice Payment", $this->id, $this->date);
    }

    public function deleteCustomerTransactionLine(){
        if($this->getCustomerTransactionLine()){
            $transactionCreatedAt = $this->getCustomerTransactionLine()->created_at;
            $this->getCustomerTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('customer_id', $this->invoice->customer_id, $transactionCreatedAt);
        }
    }
}
