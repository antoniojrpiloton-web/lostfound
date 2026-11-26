<?php

namespace App\Models;

use App\Enums\NotificationDeliveryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'notification_id',
    'user_id',
    'channel',
    'notification_type',
    'status',
    'attempts',
    'error',
    'sent_at',
])]
class NotificationDelivery extends Model
{
    protected function casts(): array
    {
        return [
            'status' => NotificationDeliveryStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
