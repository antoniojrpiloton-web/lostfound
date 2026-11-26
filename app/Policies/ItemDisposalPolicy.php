<?php

namespace App\Policies;

use App\Models\ItemDisposal;
use App\Models\User;

class ItemDisposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('disposal.view');
    }

    public function view(User $user, ItemDisposal $disposal): bool
    {
        return $user->hasPermission('disposal.view') && $this->inScope($user, $disposal);
    }

    public function propose(User $user, ItemDisposal $disposal): bool
    {
        return $user->hasPermission('disposal.propose') && $this->inScope($user, $disposal);
    }

    public function approve(User $user, ItemDisposal $disposal): bool
    {
        return $user->hasPermission('disposal.approve') && $this->inScope($user, $disposal);
    }

    public function complete(User $user, ItemDisposal $disposal): bool
    {
        return $user->hasPermission('disposal.approve') && $this->inScope($user, $disposal);
    }

    public function cancel(User $user, ItemDisposal $disposal): bool
    {
        return $user->hasPermission('disposal.approve') && $this->inScope($user, $disposal);
    }

    protected function inScope(User $user, ItemDisposal $disposal): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->organizationId() !== null
            && $user->organizationId() === $disposal->organization_id;
    }
}
