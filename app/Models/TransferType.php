<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferType extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_type',
        'reference_number',
        'date',
        'transferable_id',
        'transferable_type',
        'transaction_id',
        'payment_gateway',
        'cheque_number',
        'bank_name',
        'cash_received_by',
    ];

    public function transferable()
    {
        return $this->morphTo();
    }
}