<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_report_id',
    'claim_id',
    'organization_id',
    'claimant_user_id',
    'received_by_name',
    'id_type',
    'id_last4',
    'turnover_at',
    'processed_by',
    'confirmed_by',
    'verification_outcome',
    'remarks',
])]
class ItemRecovery extends Model
{
    use HasEncryptedRouteKey;
    use HasEncryptedRouteKey;

    protected function casts(): array
    {
        return [
            'turnover_at' => 'datetime',
        ];
    }

    public function itemReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_user_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
