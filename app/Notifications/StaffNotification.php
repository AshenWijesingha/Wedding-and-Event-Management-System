<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic in-app (database channel) notification for tenant staff.
 * `event` keys the icon/grouping on the frontend; `message` is the human line.
 */
class StaffNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $event,
        public string $message,
        public ?string $url = null,
        public array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event'   => $this->event,
            'message' => $this->message,
            'url'     => $this->url,
            ...$this->data,
        ];
    }
}
