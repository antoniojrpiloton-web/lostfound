<?php

namespace App\Policies;

use App\Models\ItemMatch;
use App\Models\User;

class ItemMatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('match.view');
    }

    public function review(User $user, ItemMatch $match): bool
    {
        if (! $user->hasPermission('match.review')) {
            return false;
        }

        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        $orgId = $user->organizationId();
        if ($orgId === null) {
            return false;
        }

        $match->loadMissing(['lostReport', 'foundReport']);

        return in_array($orgId, [
            $match->lostReport?->handling_organization_id,
            $match->foundReport?->handling_organization_id,
            $match->foundReport?->custody_organization_id,
        ], true);
    }
}
