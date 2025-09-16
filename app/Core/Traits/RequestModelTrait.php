<?php

namespace App\Core\Traits;

trait RequestModelTrait
{
    /**
     * Retrieve a model instance from the current request attributes.
     *
     * @param  string  $key
     * @param  string  $modelClass
     * @return mixed
     */
    public function getModelFromRequest(string $key, string $modelClass)
    {
        $model = request()->attributes->get($key);

        return $model instanceof $modelClass ? $model : null;
    }

    /**
     * Retrieve the authenticated user from the request attributes.
     *
     * @return \App\Models\User|null
     */
    // public function getAuthenticatedUser()
    // {
    //     return $this->getModelFromRequest('authenticated_user', \App\Models\User::class);
    // }
}
