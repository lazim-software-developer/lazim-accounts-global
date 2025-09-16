<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BuildingVendor extends Model
{

    protected $fillable   = [
        'building_id',
        'vendor_id',
        'created_at',
        'updated_at',
    ];

    
}
