<?php

namespace App\Services\Claims;

use App\Enums\ClaimStatus;
use App\Enums\CustodyAction;
use App\Enums\ItemStatus;
use App\Enums\StatusDimension;
use App\Models\Claim;
use App\Models\ClaimHistory;
use App\Models\ItemCustodyRecord;
use App\Models\ItemRecovery;
use App\Models\ItemReport;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecoveryService
{
    public function __construct(
        public AuditService $auditService,
        public NotificationDispatcher $notifications,
    ) {}

    public function recordTurnover(Claim $claim, User $actor, array $data, bool $confirmImmediately = false, ?Request $request = null): ItemRecovery
    {
        if ($claim->status !== ClaimStatus::Approved) {
            throw new RuntimeException(__('Only approved claims can proceed to physical turnover.'));
        }

        if ($claim->recovery()->exists()) {
            throw new RuntimeException(__('A recovery record already exists for this claim.'));
        }

        return DB::transaction(function () use ($claim, $actor, $data, $confirmImmediately, $request): ItemRecovery {
            $foundReport = $claim->itemReport()->lockForUpdate()->firstOrFail();
            $orgId = $foundReport->custody_organization_id ?? $actor->organizationId();

            if ($orgId === null) {
                throw new RuntimeException(__('A custody organization is required to record recovery.'));
            }

            $recovery = ItemRecovery::query()->create([
                'item_report_id' => $foundReport->id,
                'claim_id' => $claim->id,
                'organization_id' => $orgId,
                'claimant_user_id' => $claim->claimant_user_id,
                'received_by_name' => $data['received_by_name'],
                'id_type' => $data['id_type'],
                'id_last4' => $data['id_last4'],
                'turnover_at' => $data['turnover_at'] ?? now(),
                'processed_by' => $actor->id,
                'confirmed_by' => null,
                'verification_outcome' => $data['verification_outcome'] ?? 'verified',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->auditService->record(
                action: 'recovery.recorded',
                auditable: $recovery,
                newValues: [
                    'claim_id' => $claim->id,
                    'item_report_id' => $foundReport->id,
                    'id_type' => $recovery->id_type,
                    'id_last4' => $recovery->id_last4,
                ],
                actor: $actor,
                organizationId: $orgId,
                request: $request,
            );

            if ($confirmImmediately) {
                return $this->confirm($recovery, $actor, $request);
            }

            return $recovery;
        });
    }

    public function confirm(ItemRecovery $recovery, User $actor, ?Request $request = null): ItemRecovery
    {
        if ($recovery->confirmed_by !== null) {
            throw new RuntimeException(__('This recovery has already been confirmed.'));
        }

        return DB::transaction(function () use ($recovery, $actor, $request): ItemRecovery {

            $lockedRecovery = ItemRecovery::query()->whereKey($recovery->id)->lockForUpdate()->firstOrFail();

            $claim = Claim::query()->whereKey($lockedRecovery->claim_id)->lockForUpdate()->firstOrFail();

            $foundReport = ItemReport::query()->whereKey($lockedRecovery->item_report_id)->lockForUpdate()->firstOrFail();

            if ($claim->claimant_user_id === $actor->id) {
                throw new RuntimeException(__('You cannot confirm recovery for your own claim.'));
            }

            $lockedRecovery->forceFill(['confirmed_by' => $actor->id])->save();

            $oldClaimStatus = $claim->status->value;
            $claim->forceFill(['status' => ClaimStatus::Completed])->save();

            ClaimHistory::query()->create([
                'claim_id' => $claim->id,
                'action' => 'completed',
                'from_status' => $oldClaimStatus,
                'to_status' => ClaimStatus::Completed->value,
                'changed_by' => $actor->id,
                'remarks' => __('Physical item turnover confirmed and claim completed.'),
            ]);

            $oldFoundStatus = $foundReport->item_status->value;
            $foundReport->forceFill(['item_status' => ItemStatus::Returned])->save();

            ReportStatusHistory::query()->create([
                'item_report_id' => $foundReport->id,
                'dimension' => StatusDimension::Item,
                'from_status' => $oldFoundStatus,
                'to_status' => ItemStatus::Returned->value,
                'changed_by' => $actor->id,
                'remarks' => __('Item physically returned to verified owner (:name).', ['name' => $lockedRecovery->received_by_name]),
                'related_type' => ItemRecovery::class,
                'related_id' => $lockedRecovery->id,
            ]);

            if ($claim->lost_report_id !== null) {
                $lostReport = ItemReport::query()->whereKey($claim->lost_report_id)->lockForUpdate()->first();
                if ($lostReport instanceof ItemReport && $lostReport->item_status !== ItemStatus::Resolved) {
                    $oldLostStatus = $lostReport->item_status->value;
                    $lostReport->forceFill(['item_status' => ItemStatus::Resolved])->save();

                    ReportStatusHistory::query()->create([
                        'item_report_id' => $lostReport->id,
                        'dimension' => StatusDimension::Item,
                        'from_status' => $oldLostStatus,
                        'to_status' => ItemStatus::Resolved->value,
                        'changed_by' => $actor->id,
                        'remarks' => __('Resolved upon confirmed recovery of matched found item :ref.', ['ref' => $foundReport->reference_no]),
                        'related_type' => ItemRecovery::class,
                        'related_id' => $lockedRecovery->id,
                    ]);
                }
            }

            ItemCustodyRecord::query()->create([
                'item_report_id' => $foundReport->id,
                'organization_id' => $lockedRecovery->organization_id,
                'action' => CustodyAction::Released,
                'received_from' => $lockedRecovery->received_by_name,
                'recorded_by' => $actor->id,
                'recorded_at' => $lockedRecovery->turnover_at ?? now(),
                'remarks' => __('Released to verified claimant (ID: :type ****:last4).', [
                    'type' => $lockedRecovery->id_type,
                    'last4' => $lockedRecovery->id_last4,
                ]),
            ]);

            $competingClaims = Claim::query()
                ->where('item_report_id', $foundReport->id)
                ->whereKeyNot($claim->id)
                ->whereIn('status', [
                    ClaimStatus::Submitted->value,
                    ClaimStatus::UnderReview->value,
                    ClaimStatus::InfoRequested->value,
                    ClaimStatus::Approved->value,
                ])
                ->get();

            foreach ($competingClaims as $competing) {
                $compFrom = $competing->status->value;
                $competing->forceFill([
                    'status' => ClaimStatus::Rejected,
                    'decided_by' => $actor->id,
                    'decided_at' => now(),
                    'decision_remarks' => __('Item returned to verified owner.'),
                ])->save();

                ClaimHistory::query()->create([
                    'claim_id' => $competing->id,
                    'action' => 'rejected',
                    'from_status' => $compFrom,
                    'to_status' => ClaimStatus::Rejected->value,
                    'changed_by' => $actor->id,
                    'remarks' => __('Automatically closed: item returned to verified owner.'),
                ]);
            }

            $this->auditService->record(
                action: 'recovery.confirmed',
                auditable: $lockedRecovery,
                newValues: [
                    'claim_id' => $claim->id,
                    'item_report_id' => $foundReport->id,
                    'competing_claims_closed' => $competingClaims->count(),
                ],
                actor: $actor,
                organizationId: $lockedRecovery->organization_id,
                request: $request,
            );

            $this->notifications->send(
                recipient: $claim->claimant,
                eventKey: 'item.recovered',
                title: __('Item Recovery Confirmed (:ref)', ['ref' => $foundReport->reference_no]),
                body: __('The physical turnover of ":item" has been confirmed. Thank you for using Boracay Lost and Found.', ['item' => $foundReport->item_name]),
                actionUrl: route('claims.show_own', $claim),
                meta: ['recovery_id' => $lockedRecovery->id],
            );

            return $lockedRecovery->fresh();
        });
    }
}
