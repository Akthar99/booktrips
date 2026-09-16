<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    /**
     * Determine whether the partner owns the package.
     */
    public function update(User $user, Package $package): bool
    {
        return $user->business?->id === $package->business_id;
    }

    /**
     * Determine whether the partner may deactivate the package.
     */
    public function delete(User $user, Package $package): bool
    {
        return $user->business?->id === $package->business_id;
    }
}
