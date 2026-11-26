<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(public AnalyticsService $analytics) {}

    public function index(Request $request): View
    {

        $user = $request->user();
        $membership = $user->activeInstitutionalMembership();
        $isSuperAdmin = $user->isPlatformSuperAdmin();
        $orgId = $user->organizationId();

        $membershipsQuery = OrganizationMembership::query();
        $invitationsQuery = OrganizationInvitation::query()->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now());
        $auditQuery = AuditLog::query()->with('user')->latest('created_at');

        if (! $isSuperAdmin && $orgId !== null) {
            $membershipsQuery->where('organization_id', $orgId);
            $invitationsQuery->where('organization_id', $orgId);
            $auditQuery->where('organization_id', $orgId);
        }

        $metrics = $this->analytics->summary($user);
        $monthlyTrend = $this->analytics->monthlyTrend($user, 6);
        $barangayBreakdown = $this->analytics->barangayBreakdown($user);

        $stats = array_merge($metrics, [
            'organizations' => $isSuperAdmin ? Organization::query()->where('is_active', true)->count() : 1,
            'memberships' => $membershipsQuery->count(),
            'pending_invitations' => $invitationsQuery->count(),
        ]);

        $recentActivity = $auditQuery->limit(6)->get();

        return view('admin.dashboard', [
            'user' => $user,
            'membership' => $membership,
            'stats' => $stats,
            'monthlyTrend' => $monthlyTrend,
            'barangayBreakdown' => $barangayBreakdown,
            'recentActivity' => $recentActivity,
        ]);
    }
}
