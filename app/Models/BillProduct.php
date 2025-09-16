<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillProduct extends Model
{
    protected $fillable = [
        'product_id',
        'bill_id',
        'quantity',
        'tax',
        'discount',
        'total',
        'price',
        'description',
        'tax_amount',
        'bill_account_id',
    ];

    public function product()
    {
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }
}
