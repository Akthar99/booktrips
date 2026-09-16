<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /**
     * Determine whether the partner can update their own business profile.
     */
    public function update(User $user, Business $business): bool
    {
        return $business->user_id === $user->id;
    }

    /**
     * Approving partner requests is reserved for super admins.
     */
    public function approve(User $user, Business $business): bool
    {
        return false;
    }
}
