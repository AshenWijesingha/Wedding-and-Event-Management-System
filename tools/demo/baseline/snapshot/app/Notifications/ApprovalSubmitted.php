<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ApprovalSubmitted extends Notification
{
    public function __construct(public string $type, public string $name, public string $submitter) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "{$this->type} \"{$this->name}\" submitted for approval",
            'submitter' => $this->submitter,
        ];
    }
}
