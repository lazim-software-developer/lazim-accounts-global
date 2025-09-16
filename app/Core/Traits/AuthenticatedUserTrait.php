<?php

namespace App\Core\Traits;



// only use for apis
trait AuthenticatedUserTrait
{

    use RequestModelTrait;

    // public readonly ?int $ownerId;
    // public readonly ?int $userId;

    // public function __construct()
    // {
    //     $user = $this->getAuthenticatedUser();
    //     $this->ownerId = $this->getAuthenticatedUser()?->owner_association_id;
    //     $this->userId = $this->getAuthenticatedUser()?->id;
    // }

    /**
     * Retrieve the authenticated user from the request attributes.
     *
     * @return \App\Models\User|null
     */
    private function getAuthenticatedUser()
    {
        return $this->getModelFromRequest('authenticated_user', \App\Models\User::class);
    }

    public function getCurrentOwnerId()
    {
        return $this->getAuthenticatedUser()?->owner_association_id;
    }

    public function getCurrentUserId()
    {
        return $this->getAuthenticatedUser()?->id;
    }

    public function getCreatorId()
    {
        return $this->getAuthenticatedUser()?->creatorId();
    }

    public function hasGlobalAccess()
    {
        return $this->getAuthenticatedUser()?->hasGlobalAccess() ?? false;
    }
}
