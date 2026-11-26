<?php

namespace App\Policies;

use App\Models\Barangay;
use App\Models\User;

class BarangayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('masterdata.manage');
    }

    public function create(User $user): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('masterdata.manage');
    }

    public function update(User $user, Barangay $barangay): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('masterdata.manage');
    }
}
