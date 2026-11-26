<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('claim.create');
    }

    public function viewOwn(User $user, Claim $claim): bool
    {
        return $claim->claimant_user_id === $user->id;
    }

    public function viewAnyInstitutional(User $user): bool
    {
        return $user->hasPermission('claim.view_org');
    }

    public function viewInstitutional(User $user, Claim $claim): bool
    {
        if (! $user->hasPermission('claim.view_org')) {
            return false;
        }

        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        $orgId = $user->organizationId();
        $claim->loadMissing('itemReport');

        return $orgId !== null && in_array($orgId, [
            $claim->itemReport?->custody_organization_id,
            $claim->itemReport?->handling_organization_id,
        ], true);
    }

    public function requestInfo(User $user, Claim $claim): bool
    {
        return $user->hasPermission('claim.request_info') && $this->viewInstitutional($user, $claim);
    }

    public function decide(User $user, Claim $claim): bool
    {
        if ($claim->claimant_user_id === $user->id) {
            return false;
        }

        return ($user->hasPermission('claim.approve') || $user->hasPermission('claim.reject'))
            && $this->viewInstitutional($user, $claim);
    }
}
