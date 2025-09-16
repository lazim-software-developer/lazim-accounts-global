<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountSubType extends Model
{
    protected $fillable = [
        'name',
        'type',
        'created_by',
        'building_id',
        'parent_id'

    ];

    public function parent()
    {
        return $this->belongsTo(ChartOfAccountSubType::class, 'parent_id');
    }

    public function childSubTypes()
    {
        return $this->hasMany(ChartOfAccountSubType::class, 'parent_id');
    }

    public function typeObj()
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type');
    }
}
