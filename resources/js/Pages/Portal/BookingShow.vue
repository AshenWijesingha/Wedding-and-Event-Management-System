<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ booking: Object });

const statusColors = {
    tentative:  'bg-yellow-100 text-yellow-700',
    confirmed:  'bg-green-100 text-green-700',
    in_progress:'bg-blue-100 text-blue-700',
    completed:  'bg-gray-100 text-gray-600',
    cancelled:  'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout :title="`Booking ${booking.booking_number}`">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/portal/bookings" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">{{ booking.booking_number }}</h2>
                    <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[booking.status] ?? 'bg-gray-100']">
                        {{ booking.status?.replace('_', ' ') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 space-y-4">
                    <!-- Event details -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Event Details</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-gray-500">Date</dt><dd class="font-medium">{{ booking.event?.date }}</dd></div>
                            <div><dt class="text-gray-500">Type</dt><dd class="font-medium capitalize">{{ booking.event?.type }}</dd></div>
                            <div><dt class="text-gray-500">Guests</dt><dd>{{ booking.event?.guest_count }}</dd></div>
                            <div><dt class="text-gray-500">Venue</dt><dd class="font-medium">{{ booking.venue?.name }}</dd></div>
                            <div v-if="booking.event?.start_time"><dt class="text-gray-500">Start</dt><dd>{{ booking.event.start_time }}</dd></div>
                            <div v-if="booking.event?.end_time"><dt class="text-gray-500">End</dt><dd>{{ booking.event.end_time }}</dd></div>
                        </dl>
                    </div>

                    <!-- Payment history -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Payment History</h3>
                        <table class="w-full text-sm" v-if="booking.payments?.length">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="pb-2">Payment #</th>
                                    <th class="pb-2">Amount</th>
                                    <th class="pb-2">Date</th>
                                    <th class="pb-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="p in booking.payments" :key="p.id">
                                    <td class="py-2 font-medium">{{ p.payment_number }}</td>
                                    <td class="py-2">${{ p.amount?.toLocaleString() }}</td>
                                    <td class="py-2 text-gray-600">{{ p.payment_date }}</td>
                                    <td class="py-2">
                                        <span :class="p.status === 'completed' ? 'text-green-600' : 'text-yellow-600'" class="capitalize">{{ p.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-gray-400">No payments yet.</p>
                    </div>
                </div>

                <!-- Financial sidebar -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Financial Summary</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Total</dt>
                            <dd class="font-semibold">${{ booking.financial?.total?.toLocaleString() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Paid</dt>
                            <dd class="text-green-600 font-medium">${{ booking.financial?.paid?.toLocaleString() }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-gray-100 pt-2">
                            <dt class="font-medium text-gray-700">Balance</dt>
                            <dd :class="(booking.financial?.balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600'" class="font-bold">
                                ${{ booking.financial?.balance?.toLocaleString() }}
                            </dd>
                        </div>
                    </dl>
                    <p v-if="booking.notes" class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500 whitespace-pre-line">{{ booking.notes }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
