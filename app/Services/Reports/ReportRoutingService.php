<?php

namespace App\Services\Reports;

use App\Models\Location;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class ReportRoutingService
{
    public function resolveHandlingOrganizationId(?int $barangayId, ?int $locationId = null): ?int
    {
        if ($locationId !== null) {
            $locationOrgId = Location::query()
                ->whereKey($locationId)
                ->value('organization_id');

            if ($locationOrgId !== null) {
                return (int) $locationOrgId;
            }
        }

        if ($barangayId !== null) {
            $defaultOrgId = DB::table('organization_barangay')
                ->where('barangay_id', $barangayId)
                ->orderByDesc('is_default')
                ->value('organization_id');

            if ($defaultOrgId !== null) {
                return (int) $defaultOrgId;
            }
        }

        $fallbackOrgId = Organization::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $fallbackOrgId !== null ? (int) $fallbackOrgId : null;
    }
}
