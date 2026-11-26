<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Organization;
use App\Services\Analytics\AnalyticsService;
use App\Services\Reports\OfficialPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAnalyticsController extends Controller
{
    public function __construct(
        public AnalyticsService $analytics,
        public OfficialPdfService $pdfService,
    ) {}

    public function index(Request $request): View
    {

        $user = $request->user();
        $filters = $request->only(['date_from', 'date_to', 'barangay_id', 'organization_id']);

        $summary = $this->analytics->summary($user, $filters);
        $monthlyTrend = $this->analytics->monthlyTrend($user, 12, $filters);
        $barangayBreakdown = $this->analytics->barangayBreakdown($user, $filters);
        $institutionBreakdown = $this->analytics->institutionBreakdown($user, $filters);

        $barangays = Barangay::query()->where('is_active', true)->orderBy('name')->get();
        $organizations = $user->isPlatformSuperAdmin()
            ? Organization::query()->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('admin.analytics.index', compact(
            'summary',
            'monthlyTrend',
            'barangayBreakdown',
            'institutionBreakdown',
            'filters',
            'barangays',
            'organizations',
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {

        $user = $request->user();
        abort_unless($user->hasPermission('export.generate'), 403);

        $filters = $request->only(['date_from', 'date_to', 'barangay_id', 'organization_id']);
        $rows = $this->analytics->exportRows($user, $filters);
        $filename = 'boracay-lf-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Reference No',
                'Type',
                'Item Name',
                'Category',
                'Barangay',
                'Location',
                'Occurred Date',
                'Verification Status',
                'Item Status',
                'Handling Institution',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->reference_no,
                    $row->type->value,
                    $row->item_name,
                    $row->category?->label() ?? '',
                    $row->barangay?->name ?? '',
                    $row->location?->name ?? '',
                    $row->occurred_date?->toDateString() ?? '',
                    $row->verification_status->value,
                    $row->item_status->value,
                    $row->custodyOrganization?->name ?? $row->handlingOrganization?->name ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {

        $user = $request->user();
        abort_unless($user->hasPermission('export.generate'), 403);

        $filters = $request->only(['date_from', 'date_to', 'barangay_id', 'organization_id']);
        $summary = $this->analytics->summary($user, $filters);
        $barangayBreakdown = $this->analytics->barangayBreakdown($user, $filters);
        $institutionBreakdown = $this->analytics->institutionBreakdown($user, $filters);
        $rows = $this->analytics->exportRows($user, $filters)->take(200);

        $bytes = $this->pdfService->buildAnalyticsReport(
            user: $user,
            filters: $filters,
            summary: $summary,
            barangayBreakdown: $barangayBreakdown,
            institutionBreakdown: $institutionBreakdown,
            rows: $rows,
        );

        $filename = 'boracay-lf-analytics-'.now()->format('Ymd-His').'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
