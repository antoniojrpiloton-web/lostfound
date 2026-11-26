<?php

namespace App\Services\Claims;

use App\Enums\ClaimStatus;
use App\Enums\CustodyStage;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\VerificationStatus;
use App\Models\Claim;
use App\Models\ClaimEvidence;
use App\Models\ClaimHistory;
use App\Models\ItemReport;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reports\ReferenceNumberService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ClaimService
{
    private const ACTIVE_STATUSES = [
        ClaimStatus::Submitted->value,
        ClaimStatus::UnderReview->value,
        ClaimStatus::InfoRequested->value,
        ClaimStatus::Approved->value,
    ];

    public function __construct(
        public ReferenceNumberService $referenceNumbers,
        public AuditService $auditService,
        public NotificationDispatcher $notifications,
    ) {}

    public function isReportClaimable(ItemReport $report): bool
    {
        return $report->type === ReportType::Found
            && $report->verification_status === VerificationStatus::Verified
            && in_array($report->item_status, [ItemStatus::AwaitingTurnover, ItemStatus::InCustody, ItemStatus::ClaimPending], true);
    }

    public function submit(User $claimant, ItemReport $foundReport, array $data, array $evidenceFiles = [], ?Request $request = null): Claim
    {
        if (! $this->isReportClaimable($foundReport)) {
            throw new RuntimeException(__('This item is not currently eligible for ownership claims.'));
        }

        $duplicateActive = Claim::query()
            ->where('item_report_id', $foundReport->id)
            ->where('claimant_user_id', $claimant->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();

        if ($duplicateActive) {
            throw new RuntimeException(__('You already have an active claim for this found item.'));
        }

        return DB::transaction(function () use ($claimant, $foundReport, $data, $evidenceFiles, $request): Claim {
            $referenceNo = $this->referenceNumbers->next('BLF-C');

            $claim = Claim::query()->create([
                'reference_no' => $referenceNo,
                'item_report_id' => $foundReport->id,
                'lost_report_id' => ! empty($data['lost_report_id']) ? (int) $data['lost_report_id'] : null,
                'claimant_user_id' => $claimant->id,
                'ownership_description' => $data['ownership_description'],
                'distinguishing_details' => $data['distinguishing_details'],
                'status' => ClaimStatus::Submitted,
                'submitted_at' => now(),
            ]);

            foreach ($evidenceFiles as $file) {
                $this->storeEvidence($claim, $file, $data['evidence_description'] ?? null);
            }

            if ($foundReport->item_status === ItemStatus::InCustody) {
                $foundReport->forceFill(['item_status' => ItemStatus::ClaimPending])->save();

                ReportStatusHistory::query()->create([
                    'item_report_id' => $foundReport->id,
                    'dimension' => StatusDimension::Item,
                    'from_status' => ItemStatus::InCustody->value,
                    'to_status' => ItemStatus::ClaimPending->value,
                    'changed_by' => $claimant->id,
                    'remarks' => __('Ownership claim :ref submitted.', ['ref' => $referenceNo]),
                    'related_type' => Claim::class,
                    'related_id' => $claim->id,
                ]);
            }

            ClaimHistory::query()->create([
                'claim_id' => $claim->id,
                'action' => 'submitted',
                'from_status' => null,
                'to_status' => ClaimStatus::Submitted->value,
                'changed_by' => $claimant->id,
                'remarks' => __('Claim submitted for institutional review.'),
            ]);

            $this->auditService->record(
                action: 'claim.submitted',
                auditable: $claim,
                newValues: [
                    'reference_no' => $referenceNo,
                    'item_report_id' => $foundReport->id,
                    'evidence_count' => count($evidenceFiles),
                ],
                actor: $claimant,
                organizationId: $foundReport->custody_organization_id,
                request: $request,
            );

            $claimBody = $foundReport->item_status === ItemStatus::AwaitingTurnover
                ? __('Your ownership claim for ":item" has been submitted. This item is currently held by the finder; once police verify your claim, the finder will be notified to bring it to the Police Booth for supervised return.', ['item' => $foundReport->item_name])
                : __('Your ownership claim for ":item" has been submitted for institutional verification.', ['item' => $foundReport->item_name]);

            $this->notifications->send(
                recipient: $claimant,
                eventKey: 'claim.submitted',
                title: __('Claim Submitted (:ref)', ['ref' => $referenceNo]),
                body: $claimBody,
                actionUrl: route('claims.show_own', $claim),
                meta: ['claim_id' => $claim->id],
            );

            return $claim;
        });
    }

    public function requestAdditionalInfo(Claim $claim, User $actor, string $remarks, ?Request $request = null): Claim
    {
        if (! in_array($claim->status, [ClaimStatus::Submitted, ClaimStatus::UnderReview], true)) {
            throw new RuntimeException(__('Additional information can only be requested on submitted or under-review claims.'));
        }

        return $this->transition($claim, ClaimStatus::InfoRequested, 'info_requested', $actor, $remarks, $request);
    }

    public function respondToInfoRequest(Claim $claim, User $claimant, string $details, array $evidenceFiles = [], ?Request $request = null): Claim
    {
        if ($claim->status !== ClaimStatus::InfoRequested) {
            throw new RuntimeException(__('This claim is not awaiting additional information.'));
        }

        return DB::transaction(function () use ($claim, $claimant, $details, $evidenceFiles, $request): Claim {
            $combined = trim(($claim->distinguishing_details ?? '')."\n\n[Additional Response]: ".$details);
            $claim->forceFill(['distinguishing_details' => $combined])->save();

            foreach ($evidenceFiles as $file) {
                $this->storeEvidence($claim, $file, __('Additional information response'));
            }

            return $this->transition($claim, ClaimStatus::UnderReview, 'info_provided', $claimant, $details, $request);
        });
    }

    public function approve(
        Claim $claim,
        User $actor,
        ?string $turnoverScheduledAt = null,
        ?string $remarks = null,
        ?Request $request = null,
        ?string $pickupMode = null,
        ?string $pickupLocation = null,
    ): Claim {
        if ($claim->claimant_user_id === $actor->id) {
            throw new RuntimeException(__('You cannot approve your own claim.'));
        }

        if (! in_array($claim->status, [ClaimStatus::Submitted, ClaimStatus::UnderReview, ClaimStatus::InfoRequested], true)) {
            throw new RuntimeException(__('This claim cannot be approved in its current state.'));
        }

        return DB::transaction(function () use ($claim, $actor, $turnoverScheduledAt, $remarks, $request, $pickupMode, $pickupLocation): Claim {
            $alreadyApproved = Claim::query()
                ->where('item_report_id', $claim->item_report_id)
                ->whereKeyNot($claim->id)
                ->whereIn('status', [ClaimStatus::Approved->value, ClaimStatus::Completed->value])
                ->lockForUpdate()
                ->exists();

            if ($alreadyApproved) {
                throw new RuntimeException(__('Another claim for this physical item is already approved or completed.'));
            }

            $normalizedPickupMode = in_array($pickupMode, ['station_pickup', 'next_day_booth'], true)
                ? $pickupMode
                : 'station_pickup';
            $normalizedPickupLocation = $pickupLocation !== null && trim($pickupLocation) !== ''
                ? trim($pickupLocation)
                : null;

            $from = $claim->status->value;
            $claim->forceFill([
                'status' => ClaimStatus::Approved,
                'turnover_scheduled_at' => $turnoverScheduledAt ?: null,
                'pickup_mode' => $normalizedPickupMode,
                'pickup_location' => $normalizedPickupLocation,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_remarks' => $remarks,
            ])->save();

            $foundReport = $claim->itemReport()->first();
            if ($foundReport instanceof ItemReport && $normalizedPickupMode === 'next_day_booth') {
                $foundReport->forceFill([
                    'custody_stage' => CustodyStage::ScheduledForBooth,
                    'booth_location' => $normalizedPickupLocation ?: $foundReport->booth_location,
                ])->save();
            }

            $pickupNote = $normalizedPickupMode === 'next_day_booth'
                ? __('Scheduled for next-day Police Booth bring-back at :loc.', ['loc' => $normalizedPickupLocation ?: __('designated beach booth')])
                : __('Owner to claim directly at the Police Station.');

            ClaimHistory::query()->create([
                'claim_id' => $claim->id,
                'action' => 'approved',
                'from_status' => $from,
                'to_status' => ClaimStatus::Approved->value,
                'changed_by' => $actor->id,
                'remarks' => trim(($remarks ? $remarks.' ' : '').$pickupNote),
            ]);

            $this->auditService->record(
                action: 'claim.approved',
                auditable: $claim,
                oldValues: ['status' => $from],
                newValues: [
                    'status' => ClaimStatus::Approved->value,
                    'turnover_scheduled_at' => $turnoverScheduledAt,
                    'pickup_mode' => $normalizedPickupMode,
                    'pickup_location' => $normalizedPickupLocation,
                ],
                actor: $actor,
                organizationId: $claim->itemReport?->custody_organization_id,
                request: $request,
            );

            $notificationBody = $normalizedPickupMode === 'next_day_booth'
                ? __('Your ownership claim has been approved. Officers will bring your item to :loc on the scheduled date. Please present a valid ID.', ['loc' => $normalizedPickupLocation ?: __('your designated Police Booth')])
                : __('Your ownership claim has been approved. Because unclaimed items remain secured at the station, please visit the Police Station with a valid ID to complete physical turnover.');

            $this->notifications->send(
                recipient: $claim->claimant,
                eventKey: 'claim.approved',
                title: __('Claim Approved (:ref)', ['ref' => $claim->reference_no]),
                body: $notificationBody,
                actionUrl: route('claims.show_own', $claim),
                meta: ['claim_id' => $claim->id, 'pickup_mode' => $normalizedPickupMode, 'pickup_location' => $normalizedPickupLocation],
            );

            return $claim->fresh();
        });
    }

    public function reject(Claim $claim, User $actor, string $remarks, ?Request $request = null): Claim
    {
        if ($claim->claimant_user_id === $actor->id) {
            throw new RuntimeException(__('You cannot decide your own claim.'));
        }

        if (! in_array($claim->status, self::ACTIVE_STATUSES, true)) {
            throw new RuntimeException(__('Only active claims can be rejected.'));
        }

        return DB::transaction(function () use ($claim, $actor, $remarks, $request): Claim {
            $from = $claim->status->value;
            $claim->forceFill([
                'status' => ClaimStatus::Rejected,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_remarks' => $remarks,
            ])->save();

            ClaimHistory::query()->create([
                'claim_id' => $claim->id,
                'action' => 'rejected',
                'from_status' => $from,
                'to_status' => ClaimStatus::Rejected->value,
                'changed_by' => $actor->id,
                'remarks' => $remarks,
            ]);

            $this->revertItemIfNoActiveClaims($claim, $actor);

            $this->auditService->record(
                action: 'claim.rejected',
                auditable: $claim,
                oldValues: ['status' => $from],
                newValues: ['status' => ClaimStatus::Rejected->value, 'decision_remarks' => $remarks],
                actor: $actor,
                organizationId: $claim->itemReport?->custody_organization_id,
                request: $request,
            );

            $this->notifications->send(
                recipient: $claim->claimant,
                eventKey: 'claim.rejected',
                title: __('Claim Rejected (:ref)', ['ref' => $claim->reference_no]),
                body: __('Your ownership claim was not approved. Remarks: :remarks', ['remarks' => $remarks]),
                actionUrl: route('claims.show_own', $claim),
                meta: ['claim_id' => $claim->id],
            );

            return $claim->fresh();
        });
    }

    public function withdraw(Claim $claim, User $claimant, ?string $remarks = null, ?Request $request = null): Claim
    {
        if (! in_array($claim->status, [ClaimStatus::Submitted, ClaimStatus::UnderReview, ClaimStatus::InfoRequested], true)) {
            throw new RuntimeException(__('Only pending claims can be withdrawn.'));
        }

        return DB::transaction(function () use ($claim, $claimant, $remarks, $request): Claim {
            $updated = $this->transition($claim, ClaimStatus::Withdrawn, 'withdrawn', $claimant, $remarks ?: __('Claim withdrawn by claimant.'), $request);
            $this->revertItemIfNoActiveClaims($updated, $claimant);

            return $updated;
        });
    }

    protected function transition(
        Claim $claim,
        ClaimStatus $to,
        string $action,
        User $actor,
        ?string $remarks = null,
        ?Request $request = null,
    ): Claim {
        $from = $claim->status->value;
        $claim->forceFill(['status' => $to])->save();

        ClaimHistory::query()->create([
            'claim_id' => $claim->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to->value,
            'changed_by' => $actor->id,
            'remarks' => $remarks,
        ]);

        $this->auditService->record(
            action: 'claim.'.$action,
            auditable: $claim,
            oldValues: ['status' => $from],
            newValues: ['status' => $to->value],
            actor: $actor,
            organizationId: $claim->itemReport?->custody_organization_id,
            request: $request,
        );

        return $claim->fresh();
    }

    protected function revertItemIfNoActiveClaims(Claim $claim, User $actor): void
    {
        $report = $claim->itemReport()->first();
        if (! $report instanceof ItemReport || $report->item_status !== ItemStatus::ClaimPending) {
            return;
        }

        $hasOtherActive = Claim::query()
            ->where('item_report_id', $report->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();

        if (! $hasOtherActive) {
            $report->forceFill(['item_status' => ItemStatus::InCustody])->save();

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Item,
                'from_status' => ItemStatus::ClaimPending->value,
                'to_status' => ItemStatus::InCustody->value,
                'changed_by' => $actor->id,
                'remarks' => __('Reverted to in custody after no active claims remained.'),
                'related_type' => Claim::class,
                'related_id' => $claim->id,
            ]);
        }
    }

    protected function storeEvidence(Claim $claim, UploadedFile $file, ?string $description = null): ClaimEvidence
    {
        $realPath = $file->getRealPath();
        $sha256 = $realPath !== false ? hash_file('sha256', $realPath) : hash('sha256', (string) $file->get());
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $uuid = (string) Str::uuid();
        $privatePath = "claim-evidence/private/{$claim->id}/{$uuid}.{$ext}";

        Storage::disk('local')->putFileAs("claim-evidence/private/{$claim->id}", $file, "{$uuid}.{$ext}");

        return ClaimEvidence::query()->create([
            'claim_id' => $claim->id,
            'private_path' => $privatePath,
            'original_name' => $file->getClientOriginalName() ?: "{$uuid}.{$ext}",
            'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
            'sha256' => (string) $sha256,
            'description' => $description,
        ]);
    }
}
