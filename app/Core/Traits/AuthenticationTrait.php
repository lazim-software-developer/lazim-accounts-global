<?php

namespace App\Core\Traits;

use Illuminate\Support\Facades\Auth;



// using for web as of now
trait AuthenticationTrait
{
    /**
     * Get the customer authentication guard.
     *
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function getGuard(string $guard = 'customer')
    {
        return \Auth::guard($guard);
    }

    protected function getAuthenticatedUser()
    {
        return Auth::user();
    }
}
