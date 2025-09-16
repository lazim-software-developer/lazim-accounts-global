<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFlat extends Model
{
    protected $table = 'customer_flat';
    protected $fillable = [
        'customer_id', 'flat_id', 'active', 'building_id', 'property_number'
    ];
}
