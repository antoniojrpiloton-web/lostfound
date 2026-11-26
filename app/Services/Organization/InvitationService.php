<?php

namespace App\Services\Organization;

use App\Enums\MembershipStatus;
use App\Enums\RoleScope;
use App\Enums\UserStatus;
use App\Exceptions\InvitationException;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(public AuditService $auditService) {}

    public function createInvitation(
        ?User $actor,
        string $email,
        Role $role,
        ?Organization $organization = null,
        int $expiresInDays = 7,
        ?Request $request = null,
    ): array {
        $normalizedEmail = strtolower(trim($email));

        if ($role->scope === RoleScope::Public) {
            throw new InvitationException(__('Public users do not require an institutional invitation.'));
        }

        if ($role->scope === RoleScope::Platform && $organization !== null) {
            throw new InvitationException(__('Platform administrator invitations cannot be scoped to a single organization.'));
        }

        if ($role->scope === RoleScope::Organization && $organization === null) {
            throw new InvitationException(__('An organization is required for institutional roles.'));
        }

        if ($actor !== null && ! $actor->isPlatformSuperAdmin()) {
            if ($role->key !== 'institution_staff') {
                throw new InvitationException(__('Institution administrators may only invite institution staff.'));
            }

            if ($organization === null || ! $actor->belongsToOrganization($organization->id)) {
                throw new InvitationException(__('You can only invite staff to your own organization.'));
            }
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $organization?->id,
            'role_id' => $role->id,
            'email' => $normalizedEmail,
            'token_hash' => $tokenHash,
            'invited_by' => $actor?->id,
            'expires_at' => now()->addDays($expiresInDays),
        ]);

        $this->auditService->record(
            action: 'organization.invitation_created',
            auditable: $invitation,
            newValues: [
                'email' => $normalizedEmail,
                'role_key' => $role->key,
                'organization_id' => $organization?->id,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            actor: $actor,
            organizationId: $organization?->id,
            request: $request,
        );

        return [
            'invitation' => $invitation,
            'plain_token' => $plainToken,
        ];
    }

    public function acceptInvitation(string $plainToken, User $user, ?Request $request = null): OrganizationMembership
    {
        $tokenHash = hash('sha256', trim($plainToken));

        return DB::transaction(function () use ($tokenHash, $user, $request): OrganizationMembership {
            $invitation = OrganizationInvitation::query()
                ->with(['role', 'organization'])
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $invitation instanceof OrganizationInvitation) {
                throw new InvitationException(__('This invitation link is invalid.'));
            }

            if ($invitation->revoked_at !== null) {
                throw new InvitationException(__('This invitation has been revoked.'));
            }

            if ($invitation->accepted_at !== null) {
                throw new InvitationException(__('This invitation has already been used.'));
            }

            if ($invitation->expires_at->isPast()) {
                throw new InvitationException(__('This invitation has expired.'));
            }

            if ($user->email_verified_at === null) {
                throw new InvitationException(__('A verified Google email address is required to accept an invitation.'));
            }

            if (strtolower($user->email) !== strtolower($invitation->email)) {
                throw new InvitationException(__('This invitation was issued for a different email address.'));
            }

            $existingActive = $user->activeInstitutionalMembership();
            if ($existingActive !== null && $existingActive->organization_id !== $invitation->organization_id) {
                throw new InvitationException(__('Your account already has an active institutional membership.'));
            }

            $membership = OrganizationMembership::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'organization_id' => $invitation->organization_id,
                ],
                [
                    'role_id' => $invitation->role_id,
                    'status' => MembershipStatus::Active,
                    'invited_by' => $invitation->invited_by,
                    'approved_by' => $invitation->invited_by,
                    'approved_at' => now(),
                ]
            );

            $invitation->forceFill([
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
            ])->save();

            $this->auditService->record(
                action: 'organization.invitation_accepted',
                auditable: $invitation,
                newValues: [
                    'membership_id' => $membership->id,
                    'role_key' => $invitation->role->key,
                    'organization_id' => $invitation->organization_id,
                ],
                actor: $user,
                organizationId: $invitation->organization_id,
                request: $request,
            );

            return $membership;
        });
    }

    public function revokeInvitation(OrganizationInvitation $invitation, User $actor, ?Request $request = null): void
    {
        if ($invitation->accepted_at !== null) {
            throw new InvitationException(__('Accepted invitations cannot be revoked.'));
        }

        if ($invitation->revoked_at !== null) {
            return;
        }

        $invitation->forceFill(['revoked_at' => now()])->save();

        $this->auditService->record(
            action: 'organization.invitation_revoked',
            auditable: $invitation,
            newValues: ['revoked_at' => $invitation->revoked_at->toIso8601String()],
            actor: $actor,
            organizationId: $invitation->organization_id,
            request: $request,
        );
    }

    public function updateMembershipStatus(
        OrganizationMembership $membership,
        MembershipStatus $status,
        User $actor,
        ?Request $request = null,
    ): OrganizationMembership {
        $oldStatus = $membership->status;

        if ($membership->role?->key === 'platform_super_admin' && $status !== MembershipStatus::Active) {
            throw new InvitationException(__('Platform Super Administrator accounts cannot be suspended or deactivated.'));
        }

        $membership->forceFill([
            'status' => $status,
            'approved_by' => $status === MembershipStatus::Active ? ($membership->approved_by ?? $actor->id) : $membership->approved_by,
            'approved_at' => $status === MembershipStatus::Active ? ($membership->approved_at ?? now()) : $membership->approved_at,
        ])->save();

        $memberUser = $membership->user()->first();
        if ($memberUser instanceof User) {
            if ($status === MembershipStatus::Active) {
                if ($memberUser->status === UserStatus::Suspended) {
                    $memberUser->forceFill(['status' => UserStatus::Active])->save();
                }
            } else {
                $hasOtherActive = OrganizationMembership::query()
                    ->where('user_id', $memberUser->id)
                    ->where('status', MembershipStatus::Active)
                    ->exists();

                if (! $hasOtherActive) {
                    $memberUser->forceFill(['status' => UserStatus::Suspended])->save();

                    if (config('session.driver') === 'database') {
                        DB::table(config('session.table', 'sessions'))
                            ->where('user_id', $memberUser->id)
                            ->delete();
                    }
                }
            }
        }

        $this->auditService->record(
            action: 'organization.membership_status_updated',
            auditable: $membership,
            oldValues: ['status' => $oldStatus->value],
            newValues: ['status' => $status->value],
            actor: $actor,
            organizationId: $membership->organization_id,
            request: $request,
        );

        return $membership;
    }
}
