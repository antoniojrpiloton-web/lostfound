<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_image_id',
    'item_report_id',
    'model_version',
    'dimensions',
    'vector',
])]
class ImageEmbedding extends Model
{
    protected function casts(): array
    {
        return [
            'dimensions' => 'integer',
            'vector' => 'array',
        ];
    }

    public function itemImage(): BelongsTo
    {
        return $this->belongsTo(ItemImage::class);
    }

    public function itemReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class);
    }
}
