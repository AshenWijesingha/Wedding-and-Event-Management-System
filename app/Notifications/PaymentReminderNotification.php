<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;
        $daysUntilDue = now()->startOfDay()->diffInDays($this->payment->due_date, false);

        $urgency = $daysUntilDue <= 0 ? 'overdue' : ($daysUntilDue <= 3 ? 'due soon' : 'upcoming');

        return (new MailMessage)
            ->subject("Payment Reminder: {$this->payment->payment_number} is {$urgency}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("This is a reminder that your payment of \${$this->payment->amount} for booking {$booking->booking_number} is {$urgency}.")
            ->line("Due date: {$this->payment->due_date->format('M d, Y')}")
            ->line("Installment: {$this->payment->installment_name}")
            ->action('View Booking', url("/portal/bookings"))
            ->line('Please contact us if you have any questions.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_id'     => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'amount'         => $this->payment->amount,
            'due_date'       => $this->payment->due_date?->toDateString(),
            'booking_number' => $this->payment->booking->booking_number ?? null,
        ];
    }
}
