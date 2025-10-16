<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    const TYPE_DEBIT_NOTE = 'Debit Note';

    protected $fillable = [
        'bill',
        'vendor',
        'amount',
        'date',
        'reference',
        'vat_amount',
        'building_id',
        'created_by',
        'total_amount',
        'description',
    ];

    public function vendor()
    {
        return $this->hasOne('App\Models\Vender', 'vender_id', 'vendor');
    }

    public function getVendorTransactionLine()
    {
        return StakeholderTransactionLine::where('reference_id', $this->id)->where('reference', self::TYPE_DEBIT_NOTE)->first();
    }

    public function updateVendorBalance()
    {
        Utility::updateUserTransactionLine('vendor', $this->vendor, $this->amount, 'debit', self::TYPE_DEBIT_NOTE, $this->id, $this->date);
    }

    public function deleteVendorTransactionLine()
    {
        if ($this->getVendorTransactionLine()) {
            $transactionCreatedAt = $this->getVendorTransactionLine()->created_at;
            $this->getVendorTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('vender_id', $this->vendor, $transactionCreatedAt);
        }
    }
}
