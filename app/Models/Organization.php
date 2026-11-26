<?php

namespace App\Models;

use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'code', 'name', 'contact_email', 'contact_phone', 'address', 'is_active'])]
class Organization extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'is_active' => 'boolean',
        ];
    }

    public function barangays(): BelongsToMany
    {
        return $this->belongsToMany(Barangay::class, 'organization_barangay')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function handledReports(): HasMany
    {
        return $this->hasMany(ItemReport::class, 'handling_organization_id');
    }

    public function custodyReports(): HasMany
    {
        return $this->hasMany(ItemReport::class, 'custody_organization_id');
    }

    public function custodyRecords(): HasMany
    {
        return $this->hasMany(ItemCustodyRecord::class);
    }

    public function recoveries(): HasMany
    {
        return $this->hasMany(ItemRecovery::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(ItemDisposal::class);
    }
}
