<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['prefix', 'year', 'last_number'])]
class ReferenceSequence extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'prefix';

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
