<?php

namespace App\Models;

use App\Enums\CustodyStage;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use App\Services\Localization\ContentTranslationService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'reference_no',
    'type',
    'reporter_user_id',
    'encoded_by',
    'handling_organization_id',
    'custody_organization_id',
    'category_id',
    'item_name',
    'description',
    'brand',
    'model',
    'color',
    'distinguishing_features',
    'occurred_date',
    'occurred_time',
    'barangay_id',
    'location_id',
    'location_details',
    'reward_offered',
    'reward_details',
    'contact_preferences',
    'contact_phone_enc',
    'finder_name',
    'finder_contact_enc',
    'remarks',
    'verification_status',
    'item_status',
    'custody_stage',
    'booth_location',
    'station_transferred_at',
    'is_confidential',
    'is_public',
    'photo_exception_reason',
    'photo_exception_by',
    'verified_by',
    'verified_at',
    'rejection_reason',
    'received_at',
    'source_locale',
    'search_text',
])]
class ItemReport extends Model
{
    use HasEncryptedRouteKey;
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'verification_status' => VerificationStatus::class,
            'item_status' => ItemStatus::class,
            'custody_stage' => CustodyStage::class,
            'station_transferred_at' => 'datetime',
            'occurred_date' => 'date',
            'reward_offered' => 'boolean',
            'contact_preferences' => 'array',
            'contact_phone_enc' => 'encrypted',
            'finder_contact_enc' => 'encrypted',
            'is_confidential' => 'boolean',
            'is_public' => 'boolean',
            'verified_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function handlingOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'handling_organization_id');
    }

    public function custodyOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'custody_organization_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function photoExceptionApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photo_exception_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class);
    }

    public function matchesAsLost(): HasMany
    {
        return $this->hasMany(ItemMatch::class, 'lost_report_id');
    }

    public function matchesAsFound(): HasMany
    {
        return $this->hasMany(ItemMatch::class, 'found_report_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function recovery(): HasOne
    {
        return $this->hasOne(ItemRecovery::class);
    }

    public function custodyRecords(): HasMany
    {
        return $this->hasMany(ItemCustodyRecord::class);
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(ItemDisposal::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(ItemDisposal::class);
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatformSuperAdmin()) {
            return $query;
        }

        $orgId = $user->organizationId();

        if ($orgId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $builder) use ($orgId): void {
            $builder->where('handling_organization_id', $orgId)
                ->orWhere('custody_organization_id', $orgId);
        });
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where('is_confidential', false)
            ->where('verification_status', VerificationStatus::Verified)
            ->whereNotIn('item_status', [ItemStatus::Closed->value, ItemStatus::Disposed->value]);
    }

    public function publicStatusLabel(): string
    {
        if ($this->verification_status === VerificationStatus::Rejected) {
            return 'Rejected';
        }

        if ($this->verification_status !== VerificationStatus::Verified) {
            return $this->verification_status->label();
        }

        if ($this->type === ReportType::Found && $this->item_status === ItemStatus::ClaimPending) {
            return 'Claim Pending';
        }

        if (in_array($this->item_status, [ItemStatus::Returned, ItemStatus::Resolved], true)) {
            return 'Claimed';
        }

        if ($this->type === ReportType::Found && $this->item_status === ItemStatus::InCustody) {
            return 'Found';
        }

        if ($this->type === ReportType::Lost && $this->item_status === ItemStatus::Open) {
            return 'Lost';
        }

        return ucwords(str_replace('_', ' ', $this->item_status->value));
    }

    public function effectiveCustodyStage(): ?CustodyStage
    {
        if ($this->type !== ReportType::Found) {
            return null;
        }

        if ($this->item_status === ItemStatus::Returned) {
            return CustodyStage::Released;
        }

        if ($this->custody_stage instanceof CustodyStage) {
            return $this->custody_stage;
        }

        if ($this->item_status === ItemStatus::AwaitingTurnover) {
            return CustodyStage::WithFinder;
        }

        return CustodyStage::AtStation;
    }

    public function translated(string $field, ?string $locale = null, bool $cacheOnly = true): string
    {
        return app(ContentTranslationService::class)
            ->translateReportField($this, $field, $locale, $cacheOnly)['text'];
    }
}
