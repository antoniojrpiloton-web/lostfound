<?php

namespace App\Policies;

use App\Models\OrganizationMembership;
use App\Models\SystemSetting;
use App\Models\User;

class OrganizationMembershipPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.manage') && $this->institutionAdminsCanManageStaff();
    }

    public function view(User $user, OrganizationMembership $membership): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.manage')
            && $this->institutionAdminsCanManageStaff()
            && $membership->organization_id !== null
            && $user->belongsToOrganization($membership->organization_id);
    }

    public function update(User $user, OrganizationMembership $membership): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('staff.manage') || ! $this->institutionAdminsCanManageStaff()) {
            return false;
        }

        if ($membership->organization_id === null || ! $user->belongsToOrganization($membership->organization_id)) {
            return false;
        }

        if ($membership->user_id === $user->id) {
            return false;
        }

        return $membership->role?->key === 'institution_staff';
    }

    protected function institutionAdminsCanManageStaff(): bool
    {
        $setting = SystemSetting::query()->where('key', 'institutions.admins_can_manage_staff')->first();

        return (bool) ($setting?->value['value'] ?? true);
    }
}
