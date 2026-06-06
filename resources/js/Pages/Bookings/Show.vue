<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ booking: Object });

const cancelForm = useForm({ cancellation_reason: '' });
const showCancelModal = ref(false);

function confirm() {
    if (!window.confirm('Confirm this booking?')) return;
    router.post(`/admin/bookings/${props.booking.id}/confirm`, {}, { preserveScroll: true });
}

function cancelBooking() {
    cancelForm.post(`/admin/bookings/${props.booking.id}/cancel`, {
        onSuccess: () => { showCancelModal.value = false; cancelForm.reset(); },
    });
}

const statusColors = {
    tentative: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-green-100 text-green-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-gray-100 text-gray-600',
    cancelled: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout :title="`Booking ${booking.booking_number}`">
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/bookings" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ booking.booking_number }}</h2>
                        <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[booking.status] ?? 'bg-gray-100']">
                            {{ booking.status?.replace('_', ' ') }}
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button v-if="booking.status === 'tentative'" @click="confirm"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                        Confirm Booking
                    </button>
                    <button v-if="!['completed', 'cancelled'].includes(booking.status)"
                        @click="showCancelModal = true"
                        class="px-4 py-2 border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium rounded-lg">
                        Cancel
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Main details -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Event details -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Event Details</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Event Type</dt>
                                <dd class="font-medium capitalize">{{ booking.event?.type }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Event Date</dt>
                                <dd class="font-medium">{{ booking.event?.date }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Start Time</dt>
                                <dd>{{ booking.event?.start_time ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">End Time</dt>
                                <dd>{{ booking.event?.end_time ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Guest Count</dt>
                                <dd>{{ booking.event?.guest_count }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Setup Time</dt>
                                <dd>{{ booking.event?.setup_time ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Client & Venue -->
                    <div class="bg-white rounded-lg shadow-sm p-5 grid grid-cols-2 gap-5">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Client</h3>
                            <p class="text-sm font-medium text-gray-900">{{ booking.client?.full_name }}</p>
                            <p class="text-sm text-gray-500">{{ booking.client?.email }}</p>
                            <p class="text-sm text-gray-500">{{ booking.client?.phone }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Venue</h3>
                            <p class="text-sm font-medium text-gray-900">{{ booking.venue?.name ?? '—' }}</p>
                            <p class="text-sm text-gray-500" v-if="booking.package">{{ booking.package?.name }}</p>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Payment History</h3>
                        </div>
                        <table class="w-full text-sm" v-if="booking.payments?.length">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="pb-2">Payment #</th>
                                    <th class="pb-2">Installment</th>
                                    <th class="pb-2">Amount</th>
                                    <th class="pb-2">Date</th>
                                    <th class="pb-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="payment in booking.payments" :key="payment.id">
                                    <td class="py-2 font-medium">{{ payment.payment_number }}</td>
                                    <td class="py-2 text-gray-600">{{ payment.installment_name }}</td>
                                    <td class="py-2 font-medium">${{ payment.amount?.toLocaleString() }}</td>
                                    <td class="py-2 text-gray-600">{{ payment.payment_date }}</td>
                                    <td class="py-2">
                                        <span :class="payment.status === 'completed' ? 'text-green-600' : 'text-yellow-600'" class="capitalize">
                                            {{ payment.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-gray-400">No payments recorded yet.</p>
                    </div>
                </div>

                <!-- Financial summary sidebar -->
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Financial Summary</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Total Amount</dt>
                                <dd class="font-semibold">${{ booking.financial?.total?.toLocaleString() }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Paid</dt>
                                <dd class="text-green-600 font-medium">${{ booking.financial?.paid?.toLocaleString() }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-gray-100 pt-2 mt-2">
                                <dt class="text-gray-700 font-medium">Balance Due</dt>
                                <dd :class="(booking.financial?.balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600'" class="font-bold">
                                    ${{ booking.financial?.balance?.toLocaleString() }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div v-if="booking.notes" class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Notes</h3>
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ booking.notes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancel Booking</h3>
                <p class="text-sm text-gray-500 mb-4">This action cannot be undone. The booking will be marked as cancelled.</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                    <textarea v-model="cancelForm.cancellation_reason" rows="3"
                        class="w-full border-gray-300 rounded-md text-sm focus:border-red-500 focus:ring-red-500 resize-none"
                        placeholder="Reason for cancellation..."
                    />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="showCancelModal = false; cancelForm.reset();"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                        Keep Booking
                    </button>
                    <button @click="cancelBooking" :disabled="cancelForm.processing"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                        Confirm Cancel
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
