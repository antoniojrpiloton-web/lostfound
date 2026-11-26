<?php

namespace App\Services\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\SystemEventNotification;
use App\Services\MasterData\MasterDataCacheService;
use Throwable;

class NotificationDispatcher
{
    public function __construct(public MasterDataCacheService $masterData) {}

    public function send(
        ?User $recipient,
        string $eventKey,
        string $title,
        string $body,
        ?string $actionUrl = null,
        array $meta = [],
    ): void {
        if (! $recipient instanceof User) {
            return;
        }

        $emailEnabled = (bool) $this->masterData->setting('notifications.email_enabled', false);
        $channels = $emailEnabled ? ['database', 'mail'] : ['database'];

        $notification = new SystemEventNotification(
            eventKey: $eventKey,
            title: $title,
            body: $body,
            actionUrl: $actionUrl,
            channels: $channels,
            meta: $meta,
        );

        foreach ($channels as $channel) {
            $delivery = NotificationDelivery::query()->create([
                'notification_id' => null,
                'user_id' => $recipient->id,
                'channel' => $channel,
                'notification_type' => $eventKey,
                'status' => NotificationDeliveryStatus::Queued,
                'attempts' => 1,
            ]);

            try {
                if ($channel === $channels[0]) {
                    $recipient->notify($notification);
                }

                $delivery->forceFill([
                    'status' => NotificationDeliveryStatus::Sent,
                    'sent_at' => now(),
                ])->save();
            } catch (Throwable $exception) {
                $delivery->forceFill([
                    'status' => NotificationDeliveryStatus::Failed,
                    'error' => $exception->getMessage(),
                ])->save();
            }
        }
    }
}
