<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('masterdata.manage');
    }

    public function create(User $user): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('masterdata.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('masterdata.manage');
    }
}
