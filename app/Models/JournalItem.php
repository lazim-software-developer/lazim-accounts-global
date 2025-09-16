<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    protected $fillable = [
        'journal',
        'account',
        'debit',
        'credit',
        'type'
    ];

    public function accounts()
    {
        return match ($this->type) {
            'account' => $this->hasOne('App\Models\ChartOfAccount', 'id', 'account'),
            'customer' => $this->hasOne('App\Models\Customer', 'id', 'account'),
            'vendor' => $this->hasOne('App\Models\Vender', 'id', 'account'),
        };
    }
}
