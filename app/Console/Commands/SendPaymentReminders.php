<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'payments:send-reminders {--days=3 : Send reminders for payments due within this many days}';
    protected $description = 'Send payment reminders for upcoming and overdue pending payments';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $payments = Payment::with(['booking', 'client'])
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays($days)->toDateString())
            ->whereNull('reminder_sent_at')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                  ->whereNotNull('due_date')
                  ->where('due_date', '<', now()->toDateString())
                  ->where('reminder_sent_at', '<', now()->subDays(3));
            })
            ->get();

        $count = 0;
        foreach ($payments as $payment) {
            $client = $payment->client;
            if (!$client) {
                continue;
            }

            $client->notify(new PaymentReminderNotification($payment));
            $payment->update(['reminder_sent_at' => now()]);
            $count++;
        }

        $this->info("Sent {$count} payment reminder(s).");
        return self::SUCCESS;
    }
}
