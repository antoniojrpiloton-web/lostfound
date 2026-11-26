<?php

namespace App\Models;

use App\Enums\MatchStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lost_report_id',
    'found_report_id',
    'score',
    'factors',
    'algorithm_version',
    'status',
    'reviewed_by',
    'reviewed_at',
    'review_remarks',
])]
class ItemMatch extends Model
{
    use HasEncryptedRouteKey;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:4',
            'factors' => 'array',
            'status' => MatchStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function lostReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'lost_report_id');
    }

    public function foundReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'found_report_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
