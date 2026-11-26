<?php

namespace App\Services\Disposals;

use App\Enums\ClaimStatus;
use App\Enums\CustodyAction;
use App\Enums\DisposalStatus;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\StatusDimension;
use App\Enums\VerificationStatus;
use App\Models\ItemCustodyRecord;
use App\Models\ItemDisposal;
use App\Models\ItemDisposalDocument;
use App\Models\ItemReport;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\MasterData\MasterDataCacheService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DisposalService
{
    public const METHODS = [
        'donation' => 'Donation to Charity / LGU Social Welfare',
        'auction' => 'Public Auction',
        'destruction' => 'Supervised Destruction / Waste Disposal',
        'transfer_to_lgu' => 'Transfer to Municipal LGU / Police Property Custodian',
    ];

    public function __construct(
        public MasterDataCacheService $masterData,
        public AuditService $audit,
        public NotificationDispatcher $notifications,
    ) {}

    public function flagEligibleItems(): int
    {
        $retentionDays = max(1, (int) $this->masterData->setting('disposal.retention_days', 180));
        $cutoffDate = now()->subDays($retentionDays)->toDateString();

        $activeClaimStatuses = [
            ClaimStatus::Submitted->value,
            ClaimStatus::UnderReview->value,
            ClaimStatus::InfoRequested->value,
            ClaimStatus::Approved->value,
        ];

        $candidates = ItemReport::query()
            ->where('type', ReportType::Found->value)
            ->where('verification_status', VerificationStatus::Verified->value)
            ->where('item_status', ItemStatus::InCustody->value)
            ->whereDate('occurred_date', '<=', $cutoffDate)
            ->whereDoesntHave('claims', function ($query) use ($activeClaimStatuses): void {
                $query->whereIn('status', $activeClaimStatuses);
            })
            ->whereDoesntHave('disposals', function ($query): void {
                $query->where('status', '!=', DisposalStatus::Cancelled->value);
            })
            ->get();

        $flagged = 0;

        foreach ($candidates as $report) {
            $orgId = $report->custody_organization_id ?? $report->handling_organization_id;
            if ($orgId === null) {
                continue;
            }

            DB::transaction(function () use ($report, $orgId, &$flagged): void {
                $disposal = ItemDisposal::query()->create([
                    'item_report_id' => $report->id,
                    'organization_id' => $orgId,
                    'eligible_on' => now()->toDateString(),
                    'status' => DisposalStatus::Eligible,
                ]);

                $oldStatus = $report->item_status->value;
                $report->forceFill([
                    'item_status' => ItemStatus::DisposalPending,
                ])->save();

                ReportStatusHistory::query()->create([
                    'item_report_id' => $report->id,
                    'dimension' => StatusDimension::Item,
                    'from_status' => $oldStatus,
                    'to_status' => ItemStatus::DisposalPending->value,
                    'changed_by' => null,
                    'remarks' => 'Flagged eligible for disposal after retention period.',
                ]);

                $this->notifyOrganizationAdmins($disposal, $report);
                $flagged++;
            });
        }

        return $flagged;
    }

    public function propose(ItemDisposal $disposal, User $actor, string $method, ?string $remarks = null): ItemDisposal
    {
        if ($disposal->status !== DisposalStatus::Eligible) {
            throw ValidationException::withMessages([
                'status' => __('Only eligible items can be proposed for disposal.'),
            ]);
        }

        return DB::transaction(function () use ($disposal, $actor, $method, $remarks): ItemDisposal {
            $old = ['status' => $disposal->status->value, 'method' => $disposal->method];

            $disposal->forceFill([
                'status' => DisposalStatus::Proposed,
                'method' => $method,
                'proposed_by' => $actor->id,
                'remarks' => $remarks,
            ])->save();

            $this->audit->record(
                action: 'disposal.proposed',
                auditable: $disposal,
                oldValues: $old,
                newValues: ['status' => DisposalStatus::Proposed->value, 'method' => $method],
                actor: $actor,
                organizationId: $disposal->organization_id,
            );

            return $disposal->fresh(['itemReport', 'organization', 'proposer', 'approver', 'documents']);
        });
    }

    public function approve(ItemDisposal $disposal, User $actor, ?string $remarks = null): ItemDisposal
    {
        if ($disposal->status !== DisposalStatus::Proposed) {
            throw ValidationException::withMessages([
                'status' => __('Only proposed disposals can be approved.'),
            ]);
        }

        return DB::transaction(function () use ($disposal, $actor, $remarks): ItemDisposal {
            $old = ['status' => $disposal->status->value];

            $disposal->forceFill([
                'status' => DisposalStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'remarks' => $remarks !== null && trim($remarks) !== '' ? trim($remarks) : $disposal->remarks,
            ])->save();

            $this->audit->record(
                action: 'disposal.approved',
                auditable: $disposal,
                oldValues: $old,
                newValues: ['status' => DisposalStatus::Approved->value, 'approved_by' => $actor->id],
                actor: $actor,
                organizationId: $disposal->organization_id,
            );

            return $disposal->fresh(['itemReport', 'organization', 'proposer', 'approver', 'documents']);
        });
    }

    public function complete(
        ItemDisposal $disposal,
        User $actor,
        string $disposedOn,
        string $method,
        ?string $remarks = null,
        array $documents = [],
    ): ItemDisposal {
        if ($disposal->status !== DisposalStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => __('Disposal must be approved before it can be completed.'),
            ]);
        }

        return DB::transaction(function () use ($disposal, $actor, $disposedOn, $method, $remarks, $documents): ItemDisposal {

            $report = $disposal->itemReport()->lockForUpdate()->firstOrFail();

            $oldItemStatus = $report->item_status->value;

            $disposal->forceFill([
                'status' => DisposalStatus::Completed,
                'method' => $method,
                'disposed_on' => $disposedOn,
                'remarks' => $remarks !== null && trim($remarks) !== '' ? trim($remarks) : $disposal->remarks,
            ])->save();

            foreach ($documents as $file) {
                $extension = $file->getClientOriginalExtension() ?: 'bin';
                $filename = Str::uuid()->toString().'.'.$extension;
                $path = $file->storeAs('private/disposals/'.$disposal->id, $filename, 'local');

                ItemDisposalDocument::query()->create([
                    'item_disposal_id' => $disposal->id,
                    'private_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?? 'application/octet-stream',
                    'size' => $file->getSize() ?: 0,
                ]);
            }

            $report->forceFill([
                'item_status' => ItemStatus::Disposed,
                'is_public' => false,
            ])->save();

            ItemCustodyRecord::query()->create([
                'item_report_id' => $report->id,
                'organization_id' => $disposal->organization_id,
                'from_organization_id' => null,
                'action' => CustodyAction::Disposed,
                'received_from' => null,
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
                'remarks' => 'Disposed via '.$method.($remarks ? ' — '.$remarks : ''),
            ]);

            ReportStatusHistory::query()->create([
                'item_report_id' => $report->id,
                'dimension' => StatusDimension::Item,
                'from_status' => $oldItemStatus,
                'to_status' => ItemStatus::Disposed->value,
                'changed_by' => $actor->id,
                'remarks' => 'Completed disposal ('.$method.').',
            ]);

            $this->audit->record(
                action: 'disposal.completed',
                auditable: $disposal,
                oldValues: ['status' => DisposalStatus::Approved->value, 'item_status' => $oldItemStatus],
                newValues: [
                    'status' => DisposalStatus::Completed->value,
                    'item_status' => ItemStatus::Disposed->value,
                    'method' => $method,
                    'disposed_on' => $disposedOn,
                ],
                actor: $actor,
                organizationId: $disposal->organization_id,
            );

            return $disposal->fresh(['itemReport', 'organization', 'proposer', 'approver', 'documents']);
        });
    }

    public function cancel(ItemDisposal $disposal, User $actor, string $reason): ItemDisposal
    {
        if (in_array($disposal->status, [DisposalStatus::Completed, DisposalStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => __('Completed or already cancelled disposals cannot be cancelled.'),
            ]);
        }

        return DB::transaction(function () use ($disposal, $actor, $reason): ItemDisposal {
            $oldStatus = $disposal->status->value;
            $disposal->forceFill([
                'status' => DisposalStatus::Cancelled,
                'remarks' => $reason,
            ])->save();

            $report = $disposal->itemReport;
            if ($report instanceof ItemReport && $report->item_status === ItemStatus::DisposalPending) {
                $report->forceFill(['item_status' => ItemStatus::InCustody])->save();

                ReportStatusHistory::query()->create([
                    'item_report_id' => $report->id,
                    'dimension' => StatusDimension::Item,
                    'from_status' => ItemStatus::DisposalPending->value,
                    'to_status' => ItemStatus::InCustody->value,
                    'changed_by' => $actor->id,
                    'remarks' => 'Disposal cancelled: '.$reason,
                ]);
            }

            $this->audit->record(
                action: 'disposal.cancelled',
                auditable: $disposal,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => DisposalStatus::Cancelled->value, 'remarks' => $reason],
                actor: $actor,
                organizationId: $disposal->organization_id,
            );

            return $disposal->fresh(['itemReport', 'organization', 'proposer', 'approver', 'documents']);
        });
    }

    protected function notifyOrganizationAdmins(ItemDisposal $disposal, ItemReport $report): void
    {
        $admins = User::query()
            ->whereHas('memberships', function ($query) use ($disposal): void {
                $query->where('organization_id', $disposal->organization_id)
                    ->where('status', 'active')
                    ->whereHas('role', function ($roleQuery): void {
                        $roleQuery->whereIn('key', ['institution_admin', 'platform_super_admin']);
                    });
            })
            ->get();

        foreach ($admins as $admin) {
            $this->notifications->send(
                recipient: $admin,
                eventKey: 'disposal.eligible',
                title: __('Item Eligible for Disposal'),
                body: __('Found item :ref (:name) has exceeded the custody retention period and is queued for disposal review.', [
                    'ref' => $report->reference_no,
                    'name' => $report->item_name,
                ]),
                actionUrl: route('admin.disposals.show', $disposal),
                meta: ['disposal_id' => $disposal->id, 'report_id' => $report->id],
            );
        }
    }
}
