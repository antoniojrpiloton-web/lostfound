<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $eventKey,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public array $channels = ['database'],
        public array $meta = [],
    ) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('['.config('app.name').'] '.$this->title)
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('User')]))
            ->line($this->body);

        if ($this->actionUrl !== null) {
            $mail->action(__('View Details'), $this->actionUrl);
        }

        return $mail->line(__('Thank you for using Boracay Lost and Found System.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->eventKey,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'meta' => $this->meta,
        ];
    }
}
