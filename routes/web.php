<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminClaimController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDataTableController;
use App\Http\Controllers\Admin\AdminDisposalController;
use App\Http\Controllers\Admin\AdminFoundReportController;
use App\Http\Controllers\Admin\AdminRecoveryController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSystemHealthController;
use App\Http\Controllers\Admin\BarangayController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ImageReviewController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MatchReviewController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Api\ImagePrecheckController;
use App\Http\Controllers\Api\Select2LookupController;
use App\Http\Controllers\Api\TranslationBatchController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\InvitationAcceptanceController;
use App\Http\Controllers\MilestoneStubController;
use App\Http\Controllers\Public\FoundReportController;
use App\Http\Controllers\Public\LostReportController;
use App\Http\Controllers\Public\MyReportsController;
use App\Http\Controllers\Public\NotificationController;
use App\Http\Controllers\Public\PublicClaimController;
use App\Http\Controllers\Public\PublicItemController;
use App\Http\Controllers\Public\SmartSearchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/help', 'public.help')->name('public.help');

Route::get('/items', [PublicItemController::class, 'index'])->name('public.items.index');
Route::post('/items/visual-search', [PublicItemController::class, 'visualSearch'])
    ->middleware('throttle:15,1')
    ->name('public.items.visual_search');
Route::get('/items/{referenceNo}', [PublicItemController::class, 'show'])->name('public.items.show');
Route::get('/items/{referenceNo}/fb-poster', [PublicItemController::class, 'fbPoster'])->name('public.items.fb_poster');
Route::get('/items/{referenceNo}/images/{image}', [PublicItemController::class, 'showImage'])->name('public.items.image');

Route::get('/api/search', [PublicItemController::class, 'apiSearch'])
    ->middleware('throttle:60,1')
    ->name('api.search');

Route::post('/api/smart-search', SmartSearchController::class)
    ->middleware('throttle:30,1')
    ->name('api.smart_search');

Route::post('/api/images/precheck', ImagePrecheckController::class)
    ->middleware('throttle:30,1')
    ->name('api.images.precheck');

Route::post('/api/translate/batch', TranslationBatchController::class)
    ->middleware('throttle:120,1')
    ->name('api.translate.batch');

Route::prefix('api/select2')->middleware('throttle:60,1')->name('api.select2.')->group(function (): void {
    Route::get('/categories', [Select2LookupController::class, 'categories'])->name('categories');
    Route::get('/barangays', [Select2LookupController::class, 'barangays'])->name('barangays');
    Route::get('/locations', [Select2LookupController::class, 'locations'])->name('locations');
    Route::get('/organizations', [Select2LookupController::class, 'organizations'])->name('organizations');
});

Route::get('/invitations/{token}', [InvitationAcceptanceController::class, 'accept'])
    ->middleware('throttle:20,1')
    ->name('invitations.accept');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [GoogleAuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('auth.google.callback');
});

Route::post('/logout', [GoogleAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/report-lost', [LostReportController::class, 'create'])->name('reports.lost.create');
    Route::post('/report-lost/precheck', [LostReportController::class, 'precheck'])
        ->middleware('throttle:20,1')
        ->name('reports.lost.precheck');
    Route::post('/report-lost', [LostReportController::class, 'store'])
        ->middleware('throttle:15,1')
        ->name('reports.lost.store');

    Route::get('/report-found', [FoundReportController::class, 'create'])->name('reports.found.create');
    Route::post('/report-found/precheck', [FoundReportController::class, 'precheck'])
        ->middleware('throttle:20,1')
        ->name('reports.found.precheck');
    Route::post('/report-found', [FoundReportController::class, 'store'])
        ->middleware('throttle:15,1')
        ->name('reports.found.store');

    Route::get('/my-reports', [MyReportsController::class, 'index'])->name('reports.mine');
    Route::get('/my-reports/{report}', [MyReportsController::class, 'show'])->name('reports.show_own');
    Route::get('/my-reports/{report}/reference-slip', [MyReportsController::class, 'referenceSlip'])->name('reports.reference_slip_own');
    Route::get('/my-reports/{report}/images/{image}', [MyReportsController::class, 'showImage'])->name('reports.image_own');
    Route::post('/my-reports/{report}/images/{image}/replace', [MyReportsController::class, 'replaceImage'])
        ->middleware('throttle:15,1')
        ->name('reports.image_replace');
    Route::post('/my-reports/{report}/withdraw', [MyReportsController::class, 'withdraw'])->name('reports.withdraw');

    Route::get('/items/{referenceNo}/claim', [PublicClaimController::class, 'create'])->name('claims.create');
    Route::post('/items/{referenceNo}/claim', [PublicClaimController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('claims.store');
    Route::get('/my-claims', [PublicClaimController::class, 'mine'])->name('claims.mine');
    Route::get('/my-claims/{claim}', [PublicClaimController::class, 'show'])->name('claims.show_own');
    Route::get('/my-claims/{claim}/evidence/{evidence}', [PublicClaimController::class, 'downloadEvidence'])->name('claims.evidence_own');
    Route::post('/my-claims/{claim}/respond', [PublicClaimController::class, 'respond'])->name('claims.respond');
    Route::post('/my-claims/{claim}/withdraw', [PublicClaimController::class, 'withdraw'])->name('claims.withdraw');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
});

Route::middleware(['auth', 'institutional'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::middleware('permission:report.view_org')->group(function (): void {
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/return-all-booth-to-station', [AdminReportController::class, 'returnAllBoothToStation'])->name('reports.return_all_booth_to_station');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/verify', [AdminReportController::class, 'verify'])->name('reports.verify');
        Route::patch('/reports/{report}/reject', [AdminReportController::class, 'reject'])->name('reports.reject');
        Route::patch('/reports/{report}/request-revision', [AdminReportController::class, 'requestRevision'])->name('reports.request_revision');
        Route::patch('/reports/{report}/confirm-turnover', [AdminReportController::class, 'confirmTurnover'])->name('reports.confirm_turnover');
        Route::patch('/reports/{report}/return-to-station', [AdminReportController::class, 'returnToStation'])->name('reports.return_to_station');
        Route::patch('/reports/{report}/schedule-booth-handoff', [AdminReportController::class, 'scheduleBoothHandoff'])->name('reports.schedule_booth_handoff');
        Route::post('/reports/{report}/notify-finder', [AdminReportController::class, 'notifyFinder'])->name('reports.notify_finder');
        Route::patch('/reports/{report}/transfer-custody', [AdminReportController::class, 'transferCustody'])->name('reports.transfer_custody');
        Route::get('/reports/{report}/reference-slip', [AdminReportController::class, 'referenceSlip'])->name('reports.reference_slip');
        Route::get('/reports/{report}/fb-poster', [AdminReportController::class, 'fbPoster'])->name('reports.fb_poster');
        Route::post('/reports/{report}/scan-matches', [AdminReportController::class, 'scanMatches'])->name('reports.scan_matches');
    });

    Route::middleware('permission:report.encode_on_behalf')->group(function (): void {
        Route::get('/found-items/create', [AdminFoundReportController::class, 'create'])->name('found.create');
        Route::post('/found-items/precheck', [AdminFoundReportController::class, 'precheck'])->name('found.precheck');
        Route::post('/found-items', [AdminFoundReportController::class, 'store'])->name('found.store');
    });

    Route::middleware('permission:image.review')->group(function (): void {
        Route::get('/image-reviews', [ImageReviewController::class, 'index'])->name('images.reviews');
        Route::get('/image-reviews/{image}/preview', [ImageReviewController::class, 'preview'])->name('images.preview');
        Route::patch('/image-reviews/{image}', [ImageReviewController::class, 'decide'])->name('images.decide');
    });

    Route::middleware('permission:match.view')->group(function (): void {
        Route::get('/matches', [MilestoneStubController::class, 'upcoming'])->name('matches.index');
        Route::post('/matches/scan', [MatchReviewController::class, 'scan'])->name('matches.scan');
        Route::patch('/matches/{match}', [MatchReviewController::class, 'decide'])->name('matches.decide');
    });

    Route::middleware('permission:claim.view_org')->group(function (): void {
        Route::get('/claims', [AdminClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [AdminClaimController::class, 'show'])->name('claims.show');
        Route::get('/claims/{claim}/evidence/{evidence}', [AdminClaimController::class, 'downloadEvidence'])->name('claims.evidence');
        Route::patch('/claims/{claim}/request-info', [AdminClaimController::class, 'requestInfo'])->name('claims.request_info');
        Route::patch('/claims/{claim}/approve', [AdminClaimController::class, 'approve'])->name('claims.approve');
        Route::patch('/claims/{claim}/reject', [AdminClaimController::class, 'reject'])->name('claims.reject');
        Route::post('/claims/{claim}/recovery', [AdminRecoveryController::class, 'store'])->name('claims.recovery.store');
    });

    Route::middleware('permission:recovery.record')->group(function (): void {
        Route::get('/recoveries', [AdminRecoveryController::class, 'index'])->name('recoveries.index');
        Route::patch('/recoveries/{recovery}/confirm', [AdminRecoveryController::class, 'confirm'])->name('recoveries.confirm');
        Route::get('/recoveries/{recovery}/receipt-pdf', [AdminRecoveryController::class, 'receiptPdf'])->name('recoveries.receipt_pdf');
    });

    Route::middleware('permission:disposal.view')->group(function (): void {
        Route::get('/disposals', [MilestoneStubController::class, 'upcoming'])->name('disposals.index');
        Route::post('/disposals/scan', [AdminDisposalController::class, 'scan'])->name('disposals.scan');
        Route::get('/disposals/{disposal}', [AdminDisposalController::class, 'show'])->name('disposals.show');
        Route::get('/disposals/{disposal}/documents/{document}', [AdminDisposalController::class, 'downloadDocument'])->name('disposals.document');
        Route::patch('/disposals/{disposal}/propose', [AdminDisposalController::class, 'propose'])->name('disposals.propose');
        Route::patch('/disposals/{disposal}/approve', [AdminDisposalController::class, 'approve'])->name('disposals.approve');
        Route::post('/disposals/{disposal}/complete', [AdminDisposalController::class, 'complete'])->name('disposals.complete');
        Route::patch('/disposals/{disposal}/cancel', [AdminDisposalController::class, 'cancel'])->name('disposals.cancel');
    });

    Route::middleware('permission:analytics.view')->group(function (): void {
        Route::get('/analytics', [MilestoneStubController::class, 'upcoming'])->name('analytics.index');
        Route::get('/analytics/export/csv', [AdminAnalyticsController::class, 'exportCsv'])->name('analytics.export.csv');
        Route::get('/analytics/export/pdf', [AdminAnalyticsController::class, 'exportPdf'])->name('analytics.export.pdf');
        Route::get('/audit-logs', [MilestoneStubController::class, 'upcoming'])->name('audit_logs.index');
    });

    Route::prefix('datatables')->name('datatables.')->group(function (): void {
        Route::get('/reports', [AdminDataTableController::class, 'reports'])->middleware('permission:report.view_org')->name('reports');
        Route::get('/matches', [AdminDataTableController::class, 'matches'])->middleware('permission:match.view')->name('matches');
        Route::get('/claims', [AdminDataTableController::class, 'claims'])->middleware('permission:claim.view_org')->name('claims');
        Route::get('/recoveries', [AdminDataTableController::class, 'recoveries'])->middleware('permission:recovery.record')->name('recoveries');
        Route::get('/disposals', [AdminDataTableController::class, 'disposals'])->middleware('permission:disposal.view')->name('disposals');
        Route::get('/audit-logs', [AdminDataTableController::class, 'auditLogs'])->middleware('permission:analytics.view')->name('audit_logs');
        Route::get('/organizations', [AdminDataTableController::class, 'organizations'])->middleware('permission:organization.manage')->name('organizations');
        Route::get('/categories', [AdminDataTableController::class, 'categories'])->middleware('permission:masterdata.manage')->name('categories');
        Route::get('/barangays', [AdminDataTableController::class, 'barangays'])->middleware('permission:masterdata.manage')->name('barangays');
        Route::get('/locations', [AdminDataTableController::class, 'locations'])->middleware('permission:location.manage')->name('locations');
        Route::get('/memberships', [AdminDataTableController::class, 'memberships'])->middleware('permission:staff.manage')->name('memberships');
        Route::get('/invitations', [AdminDataTableController::class, 'invitations'])->middleware('permission:staff.invite')->name('invitations');
    });

    Route::middleware('permission:organization.manage')->group(function (): void {
        Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    });

    Route::middleware('permission:masterdata.manage')->group(function (): void {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');

        Route::get('/barangays', [BarangayController::class, 'index'])->name('barangays.index');
        Route::post('/barangays', [BarangayController::class, 'store'])->name('barangays.store');
        Route::put('/barangays/{barangay}', [BarangayController::class, 'update'])->name('barangays.update');
    });

    Route::middleware('permission:location.manage')->group(function (): void {
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    });

    Route::middleware('permission:settings.manage')->group(function (): void {
        Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
        Route::put('/settings/{setting}', [SystemSettingController::class, 'update'])->name('settings.update');
        Route::get('/system-health', [MilestoneStubController::class, 'upcoming'])->name('system_health.index');
        Route::post('/system-health/backup', [AdminSystemHealthController::class, 'runBackup'])->name('system_health.backup');
        Route::get('/system-health/backup/{filename}', [AdminSystemHealthController::class, 'downloadBackup'])->name('system_health.backup.download');
    });

    Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');
    Route::patch('/memberships/{membership}', [MembershipController::class, 'update'])->name('memberships.update');

    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'revoke'])->name('invitations.revoke');
});
