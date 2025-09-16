<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueCustomerDetail extends Model
{

    protected $table = 'revenue_customer_detail';
    protected $fillable = ['revenue_id', 'customer_id', 'invoice_number', 'amount', 'reference_type', 'reference_details'];

    public function revenue()
    {
        return $this->belongsTo(Revenue::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
