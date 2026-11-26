<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'slug', 'labels', 'is_active', 'sort_order'])]
class Category extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function itemReports(): HasMany
    {
        return $this->hasMany(ItemReport::class);
    }

    public function label(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $labels = $this->labels ?? [];

        return $labels[$locale] ?? $labels['en'] ?? $this->slug;
    }
}
