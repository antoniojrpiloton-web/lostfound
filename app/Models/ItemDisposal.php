<?php

namespace App\Models;

use App\Enums\DisposalStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'item_report_id',
    'organization_id',
    'eligible_on',
    'status',
    'method',
    'proposed_by',
    'approved_by',
    'approved_at',
    'disposed_on',
    'remarks',
])]
class ItemDisposal extends Model
{
    use HasEncryptedRouteKey;

    protected function casts(): array
    {
        return [
            'eligible_on' => 'date',
            'status' => DisposalStatus::class,
            'approved_at' => 'datetime',
            'disposed_on' => 'date',
        ];
    }

    public function itemReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ItemDisposalDocument::class);
    }
}
