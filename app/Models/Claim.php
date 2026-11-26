<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'reference_no',
    'item_report_id',
    'lost_report_id',
    'claimant_user_id',
    'ownership_description',
    'distinguishing_details',
    'status',
    'turnover_scheduled_at',
    'pickup_mode',
    'pickup_location',
    'decided_by',
    'decided_at',
    'decision_remarks',
    'submitted_at',
])]
class Claim extends Model
{
    use HasEncryptedRouteKey;
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'turnover_scheduled_at' => 'datetime',
            'decided_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function itemReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class);
    }

    public function lostReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'lost_report_id');
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_user_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ClaimEvidence::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ClaimHistory::class);
    }

    public function recovery(): HasOne
    {
        return $this->hasOne(ItemRecovery::class);
    }
}
