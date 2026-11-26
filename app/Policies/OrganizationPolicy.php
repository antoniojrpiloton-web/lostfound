<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('organization.manage');
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->belongsToOrganization($organization->id);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('organization.manage');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('organization.manage');
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('organization.manage');
    }
}
