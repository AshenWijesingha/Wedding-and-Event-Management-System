<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public ?string $tempPassword = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You have been invited to ' . ($this->user->tenant->name ?? 'EventPro'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.user-invited', with: [
            'user' => $this->user,
            'tempPassword' => $this->tempPassword,
            'loginUrl' => url('/login'),
        ]);
    }
}
