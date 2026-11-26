<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\RoleScope;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['google_id', 'name', 'email', 'avatar_url', 'locale', 'status', 'last_login_at', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'status' => UserStatus::class,
            'password' => 'hashed',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class)
            ->where('status', MembershipStatus::Active);
    }

    public function activeInstitutionalMembership(): ?OrganizationMembership
    {
        return $this->activeMemberships()
            ->with(['role.permissions', 'organization'])
            ->whereHas('role', function ($query): void {
                $query->whereIn('scope', [RoleScope::Platform->value, RoleScope::Organization->value]);
            })
            ->orderByRaw('CASE WHEN organization_id IS NULL THEN 0 ELSE 1 END')
            ->first();
    }

    public function isPlatformSuperAdmin(): bool
    {
        return $this->activeMemberships()
            ->whereNull('organization_id')
            ->whereHas('role', fn ($query) => $query->where('key', 'platform_super_admin'))
            ->exists();
    }

    public function isInstitutionalUser(): bool
    {
        return $this->activeInstitutionalMembership() !== null;
    }

    public function organizationId(): ?int
    {
        return $this->activeInstitutionalMembership()?->organization_id;
    }

    public function roleKey(): string
    {
        return $this->activeInstitutionalMembership()?->role?->key ?? 'public_user';
    }

    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->activeMemberships()
            ->where('organization_id', $organizationId)
            ->exists();
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->status !== UserStatus::Active) {
            return false;
        }

        $membership = $this->activeInstitutionalMembership();

        if ($membership !== null) {
            return $membership->role->permissions->contains('key', $permissionKey);
        }

        return Role::query()
            ->where('key', 'public_user')
            ->whereHas('permissions', fn ($query) => $query->where('key', $permissionKey))
            ->exists();
    }

    public function reportedItems(): HasMany
    {
        return $this->hasMany(ItemReport::class, 'reporter_user_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'claimant_user_id');
    }

    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
