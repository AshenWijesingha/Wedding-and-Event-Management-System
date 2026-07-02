<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Best-effort dispatch for transactional email + staff in-app notifications.
 *
 * Notifications are side effects: a mail/notification failure must never break
 * the originating request. Every dispatch is wrapped — failures are logged.
 */
class Notifier
{
    /** Roles that should receive staff-facing in-app notifications. */
    private const STAFF_ROLES = ['tenant_owner', 'admin', 'manager', 'staff'];

    public static function mail(?string $to, Mailable $mailable): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            logger()->error('Notifier mail dispatch failed: ' . $e->getMessage(), [
                'mailable' => $mailable::class,
                'to' => $to,
            ]);
        }
    }

    public static function staff(?Tenant $tenant, Notification $notification): void
    {
        if (! $tenant) {
            return;
        }

        try {
            $staff = $tenant->users()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', self::STAFF_ROLES))
                ->get();

            if ($staff->isNotEmpty()) {
                NotificationFacade::send($staff, $notification);
            }
        } catch (\Throwable $e) {
            logger()->error('Notifier staff notification failed: ' . $e->getMessage(), [
                'notification' => $notification::class,
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
