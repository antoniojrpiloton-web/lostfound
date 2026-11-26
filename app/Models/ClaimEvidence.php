<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'claim_id',
    'private_path',
    'original_name',
    'mime',
    'size',
    'sha256',
    'description',
])]
class ClaimEvidence extends Model
{
    use HasEncryptedRouteKey;
    use HasEncryptedRouteKey;

    protected $table = 'claim_evidence';

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
