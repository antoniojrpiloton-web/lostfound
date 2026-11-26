<?php

namespace App\Services\Reports;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\VerificationStatus;
use App\Models\ItemReport;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Matching\ItemMatchingService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LostReportService
{
    public function __construct(
        public ReferenceNumberService $referenceNumbers,
        public ReportRoutingService $routingService,
        public SearchTextBuilder $searchTextBuilder,
        public ReportImageService $imageService,
        public AuditService $auditService,
        public NotificationDispatcher $notifications,
        public ItemMatchingService $matchingService,
    ) {}

    public function submit(User $reporter, array $data, array $photos = [], ?Request $request = null): ItemReport
    {
        return DB::transaction(function () use ($reporter, $data, $photos, $request): ItemReport {
            $barangayId = ! empty($data['barangay_id']) ? (int) $data['barangay_id'] : null;
            $locationId = ! empty($data['location_id']) ? (int) $data['location_id'] : null;
            $handlingOrgId = $this->routingService->resolveHandlingOrganizationId($barangayId, $locationId);
            $referenceNo = $this->referenceNumbers->next('BLF-L');

            $rewardOffered = (bool) ($data['reward_offered'] ?? false);

            $report = ItemReport::query()->create([
                'reference_no' => $referenceNo,
                'type' => ReportType::Lost,
                'reporter_user_id' => $reporter->id,
                'encoded_by' => $reporter->id,
                'handling_organization_id' => $handlingOrgId,
                'custody_organization_id' => null,
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
                'reward_offered' => $rewardOffered,
                'reward_details' => $rewardOffered ? ($data['reward_details'] ?? null) : null,
                'contact_preferences' => [
                    'in_app' => (bool) ($data['prefer_in_app'] ?? true),
                    'email' => (bool) ($data['prefer_email'] ?? true),
                    'phone' => (bool) ($data['prefer_phone'] ?? false),
                ],
                'contact_phone_enc' => ! empty($data['contact_phone']) ? $data['contact_phone'] : null,
                'remarks' => $data['remarks'] ?? null,
                'verification_status' => VerificationStatus::Pending,
                'item_status' => ItemStatus::Open,
                'is_confidential' => false,
                'is_public' => false,
                'source_locale' => app()->getLocale(),
            ]);

            $report->forceFill([
                'search_text' => $this->searchTextBuilder->build($report),
            ])->save();

            foreach (array_values($photos) as $index => $photo) {
                $this->imageService->storeForReport($report, $photo, isPrimary: $index === 0);
            }

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Verification,
                'from_status' => null,
                'to_status' => VerificationStatus::Pending->value,
                'changed_by' => $reporter->id,
                'remarks' => __('Lost item report submitted for verification.'),
            ]);

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Item,
                'from_status' => null,
                'to_status' => ItemStatus::Open->value,
                'changed_by' => $reporter->id,
                'remarks' => __('Initial lost item status recorded.'),
            ]);

            $this->auditService->record(
                action: 'report.lost_submitted',
                auditable: $report,
                newValues: [
                    'reference_no' => $report->reference_no,
                    'category_id' => $report->category_id,
                    'barangay_id' => $report->barangay_id,
                    'handling_organization_id' => $report->handling_organization_id,
                    'photo_count' => count($photos),
                ],
                actor: $reporter,
                organizationId: $handlingOrgId,
                request: $request,
            );

            $this->notifications->send(
                recipient: $reporter,
                eventKey: 'report.submitted',
                title: __('Lost Report Submitted (:ref)', ['ref' => $report->reference_no]),
                body: __('Your lost item report for ":item" has been submitted and is pending institutional verification.', ['item' => $report->item_name]),
                actionUrl: route('reports.show_own', $report),
                meta: ['report_id' => $report->id, 'reference_no' => $report->reference_no],
            );

            $this->matchingService->matchForReport($report, includePending: true);

            return $report;
        });
    }
}
