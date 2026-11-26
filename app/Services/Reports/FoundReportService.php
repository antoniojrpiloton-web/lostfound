<?php

namespace App\Services\Reports;

use App\Enums\CustodyAction;
use App\Enums\CustodyStage;
use App\Enums\ImageValidationStatus;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\VerificationStatus;
use App\Jobs\MatchReportJob;
use App\Models\ItemCustodyRecord;
use App\Models\ItemReport;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoundReportService
{
    public function __construct(
        public ReferenceNumberService $referenceNumbers,
        public ReportRoutingService $routingService,
        public SearchTextBuilder $searchTextBuilder,
        public ReportImageService $imageService,
        public AuditService $auditService,
        public NotificationDispatcher $notifications,
    ) {}

    public function submit(
        User $actor,
        array $data,
        array $photos = [],
        bool $inInstitutionalCustody = false,
        ?Request $request = null,
    ): ItemReport {
        return DB::transaction(function () use ($actor, $data, $photos, $inInstitutionalCustody, $request): ItemReport {
            $barangayId = ! empty($data['barangay_id']) ? (int) $data['barangay_id'] : null;
            $locationId = ! empty($data['location_id']) ? (int) $data['location_id'] : null;

            $custodyOrgId = ! empty($data['custody_organization_id'])
                ? (int) $data['custody_organization_id']
                : ($inInstitutionalCustody ? $actor->organizationId() : null);

            $handlingOrgId = $custodyOrgId
                ?? $this->routingService->resolveHandlingOrganizationId($barangayId, $locationId);

            $custodyOrgId ??= $handlingOrgId;
            $referenceNo = $this->referenceNumbers->next('BLF-F');
            $initialItemStatus = $inInstitutionalCustody ? ItemStatus::InCustody : ItemStatus::AwaitingTurnover;
            $boothLocation = ! empty($data['booth_location']) ? trim((string) $data['booth_location']) : null;
            $initialCustodyStage = ! $inInstitutionalCustody
                ? CustodyStage::WithFinder
                : (CustodyStage::tryFrom((string) ($data['custody_stage'] ?? ''))
                    ?? ($boothLocation !== null ? CustodyStage::AtBooth : CustodyStage::AtStation));

            $photoExceptionReason = (count($photos) === 0 && ! empty($data['photo_exception_reason']))
                ? (string) $data['photo_exception_reason']
                : null;

            $report = ItemReport::query()->create([
                'reference_no' => $referenceNo,
                'type' => ReportType::Found,
                'reporter_user_id' => $inInstitutionalCustody ? null : $actor->id,
                'encoded_by' => $actor->id,
                'handling_organization_id' => $handlingOrgId,
                'custody_organization_id' => $custodyOrgId,
                'category_id' => (int) $data['category_id'],
                'item_name' => $data['item_name'],
                'description' => $data['description'],
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'color' => $data['color'] ?? null,
                'distinguishing_features' => $data['distinguishing_features'] ?? null,
                'occurred_date' => $data['occurred_date'],
                'occurred_time' => $data['occurred_time'] ?? null,
                'barangay_id' => $barangayId,
                'location_id' => $locationId,
                'location_details' => $data['location_details'] ?? null,
                'finder_name' => $data['finder_name'] ?? $actor->name,
                'finder_contact_enc' => ! empty($data['finder_contact']) ? $data['finder_contact'] : null,
                'remarks' => $data['remarks'] ?? null,
                'verification_status' => VerificationStatus::Pending,
                'item_status' => $initialItemStatus,
                'custody_stage' => $initialCustodyStage,
                'booth_location' => $boothLocation,
                'station_transferred_at' => $initialCustodyStage === CustodyStage::AtStation ? now() : null,
                'is_confidential' => false,
                'is_public' => false,
                'photo_exception_reason' => $photoExceptionReason,
                'photo_exception_by' => $photoExceptionReason !== null ? $actor->id : null,
                'received_at' => $inInstitutionalCustody ? now() : null,
                'source_locale' => app()->getLocale(),
            ]);

            $report->forceFill([
                'search_text' => $this->searchTextBuilder->build($report),
            ])->save();

            $storedImages = [];
            foreach (array_values($photos) as $index => $photo) {
                $storedImages[] = $this->imageService->storeForReport($report, $photo, isPrimary: $index === 0);
            }

            $allImagesPassed = $storedImages !== []
                && collect($storedImages)->every(fn ($img): bool => $img->validation_status === ImageValidationStatus::Passed);

            $shouldAutoVerify = $inInstitutionalCustody
                && $actor->hasPermission('report.verify')
                && $allImagesPassed;

            if ($inInstitutionalCustody && $custodyOrgId !== null) {
                ItemCustodyRecord::query()->create([
                    'item_report_id' => $report->id,
                    'organization_id' => $custodyOrgId,
                    'action' => CustodyAction::Received,
                    'received_from' => $report->finder_name,
                    'recorded_by' => $actor->id,
                    'recorded_at' => now(),
                    'remarks' => __('Physical item received at institutional desk during registration.'),
                ]);
            }

            if ($shouldAutoVerify) {
                $report->forceFill([
                    'verification_status' => VerificationStatus::Verified,
                    'is_public' => true,
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ])->save();

                foreach ($storedImages as $storedImage) {
                    $this->imageService->generatePublicDerivative($storedImage);
                }

                ReportStatusHistory::query()->create([
                    'item_report_id' => $report->id,
                    'dimension' => StatusDimension::Verification,
                    'from_status' => VerificationStatus::Pending->value,
                    'to_status' => VerificationStatus::Verified->value,
                    'changed_by' => $actor->id,
                    'remarks' => __('Auto-verified at desk custody registration (all uploaded photos passed clarity checks).'),
                ]);

                MatchReportJob::dispatch($report->id);
            } else {
                ReportStatusHistory::query()->create([
                    'item_report_id' => $report->id,
                    'dimension' => StatusDimension::Verification,
                    'from_status' => null,
                    'to_status' => VerificationStatus::Pending->value,
                    'changed_by' => $actor->id,
                    'remarks' => __('Found item report registered for verification.'),
                ]);
            }

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Item,
                'from_status' => null,
                'to_status' => $initialItemStatus->value,
                'changed_by' => $actor->id,
                'remarks' => $inInstitutionalCustody
                    ? __('Item received into institutional custody.')
                    : __('Awaiting physical turnover to custody institution.'),
            ]);

            $this->auditService->record(
                action: 'report.found_submitted',
                auditable: $report,
                newValues: [
                    'reference_no' => $report->reference_no,
                    'custody_organization_id' => $custodyOrgId,
                    'item_status' => $initialItemStatus->value,
                    'photo_count' => count($photos),
                    'photo_exception' => $photoExceptionReason !== null,
                ],
                actor: $actor,
                organizationId: $custodyOrgId,
                request: $request,
            );

            if ($report->reporter_user_id !== null) {
                $this->notifications->send(
                    recipient: $actor,
                    eventKey: 'report.submitted',
                    title: __('Found Report Registered (:ref)', ['ref' => $report->reference_no]),
                    body: __('Thank you for reporting ":item". Please surrender the item to the selected institution to confirm custody.', ['item' => $report->item_name]),
                    actionUrl: route('reports.show_own', $report),
                    meta: ['report_id' => $report->id, 'reference_no' => $report->reference_no],
                );
            }

            return $report;
        });
    }
}
