<?php

namespace App\Core\Models;

use App\Core\Traits\DeleteTrait;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use SoftDeletes, DeleteTrait; // Enable soft delete functionality

    protected $fillable = ['name', 'token', 'abilities', 'expires_at'];
}
