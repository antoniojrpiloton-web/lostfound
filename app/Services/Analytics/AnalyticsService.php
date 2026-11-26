<?php

namespace App\Services\Analytics;

use App\Enums\ClaimStatus;
use App\Enums\DisposalStatus;
use App\Enums\ItemStatus;
use App\Enums\MatchStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Claim;
use App\Models\ItemDisposal;
use App\Models\ItemMatch;
use App\Models\ItemRecovery;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function summary(User $user, array $filters = []): array
    {
        $reports = $this->scopedReports($user, $filters);

        $orgId = $this->effectiveOrganizationId($user, $filters);

        $matchesQuery = ItemMatch::query()->whereIn('status', [MatchStatus::Suggested->value, MatchStatus::Possible->value]);
        $claimsQuery = Claim::query();
        $recoveriesQuery = ItemRecovery::query()->whereNotNull('confirmed_by');
        $disposalsQuery = ItemDisposal::query();

        if ($orgId !== null) {
            $matchesQuery->where(function (Builder $query) use ($orgId): void {
                $query->whereHas('lostReport', fn (Builder $q) => $q->where('handling_organization_id', $orgId)->orWhere('custody_organization_id', $orgId))
                    ->orWhereHas('foundReport', fn (Builder $q) => $q->where('handling_organization_id', $orgId)->orWhere('custody_organization_id', $orgId));
            });

            $claimsQuery->whereHas('itemReport', fn (Builder $q) => $q->where('handling_organization_id', $orgId)->orWhere('custody_organization_id', $orgId));
            $recoveriesQuery->where('organization_id', $orgId);
            $disposalsQuery->where('organization_id', $orgId);
        }

        if (! empty($filters['date_from'])) {
            $claimsQuery->whereDate('created_at', '>=', (string) $filters['date_from']);
            $recoveriesQuery->whereDate('turnover_at', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $claimsQuery->whereDate('created_at', '<=', (string) $filters['date_to']);
            $recoveriesQuery->whereDate('turnover_at', '<=', (string) $filters['date_to']);
        }

        return [
            'total_lost' => (clone $reports)->where('type', ReportType::Lost->value)->count(),
            'total_found' => (clone $reports)->where('type', ReportType::Found->value)->count(),
            'pending_verification' => (clone $reports)->where('verification_status', VerificationStatus::Pending->value)->count(),
            'verified' => (clone $reports)->where('verification_status', VerificationStatus::Verified->value)->count(),
            'rejected' => (clone $reports)->where('verification_status', VerificationStatus::Rejected->value)->count(),
            'possible_matches' => $matchesQuery->count(),
            'pending_claims' => (clone $claimsQuery)->whereIn('status', [
                ClaimStatus::Submitted->value,
                ClaimStatus::UnderReview->value,
                ClaimStatus::InfoRequested->value,
            ])->count(),
            'claimed' => (clone $claimsQuery)->where('status', ClaimStatus::Approved->value)->count(),
            'recovered' => $recoveriesQuery->count(),
            'unclaimed' => (clone $reports)
                ->where('type', ReportType::Found->value)
                ->where('verification_status', VerificationStatus::Verified->value)
                ->whereIn('item_status', [ItemStatus::AwaitingTurnover->value, ItemStatus::InCustody->value])
                ->count(),
            'disposal_eligible' => (clone $disposalsQuery)->whereIn('status', [
                DisposalStatus::Eligible->value,
                DisposalStatus::Proposed->value,
                DisposalStatus::Approved->value,
            ])->count(),
            'disposed' => (clone $reports)->where('item_status', ItemStatus::Disposed->value)->count(),
        ];
    }

    public function monthlyTrend(User $user, int $months = 6, array $filters = []): array
    {
        $labels = [];
        $lost = [];
        $found = [];
        $recovered = [];

        $orgId = $this->effectiveOrganizationId($user, $filters);

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = Carbon::now()->startOfMonth()->subMonths($i);
            $end = (clone $start)->endOfMonth();

            $labels[] = $start->format('M Y');

            $monthReports = $this->scopedReports($user, $filters)
                ->whereBetween('occurred_date', [$start->toDateString(), $end->toDateString()]);

            $lost[] = (clone $monthReports)->where('type', ReportType::Lost->value)->count();
            $found[] = (clone $monthReports)->where('type', ReportType::Found->value)->count();

            $recQuery = ItemRecovery::query()
                ->whereNotNull('confirmed_by')
                ->whereBetween('turnover_at', [$start, $end]);

            if ($orgId !== null) {
                $recQuery->where('organization_id', $orgId);
            }

            $recovered[] = $recQuery->count();
        }

        return [
            'labels' => $labels,
            'lost' => $lost,
            'found' => $found,
            'recovered' => $recovered,
        ];
    }

    public function barangayBreakdown(User $user, array $filters = []): array
    {
        $colors = ['#087EA4', '#0D9488', '#123047'];
        $barangays = Barangay::query()->where('is_active', true)->orderBy('name')->get();

        $rows = [];
        $grandTotal = 0;

        foreach ($barangays as $index => $barangay) {
            $base = $this->scopedReports($user, $filters)->where('barangay_id', $barangay->id);
            $lostCount = (clone $base)->where('type', ReportType::Lost->value)->count();
            $foundCount = (clone $base)->where('type', ReportType::Found->value)->count();
            $total = $lostCount + $foundCount;
            $grandTotal += $total;

            $rows[] = [
                'id' => $barangay->id,
                'name' => $barangay->name,
                'lost' => $lostCount,
                'found' => $foundCount,
                'total' => $total,
                'percent' => 0,
                'color' => $colors[$index % count($colors)],
            ];
        }

        foreach ($rows as &$row) {
            $row['percent'] = $grandTotal > 0 ? (int) round(($row['total'] / $grandTotal) * 100) : 0;
        }
        unset($row);

        return $rows;
    }

    public function institutionBreakdown(User $user, array $filters = []): array
    {
        $orgQuery = Organization::query()->where('is_active', true)->orderBy('name');
        $effectiveOrgId = $this->effectiveOrganizationId($user, $filters);

        if ($effectiveOrgId !== null) {
            $orgQuery->whereKey($effectiveOrgId);
        }

        $result = [];

        foreach ($orgQuery->get() as $org) {
            $orgFilters = array_merge($filters, ['organization_id' => $org->id]);
            $reports = $this->scopedReports($user, $orgFilters);

            $recQuery = ItemRecovery::query()
                ->whereNotNull('confirmed_by')
                ->where('organization_id', $org->id);

            if (! empty($filters['date_from'])) {
                $recQuery->whereDate('turnover_at', '>=', (string) $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $recQuery->whereDate('turnover_at', '<=', (string) $filters['date_to']);
            }

            $result[] = [
                'name' => $org->name,
                'lost' => (clone $reports)->where('type', ReportType::Lost->value)->count(),
                'found' => (clone $reports)->where('type', ReportType::Found->value)->count(),
                'verified' => (clone $reports)->where('verification_status', VerificationStatus::Verified->value)->count(),
                'recovered' => $recQuery->count(),
            ];
        }

        return $result;
    }

    public function exportRows(User $user, array $filters = []): Collection
    {
        return $this->scopedReports($user, $filters)
            ->with(['category', 'barangay', 'location', 'handlingOrganization', 'custodyOrganization'])
            ->latest('occurred_date')
            ->limit(2000)
            ->get();
    }

    protected function scopedReports(User $user, array $filters = []): Builder
    {
        $query = ItemReport::query()->visibleTo($user);

        if ($user->isPlatformSuperAdmin() && ! empty($filters['organization_id'])) {
            $orgId = (int) $filters['organization_id'];
            $query->where(function (Builder $builder) use ($orgId): void {
                $builder->where('handling_organization_id', $orgId)
                    ->orWhere('custody_organization_id', $orgId);
            });
        }

        if (! empty($filters['barangay_id'])) {
            $query->where('barangay_id', (int) $filters['barangay_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('occurred_date', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('occurred_date', '<=', (string) $filters['date_to']);
        }

        return $query;
    }

    protected function effectiveOrganizationId(User $user, array $filters): ?int
    {
        if (! $user->isPlatformSuperAdmin()) {
            return $user->organizationId();
        }

        return ! empty($filters['organization_id']) ? (int) $filters['organization_id'] : null;
    }
}
