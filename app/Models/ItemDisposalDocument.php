<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_disposal_id',
    'private_path',
    'original_name',
    'mime',
    'size',
])]
class ItemDisposalDocument extends Model
{
    use HasEncryptedRouteKey;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function disposal(): BelongsTo
    {
        return $this->belongsTo(ItemDisposal::class, 'item_disposal_id');
    }
}
