<script setup>
import { Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Card, StatusBadge } from '@/Components/ui';

defineProps({ booking: Object });
</script>

<template>
    <PortalLayout :title="`Booking ${booking.booking_number}`">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/portal/bookings" class="text-ink-subtle hover:text-ink">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div>
                    <h2 class="font-display text-xl font-semibold text-ink">{{ booking.booking_number }}</h2>
                    <StatusBadge :status="booking.status" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 space-y-4">
                    <!-- Event details -->
                    <Card>
                        <h3 class="text-sm font-semibold text-ink mb-3">Event Details</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-ink-subtle">Date</dt><dd class="font-medium text-ink">{{ booking.event?.date }}</dd></div>
                            <div><dt class="text-ink-subtle">Type</dt><dd class="font-medium text-ink capitalize">{{ booking.event?.type }}</dd></div>
                            <div><dt class="text-ink-subtle">Guests</dt><dd class="text-ink">{{ booking.event?.guest_count }}</dd></div>
                            <div><dt class="text-ink-subtle">Venue</dt><dd class="font-medium text-ink">{{ booking.venue?.name }}</dd></div>
                            <div v-if="booking.event?.start_time"><dt class="text-ink-subtle">Start</dt><dd class="text-ink">{{ booking.event.start_time }}</dd></div>
                            <div v-if="booking.event?.end_time"><dt class="text-ink-subtle">End</dt><dd class="text-ink">{{ booking.event.end_time }}</dd></div>
                        </dl>
                    </Card>

                    <!-- Payment history -->
                    <Card>
                        <h3 class="text-sm font-semibold text-ink mb-3">Payment History</h3>
                        <table class="w-full text-sm" v-if="booking.payments?.length">
                            <thead>
                                <tr class="text-left text-xs text-ink-subtle uppercase">
                                    <th class="pb-2">Payment #</th>
                                    <th class="pb-2">Amount</th>
                                    <th class="pb-2">Date</th>
                                    <th class="pb-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="p in booking.payments" :key="p.id">
                                    <td class="py-2 font-medium text-ink">{{ p.payment_number }}</td>
                                    <td class="py-2 text-ink">LKR {{ p.amount?.toLocaleString() }}</td>
                                    <td class="py-2 text-ink-muted">{{ p.payment_date }}</td>
                                    <td class="py-2"><StatusBadge :status="p.status" /></td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-ink-subtle">No payments yet.</p>
                    </Card>
                </div>

                <!-- Financial sidebar -->
                <Card>
                    <h3 class="text-sm font-semibold text-ink mb-3">Financial Summary</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-ink-subtle">Total</dt>
                            <dd class="font-semibold text-ink">LKR {{ booking.financial?.total?.toLocaleString() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-subtle">Paid</dt>
                            <dd class="text-success font-medium">LKR {{ booking.financial?.paid?.toLocaleString() }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-border pt-2">
                            <dt class="font-medium text-ink-muted">Balance</dt>
                            <dd :class="(booking.financial?.balance ?? 0) > 0 ? 'text-danger' : 'text-success'" class="font-bold">
                                LKR {{ booking.financial?.balance?.toLocaleString() }}
                            </dd>
                        </div>
                    </dl>
                    <p v-if="booking.notes" class="mt-4 pt-4 border-t border-border text-xs text-ink-muted whitespace-pre-line">{{ booking.notes }}</p>
                </Card>
            </div>
        </div>
    </PortalLayout>
</template>
