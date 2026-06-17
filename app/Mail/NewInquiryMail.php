<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewInquiryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New inquiry received');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-inquiry', with: [
            'inquiry' => $this->inquiry,
        ]);
    }
}
