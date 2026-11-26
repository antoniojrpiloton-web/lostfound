<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('location.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('location.manage');
    }

    public function update(User $user, Location $location): bool
    {
        if (! $user->hasPermission('location.manage')) {
            return false;
        }

        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $location->organization_id !== null && $user->belongsToOrganization($location->organization_id);
    }
}
