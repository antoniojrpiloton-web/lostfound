<?php

namespace App\Services\Reports;

use App\Enums\CustodyAction;
use App\Enums\CustodyStage;
use App\Enums\ImageValidationStatus;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\VerificationStatus;
use App\Jobs\GenerateImageEmbeddingJob;
use App\Jobs\MatchReportJob;
use App\Models\ItemCustodyRecord;
use App\Models\ItemReport;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReportVerificationService
{
    public function __construct(
        public ReportImageService $imageService,
        public AuditService $auditService,
        public NotificationDispatcher $notifications,
    ) {}

    public function verify(ItemReport $report, User $actor, ?string $remarks = null, ?Request $request = null): ItemReport
    {
        if (! in_array($report->verification_status, [VerificationStatus::Pending, VerificationStatus::NeedsRevision], true)) {
            throw new RuntimeException(__('Only pending or revision-requested reports can be verified.'));
        }

        return DB::transaction(function () use ($report, $actor, $remarks, $request): ItemReport {
            $from = $report->verification_status->value;
            $shouldPublish = ! $report->is_confidential
                && ! in_array($report->item_status, [ItemStatus::Closed, ItemStatus::Disposed], true);

            $report->forceFill([
                'verification_status' => VerificationStatus::Verified,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejection_reason' => null,
                'is_public' => $shouldPublish,
            ])->save();

            foreach ($report->images()->get() as $image) {
                if (in_array($image->validation_status, [ImageValidationStatus::Passed, ImageValidationStatus::Overridden], true)
                    && $image->public_path === null) {
                    $this->imageService->generatePublicDerivative($image);
                }

                GenerateImageEmbeddingJob::dispatch($image->id);
            }

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Verification,
                'from_status' => $from,
                'to_status' => VerificationStatus::Verified->value,
                'changed_by' => $actor->id,
                'remarks' => $remarks ?: __('Report verified and approved for publication.'),
            ]);

            $this->auditService->record(
                action: 'report.verified',
                auditable: $report,
                oldValues: ['verification_status' => $from, 'is_public' => false],
                newValues: ['verification_status' => VerificationStatus::Verified->value, 'is_public' => $shouldPublish],
                actor: $actor,
                organizationId: $report->handling_organization_id,
                request: $request,
            );

            MatchReportJob::dispatch($report->id);

            $this->notifications->send(
                recipient: $report->reporter,
                eventKey: 'report.verified',
                title: __('Report Verified (:ref)', ['ref' => $report->reference_no]),
                body: __('Your report for ":item" has been verified and published.', ['item' => $report->item_name]),
                actionUrl: route('reports.show_own', $report),
                meta: ['report_id' => $report->id],
            );

            return $report->fresh();
        });
    }

    public function reject(ItemReport $report, User $actor, string $reason, ?Request $request = null): ItemReport
    {
        if (! in_array($report->verification_status, [VerificationStatus::Pending, VerificationStatus::NeedsRevision], true)) {
            throw new RuntimeException(__('Only pending or revision-requested reports can be rejected.'));
        }

        return DB::transaction(function () use ($report, $actor, $reason, $request): ItemReport {
            $from = $report->verification_status->value;

            $report->forceFill([
                'verification_status' => VerificationStatus::Rejected,
                'rejection_reason' => $reason,
                'is_public' => false,
            ])->save();

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Verification,
                'from_status' => $from,
                'to_status' => VerificationStatus::Rejected->value,
                'changed_by' => $actor->id,
                'remarks' => $reason,
            ]);

            $this->auditService->record(
                action: 'report.rejected',
                auditable: $report,
                oldValues: ['verification_status' => $from],
                newValues: ['verification_status' => VerificationStatus::Rejected->value, 'rejection_reason' => $reason],
                actor: $actor,
                organizationId: $report->handling_organization_id,
                request: $request,
            );

            $this->notifications->send(
                recipient: $report->reporter,
                eventKey: 'report.rejected',
                title: __('Report Rejected (:ref)', ['ref' => $report->reference_no]),
                body: __('Your report for ":item" was rejected. Reason: :reason', ['item' => $report->item_name, 'reason' => $reason]),
                actionUrl: route('reports.show_own', $report),
                meta: ['report_id' => $report->id],
            );

            return $report->fresh();
        });
    }

    public function requestRevision(ItemReport $report, User $actor, string $remarks, ?Request $request = null): ItemReport
    {
        if ($report->verification_status !== VerificationStatus::Pending) {
            throw new RuntimeException(__('Only pending reports can be sent back for revision.'));
        }

        return DB::transaction(function () use ($report, $actor, $remarks, $request): ItemReport {
            $from = $report->verification_status->value;

            $report->forceFill([
                'verification_status' => VerificationStatus::NeedsRevision,
                'is_public' => false,
            ])->save();

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Verification,
                'from_status' => $from,
                'to_status' => VerificationStatus::NeedsRevision->value,
                'changed_by' => $actor->id,
                'remarks' => $remarks,
            ]);

            $this->auditService->record(
                action: 'report.revision_requested',
                auditable: $report,
                oldValues: ['verification_status' => $from],
                newValues: ['verification_status' => VerificationStatus::NeedsRevision->value, 'remarks' => $remarks],
                actor: $actor,
                organizationId: $report->handling_organization_id,
                request: $request,
            );

            $this->notifications->send(
                recipient: $report->reporter,
                eventKey: 'report.status_changed',
                title: __('Revision Requested (:ref)', ['ref' => $report->reference_no]),
                body: __('Please review institutional remarks for ":item": :remarks', ['item' => $report->item_name, 'remarks' => $remarks]),
                actionUrl: route('reports.show_own', $report),
                meta: ['report_id' => $report->id],
            );

            return $report->fresh();
        });
    }

    public function confirmPhysicalTurnover(ItemReport $report, User $actor, ?string $remarks = null, ?Request $request = null): ItemReport
    {
        if ($report->type !== ReportType::Found || $report->item_status !== ItemStatus::AwaitingTurnover) {
            throw new RuntimeException(__('Only found reports awaiting turnover can be confirmed into custody.'));
        }

        return DB::transaction(function () use ($report, $actor, $remarks, $request): ItemReport {
            $from = $report->item_status->value;
            $custodyOrgId = $report->custody_organization_id ?? $actor->organizationId();

            $report->forceFill([
                'custody_organization_id' => $custodyOrgId,
                'item_status' => ItemStatus::InCustody,
                'custody_stage' => $report->booth_location ? CustodyStage::AtBooth : CustodyStage::AtStation,
                'station_transferred_at' => $report->booth_location ? null : now(),
                'received_at' => now(),
            ])->save();

            if ($custodyOrgId !== null) {
                ItemCustodyRecord::query()->create([
                    'item_report_id' => $report->id,
                    'organization_id' => $custodyOrgId,
                    'action' => CustodyAction::Received,
                    'received_from' => $report->finder_name,
                    'recorded_by' => $actor->id,
                    'recorded_at' => now(),
                    'remarks' => $remarks ?: __('Physical item surrendered and received into institutional custody.'),
                ]);
            }

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Item,
                'from_status' => $from,
                'to_status' => ItemStatus::InCustody->value,
                'changed_by' => $actor->id,
                'remarks' => $remarks ?: __('Physical turnover confirmed by custody institution.'),
            ]);

            $this->auditService->record(
                action: 'report.turnover_confirmed',
                auditable: $report,
                oldValues: ['item_status' => $from],
                newValues: ['item_status' => ItemStatus::InCustody->value, 'custody_organization_id' => $custodyOrgId],
                actor: $actor,
                organizationId: $custodyOrgId,
                request: $request,
            );

            return $report->fresh();
        });
    }

    public function withdraw(ItemReport $report, User $reporter, ?string $remarks = null, ?Request $request = null): ItemReport
    {
        if (! in_array($report->verification_status, [VerificationStatus::Pending, VerificationStatus::NeedsRevision], true)) {
            throw new RuntimeException(__('Only pending or revision-requested reports can be withdrawn.'));
        }

        return DB::transaction(function () use ($report, $reporter, $remarks, $request): ItemReport {
            $fromVerification = $report->verification_status->value;
            $fromItem = $report->item_status->value;

            $report->forceFill([
                'verification_status' => VerificationStatus::Withdrawn,
                'item_status' => $report->type === ReportType::Lost ? ItemStatus::Closed : $report->item_status,
                'is_public' => false,
            ])->save();

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Verification,
                'from_status' => $fromVerification,
                'to_status' => VerificationStatus::Withdrawn->value,
                'changed_by' => $reporter->id,
                'remarks' => $remarks ?: __('Report withdrawn by reporter.'),
            ]);

            if ($report->type === ReportType::Lost && $fromItem !== ItemStatus::Closed->value) {
                ReportStatusHistory::query()->create([
                    'item_report_id' => $report->id,
                    'dimension' => StatusDimension::Item,
                    'from_status' => $fromItem,
                    'to_status' => ItemStatus::Closed->value,
                    'changed_by' => $reporter->id,
                    'remarks' => __('Closed upon reporter withdrawal.'),
                ]);
            }

            $this->auditService->record(
                action: 'report.withdrawn',
                auditable: $report,
                oldValues: ['verification_status' => $fromVerification],
                newValues: ['verification_status' => VerificationStatus::Withdrawn->value],
                actor: $reporter,
                organizationId: $report->handling_organization_id,
                request: $request,
            );

            return $report->fresh();
        });
    }
}
