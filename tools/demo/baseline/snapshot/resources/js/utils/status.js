// Single source of truth for status → token color classes. Replaces the
// per-page `statusColors` maps duplicated across Payments/Quotations/Bookings/etc.

const STATUS_STYLES = {
    // success (green)
    completed: 'bg-success-soft text-success',
    accepted:  'bg-success-soft text-success',
    confirmed: 'bg-success-soft text-success',
    active:    'bg-success-soft text-success',
    paid:      'bg-success-soft text-success',
    enabled:   'bg-success-soft text-success',
    published: 'bg-success-soft text-success',

    // info (blue)
    sent:      'bg-info-soft text-info',
    viewed:    'bg-info-soft text-info',
    new:       'bg-info-soft text-info',
    contacted: 'bg-info-soft text-info',
    quoted:    'bg-info-soft text-info',

    // warning (amber)
    pending:   'bg-warning-soft text-warning',
    tentative: 'bg-warning-soft text-warning',
    refunded:  'bg-warning-soft text-warning',
    overdue:   'bg-warning-soft text-warning',
    'in_progress': 'bg-warning-soft text-warning',

    // danger (red)
    failed:    'bg-danger-soft text-danger',
    rejected:  'bg-danger-soft text-danger',
    cancelled: 'bg-danger-soft text-danger',
    canceled:  'bg-danger-soft text-danger',
    expired:   'bg-danger-soft text-danger',
    suspended: 'bg-danger-soft text-danger',
    lost:      'bg-danger-soft text-danger',

    // neutral
    draft:     'bg-surface-sunken text-ink-muted',
    disabled:  'bg-surface-sunken text-ink-muted',
    inactive:  'bg-surface-sunken text-ink-muted',
};

const NEUTRAL = 'bg-surface-sunken text-ink-muted';

export function statusClass(status) {
    if (!status) return NEUTRAL;
    return STATUS_STYLES[String(status).toLowerCase()] ?? NEUTRAL;
}
