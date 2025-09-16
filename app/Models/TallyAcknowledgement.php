<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyAcknowledgement extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'type',
        'subtype',
        'voucher_number',
        'name',
    ];
}
