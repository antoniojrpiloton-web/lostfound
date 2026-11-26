<?php

namespace App\Policies;

use App\Models\ItemReport;
use App\Models\User;

class ItemReportPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('report.create');
    }

    public function viewOwn(User $user, ItemReport $report): bool
    {
        return $report->reporter_user_id === $user->id;
    }

    public function viewInstitutional(User $user, ItemReport $report): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('report.view_org')) {
            return false;
        }

        $orgId = $user->organizationId();

        return $orgId !== null
            && ($report->handling_organization_id === $orgId || $report->custody_organization_id === $orgId);
    }

    public function verify(User $user, ItemReport $report): bool
    {
        if (! $user->hasPermission('report.verify')) {
            return false;
        }

        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $report->handling_organization_id !== null
            && $user->belongsToOrganization($report->handling_organization_id);
    }
}
