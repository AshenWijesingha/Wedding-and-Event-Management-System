<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ApprovalReviewed extends Notification
{
    public function __construct(
        public string $type,
        public string $name,
        public string $decision,
        public ?string $notes,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "{$this->type} \"{$this->name}\" {$this->decision}",
            'notes' => $this->notes,
            'decision' => $this->decision,
        ];
    }
}
