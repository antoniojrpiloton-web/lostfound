<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('settings.manage');
    }

    public function update(User $user, SystemSetting $setting): bool
    {
        return $user->isPlatformSuperAdmin() && $user->hasPermission('settings.manage');
    }
}
