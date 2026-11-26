<?php

namespace App\Policies;

use App\Models\OrganizationInvitation;
use App\Models\SystemSetting;
use App\Models\User;

class OrganizationInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.invite') && $this->institutionAdminsCanManageStaff();
    }

    public function create(User $user): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.invite') && $this->institutionAdminsCanManageStaff();
    }

    public function revoke(User $user, OrganizationInvitation $invitation): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.invite')
            && $this->institutionAdminsCanManageStaff()
            && $invitation->organization_id !== null
            && $user->belongsToOrganization($invitation->organization_id);
    }

    protected function institutionAdminsCanManageStaff(): bool
    {
        $setting = SystemSetting::query()->where('key', 'institutions.admins_can_manage_staff')->first();

        return (bool) ($setting?->value['value'] ?? true);
    }
}
