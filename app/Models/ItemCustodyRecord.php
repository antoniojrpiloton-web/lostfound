<?php

namespace App\Models;

use App\Enums\CustodyAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_report_id',
    'organization_id',
    'from_organization_id',
    'action',
    'received_from',
    'recorded_by',
    'recorded_at',
    'remarks',
])]
class ItemCustodyRecord extends Model
{
    protected function casts(): array
    {
        return [
            'action' => CustodyAction::class,
            'recorded_at' => 'datetime',
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

    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
