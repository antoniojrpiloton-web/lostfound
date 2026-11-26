<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {

        $user = $request->user();

        $query = AuditLog::query()
            ->with(['user', 'organization'])
            ->latest('created_at');

        if (! $user->isPlatformSuperAdmin()) {
            $query->where('organization_id', $user->organizationId());
        }

        if ($request->filled('action')) {
            $query->where('action', (string) $request->string('action'));
        }

        $logs = $query->paginate(20)->withQueryString();

        $actionsQuery = AuditLog::query()->select('action')->distinct()->orderBy('action');
        if (! $user->isPlatformSuperAdmin()) {
            $actionsQuery->where('organization_id', $user->organizationId());
        }
        $actions = $actionsQuery->pluck('action')->all();

        return view('admin.audit-logs.index', compact('logs', 'actions'));
    }
}
