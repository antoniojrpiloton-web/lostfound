<?php

namespace App\Models;

use App\Enums\ImageValidationStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_report_id',
    'private_path',
    'public_path',
    'mime',
    'size',
    'width',
    'height',
    'sha256',
    'validation_status',
    'validation_result',
    'reviewed_by',
    'reviewed_at',
    'is_primary',
])]
class ItemImage extends Model
{
    use HasEncryptedRouteKey;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'validation_status' => ImageValidationStatus::class,
            'validation_result' => 'array',
            'reviewed_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    public function itemReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
