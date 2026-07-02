// Central registry of in-app guided tours. One entry per primary admin page.
// Steps with an `element` selector spotlight that element; steps without one
// show a centered popover. PageTour.vue filters out steps whose element is
// absent, so anchors can be added incrementally without breaking a tour.
//
// Shared anchors live in AppLayout.vue: [data-tour="t-nav"] (sidebar),
// [data-tour="t-topbar"] (header).

const nav = {
    element: '[data-tour="t-nav"]',
    popover: { title: 'Your workspace', description: 'Jump between bookings, quotations, payments and settings from here.', side: 'right' },
};

export const tours = {
    dashboard: {
        steps: [
            { popover: { title: 'Welcome to EventPro', description: 'Quick tour of your dashboard. Use ← → or click Next; press Esc to leave anytime.' } },
            nav,
            { element: '[data-tour="dashboard.stats"]', popover: { title: 'At a glance', description: 'Live totals for bookings, inquiries, revenue and clients.', side: 'bottom' } },
            { element: '[data-tour="dashboard.actions"]', popover: { title: 'Quick actions', description: 'Jump straight into the work you do most.', side: 'top' } },
        ],
    },
    bookings: {
        steps: [
            { popover: { title: 'Bookings', description: 'Every event you take on lives here.' } },
            { element: '[data-tour="bookings.new"]', popover: { title: 'New booking', description: 'Create a booking — pick a venue, package, date and client.', side: 'left' } },
            { element: '[data-tour="bookings.list"]', popover: { title: 'Your bookings', description: 'Search, filter by status, and open any booking for full detail.', side: 'top' } },
        ],
    },
    'booking-create': {
        steps: [
            { popover: { title: 'Create a booking', description: 'Fill in the event details. Availability and pricing update as you go.' } },
        ],
    },
    'booking-show': {
        steps: [
            { popover: { title: 'Booking detail', description: 'Everything about this event — timeline, vendors, and payment history.' } },
            { element: '[data-tour="booking.payments"]', popover: { title: 'Payments', description: 'Record a payment or download a receipt right here.', side: 'top' } },
        ],
    },
    inquiries: {
        steps: [
            { popover: { title: 'Inquiries', description: 'Incoming leads land here. Respond and convert them into quotations.' } },
            nav,
        ],
    },
    quotations: {
        steps: [
            { popover: { title: 'Quotations', description: 'Build, send and track quotations through to acceptance.' } },
            { element: '[data-tour="quotations.new"]', popover: { title: 'New quotation', description: 'Draft a quote from an inquiry or from scratch.', side: 'left' } },
            { element: '[data-tour="quotations.list"]', popover: { title: 'Track status', description: 'Draft, sent, accepted or expired — at a glance.', side: 'top' } },
        ],
    },
    'quotation-show': {
        steps: [
            { popover: { title: 'Quotation detail', description: 'Review line items, then send, accept or convert to a booking.' } },
            { element: '[data-tour="quotation.actions"]', popover: { title: 'Move it forward', description: 'Advance the quotation through its workflow.', side: 'bottom' } },
        ],
    },
    clients: {
        steps: [
            { popover: { title: 'Clients', description: 'Your contacts and their full event history.' } },
            nav,
        ],
    },
    venues: {
        steps: [
            { popover: { title: 'Venues', description: 'The spaces you book out. Set capacity, pricing and availability.' } },
            { element: '[data-tour="venues.new"]', popover: { title: 'Add a venue', description: 'List a new space clients can book.', side: 'left' } },
        ],
    },
    packages: {
        steps: [
            { popover: { title: 'Packages', description: 'Bundle your services into sellable packages.' } },
            { element: '[data-tour="packages.new"]', popover: { title: 'Create a package', description: 'Define inclusions and pricing.', side: 'left' } },
        ],
    },
    vendors: {
        steps: [
            { popover: { title: 'Vendors', description: 'Your network of caterers, florists and more — assign them to bookings.' } },
            nav,
        ],
    },
    tasks: {
        steps: [
            { popover: { title: 'Tasks', description: 'Track to-dos and assignments across your events.' } },
            nav,
        ],
    },
    payments: {
        steps: [
            { popover: { title: 'Payments', description: 'Every payment across all bookings, with totals and receipts.' } },
            { element: '[data-tour="payments.summary"]', popover: { title: 'Money in', description: 'Received, this month, and pending at a glance.', side: 'bottom' } },
            { element: '[data-tour="payments.list"]', popover: { title: 'All payments', description: 'Filter, view detail, or download a receipt.', side: 'top' } },
        ],
    },
    reports: {
        steps: [
            { popover: { title: 'Reports', description: 'Revenue, bookings and occupancy — exportable to CSV and PDF.' } },
            nav,
        ],
    },
    settings: {
        steps: [
            { popover: { title: 'Settings', description: 'Make EventPro yours.' } },
            { element: '[data-tour="settings.tabs"]', popover: { title: 'Everything in one place', description: 'Branding, PayHere payments, email and document templates.', side: 'bottom' } },
        ],
    },
};
