<?php

namespace App\Models;

use App\Enums\StatusDimension;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'item_report_id',
    'dimension',
    'from_status',
    'to_status',
    'changed_by',
    'remarks',
    'related_type',
    'related_id',
    'created_at',
])]
class ReportStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'dimension' => StatusDimension::class,
            'created_at' => 'datetime',
        ];
    }

    public function itemReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
