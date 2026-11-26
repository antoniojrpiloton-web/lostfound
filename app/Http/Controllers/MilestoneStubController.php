<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MilestoneStubController extends Controller
{
    public function upcoming(Request $request): View
    {
        $routeName = (string) $request->route()?->getName();
        $path = $request->path();

        $title = 'System Module';
        $description = 'This module is currently under active development and will be enabled in the next scheduled release.';

        if (str_contains($routeName, 'matches') || str_contains($path, 'matches')) {
            $title = 'Item Matching Queue';
            $description = 'Automated semantic visual matching, candidate similarity scoring, and side-by-side match verification are currently in development.';
        } elseif (str_contains($routeName, 'disposal') || str_contains($path, 'disposal')) {
            $title = 'Disposal Monitoring';
            $description = 'Custody retention tracking, overdue item flagging, and documented disposal workflows are currently in development.';
        } elseif (str_contains($routeName, 'analytics') || str_contains($path, 'analytics')) {
            $title = 'Analytics & Reports';
            $description = 'Institutional recovery analytics, trend charts, and official PDF/CSV report exports are currently in development.';
        } elseif (str_contains($routeName, 'audit_logs') || str_contains($path, 'audit-logs')) {
            $title = 'Audit Logs';
            $description = 'System-wide administrative audit trails and record diff inspection tools are currently in development.';
        } elseif (str_contains($routeName, 'system_health') || str_contains($path, 'system-health')) {
            $title = 'Health & Backups';
            $description = 'Service diagnostics, visual index coverage monitoring, and automated database backup tools are currently in development.';
        }

        return view('errors.in-development', compact('title', 'description'));
    }
}
