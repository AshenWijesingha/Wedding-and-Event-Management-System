# EventPro Admin User Guide

## Overview

EventPro is a multi-tenant Wedding and Event Management System. The admin panel is accessed at `/admin` after login.

---

## Dashboard

The dashboard provides at-a-glance KPIs:
- **Total Revenue** — sum of all completed payments
- **Confirmed Bookings** — count of confirmed future events
- **Open Inquiries** — inquiries awaiting response
- **Upcoming Events** — events in the next 30 days

Charts show monthly revenue and booking status breakdown.

---

## Hotels

Path: `/admin/hotels`

Hotels are the parent properties that group one or more venue halls. Managers can create and edit hotels; only approved hotels appear in the public venue list and are selectable in quotations.

---

## Approval workflow

Hotels, venues, and packages all go through an approval cycle before they are visible to clients or selectable when building quotations.

### Status values

| Status | Meaning |
| --- | --- |
| `draft` | Created but not yet submitted. Only the owning tenant can see it. |
| `pending` | Submitted for review. Visible to super admins in the approvals queue. |
| `approved` | Live. Appears in the public list and can be selected in quotations. |
| `rejected` | Returned to the manager with reviewer notes. Can be edited and re-submitted. |

### Manager flow

1. Create a hotel / venue / package — it starts as **draft**.
2. Fill in all required fields, then click **Submit for approval** — status moves to **pending**.
3. Wait for a super admin decision (an in-app notification is sent on approval or rejection).
4. If **rejected**, read the reviewer notes, edit the item, and re-submit.
5. If **approved**, the item is immediately live. Editing an approved item keeps it live but automatically moves it back to **pending** so the changes can be reviewed.

### Super admin flow

Path: `/admin/approvals`

The approvals queue lists every hotel, venue, and package in **pending** status across all tenants. For each item the super admin can:

- **Approve** — moves status to `approved`; manager receives a notification.
- **Reject** — moves status to `rejected` and requires reviewer notes (mandatory) sent back to the manager.

---

## Venues

Path: `/admin/venues`

### Creating a Venue
1. Click **New Venue**
2. Fill in: Name, Description, Capacity (min/max), Base Price, Weekend Surcharge
3. Add amenities using the tag builder
4. Save

### Availability
Click a venue's name → **Availability** tab to see a monthly calendar of booked dates.

---

## Packages

Path: `/admin/packages`

Packages bundle services at a per-guest or flat price. Each package can have an `includes` list displayed to clients.

---

## Bookings

Path: `/admin/bookings`

### Booking Lifecycle
`pending` → `tentative` → `confirmed` → `completed`  
Any status → `cancelled`

### Actions
- **Confirm** — moves pending/tentative to confirmed
- **Cancel** — cancels the booking (requires reason)
- **Assign Vendor** — attach a vendor with agreed amount and service description

### Filtering
Use the search bar (booking number or client name) and status filter at the top.

---

## Quotations

Path: `/admin/quotations`

- View all quotations with status filter (draft, sent, viewed, accepted, expired)
- Open a quotation to see the full line-item breakdown
- **Download PDF** — generates a branded PDF ready to send to the client
- Quotations auto-expire after the `valid_until` date

---

## Payments

Path: `/admin/payments`

Financial overview with:
- Summary cards (total revenue, this month, pending)
- Filter by status (pending, completed, failed, refunded)
- Filter by payment method

---

## Clients

Path: `/admin/clients`

Manage client profiles. Each client can be linked to bookings and quotations.

---

## Vendors

Path: `/admin/vendors`

Maintain your vendor directory (florists, photographers, caterers, etc.). Vendors can be attached to bookings with a confirmed service amount.

---

## Staff

Path: `/admin/staff`

Manage your team. Each staff member has:
- Role (coordinator, operations, sales, etc.)
- Contact details
- Task list accessible from the staff profile

---

## Tasks

Path: `/admin/tasks`

Task management board with:
- Priority levels (low, medium, high, urgent)
- Due date tracking
- Status (pending, in_progress, completed)
- Assignee filter

---

## Inquiries

Path: `/admin/inquiries`

All inquiries submitted via the public contact form. Update the status (new, in_progress, quoted, booked, closed) to track progress.

---

## Reports

Path: `/admin/reports`

Three sub-reports:
1. **Revenue** — monthly revenue + payment method breakdown
2. **Bookings** — monthly booking counts + event type breakdown
3. **Occupancy** — monthly occupancy rate + per-venue booking days

---

## Settings

Path: `/admin/settings`

Four tabs:

### General
- Business name, timezone, currency, address, contact details

### Branding
- Primary color, logo upload, social media links

### Email Templates
- Customize booking confirmation, quotation email, payment reminder, and welcome email templates  
- Available variables: `{client_name}`, `{booking_number}`, `{event_date}`, `{venue_name}`, `{total_amount}`

### Document Templates
- Quotation footer/terms, invoice footer, contract header text

---

## Themes

Path: `/admin/themes`

Select from available themes to change the look of the public-facing website. Each theme shows a color swatch preview.

---

## Plugins

Path: `/admin/plugins`

Enable/disable add-on plugins:
- **SMS Gateway** — sends SMS notifications to clients
- **Payment Gateway** — integrates online payment processing

---

## Custom Fields

Path: `/admin/custom-fields`

Add extra fields to bookings, clients, or venues:
- Field types: text, textarea, number, date, select, radio, checkbox, file
- For select/radio/checkbox: enter options one per line
- Toggle active/inactive without deleting

---

## Client Portal

Clients log in at `/portal` to view their bookings, quotations, and payments.
