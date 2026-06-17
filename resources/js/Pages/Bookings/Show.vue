<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ booking: Object, availableVendors: Array });

const cancelForm = useForm({ cancellation_reason: '' });
const showCancelModal = ref(false);
const showVendorForm = ref(false);
const vendorForm = useForm({ vendor_id: '', service_description: '', agreed_amount: '', notes: '' });

function attachVendor() {
    vendorForm.post(`/admin/bookings/${props.booking.id}/vendors`, {
        onSuccess: () => { showVendorForm.value = false; vendorForm.reset(); },
    });
}

function detachVendor(vendorId) {
    if (!window.confirm('Remove this vendor from the booking?')) return;
    router.delete(`/admin/bookings/${props.booking.id}/vendors/${vendorId}`, { preserveScroll: true });
}

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
    pending: 'bg-surface-sunken text-ink-muted',
    tentative: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-green-100 text-green-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-surface-sunken text-ink-muted',
    cancelled: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout tour="booking-show" :title="`Booking ${booking.booking_number}`">
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/bookings" class="text-ink-subtle hover:text-ink-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-ink">{{ booking.booking_number }}</h2>
                        <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[booking.status] ?? 'bg-surface-sunken']">
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
                    <div class="bg-surface rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-ink mb-3">Event Details</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-ink-subtle">Event Type</dt>
                                <dd class="font-medium capitalize">{{ booking.event?.type }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Event Date</dt>
                                <dd class="font-medium">{{ booking.event?.date }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Start Time</dt>
                                <dd>{{ booking.event?.start_time ?? 'â€”' }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">End Time</dt>
                                <dd>{{ booking.event?.end_time ?? 'â€”' }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Guest Count</dt>
                                <dd>{{ booking.event?.guest_count }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Setup Time</dt>
                                <dd>{{ booking.event?.setup_time ?? 'â€”' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Client & Venue -->
                    <div class="bg-surface rounded-lg shadow-sm p-5 grid grid-cols-2 gap-5">
                        <div>
                            <h3 class="text-sm font-semibold text-ink mb-2">Client</h3>
                            <p class="text-sm font-medium text-ink">{{ booking.client?.full_name }}</p>
                            <p class="text-sm text-ink-subtle">{{ booking.client?.email }}</p>
                            <p class="text-sm text-ink-subtle">{{ booking.client?.phone }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-ink mb-2">Venue</h3>
                            <p class="text-sm font-medium text-ink">{{ booking.venue?.name ?? 'â€”' }}</p>
                            <p class="text-sm text-ink-subtle" v-if="booking.package">{{ booking.package?.name }}</p>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div class="bg-surface rounded-lg shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-ink">Payment History</h3>
                            <Link :href="`/admin/bookings/${booking.id}/payments/create`" class="text-xs text-primary hover:underline">+ Add payment</Link>
                        </div>
                        <table class="w-full text-sm" v-if="booking.payments?.length">
                            <thead>
                                <tr class="text-left text-xs text-ink-subtle uppercase">
                                    <th class="pb-2">Payment #</th>
                                    <th class="pb-2">Installment</th>
                                    <th class="pb-2">Amount</th>
                                    <th class="pb-2">Date</th>
                                    <th class="pb-2">Status</th>
                                    <th class="pb-2 text-right">Receipt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="payment in booking.payments" :key="payment.id">
                                    <td class="py-2 font-medium">{{ payment.payment_number }}</td>
                                    <td class="py-2 text-ink-muted">{{ payment.installment_name }}</td>
                                    <td class="py-2 font-medium">LKR {{ payment.amount?.toLocaleString() }}</td>
                                    <td class="py-2 text-ink-muted">{{ payment.payment_date }}</td>
                                    <td class="py-2">
                                        <span :class="payment.status === 'completed' ? 'text-green-600' : 'text-yellow-600'" class="capitalize">
                                            {{ payment.status }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-right">
                                        <a v-if="payment.status === 'completed'" :href="`/admin/payments/${payment.id}/receipt`" class="text-primary hover:underline text-xs">Download</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-ink-subtle">No payments recorded yet.</p>
                    </div>

                    <!-- Vendors -->
                    <div class="bg-surface rounded-lg shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-ink">Vendors</h3>
                            <button @click="showVendorForm = !showVendorForm" class="text-xs text-primary hover:underline">
                                {{ showVendorForm ? 'Cancel' : '+ Assign Vendor' }}
                            </button>
                        </div>

                        <!-- Attach form -->
                        <div v-if="showVendorForm" class="mb-4 p-3 bg-surface-muted rounded-lg space-y-2">
                            <select v-model="vendorForm.vendor_id" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                                <option value="">Select vendor</option>
                                <option v-for="v in availableVendors" :key="v.id" :value="v.id">{{ v.name }} ({{ v.category }})</option>
                            </select>
                            <input v-model="vendorForm.service_description" type="text" placeholder="Service description"
                                class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                            <input v-model="vendorForm.agreed_amount" type="number" min="0" step="0.01" placeholder="Agreed amount ($)"
                                class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                            <button @click="attachVendor" :disabled="vendorForm.processing || !vendorForm.vendor_id"
                                class="w-full py-1.5 bg-primary hover:bg-primary-dark text-white text-sm rounded-md disabled:opacity-50">
                                Assign
                            </button>
                        </div>

                        <div v-if="booking.vendors?.length" class="space-y-2">
                            <div v-for="vendor in booking.vendors" :key="vendor.id" class="flex items-start justify-between text-sm py-2 border-t border-border">
                                <div>
                                    <p class="font-medium text-ink">{{ vendor.name }}</p>
                                    <p class="text-xs text-ink-subtle capitalize">{{ vendor.category }}</p>
                                    <p v-if="vendor.pivot?.service_description" class="text-xs text-ink-subtle mt-0.5">{{ vendor.pivot.service_description }}</p>
                                </div>
                                <div class="text-right">
                                    <p v-if="vendor.pivot?.agreed_amount" class="font-semibold text-ink">LKR {{ parseFloat(vendor.pivot.agreed_amount).toLocaleString() }}</p>
                                    <button @click="detachVendor(vendor.id)" class="text-xs text-red-500 hover:text-red-700 mt-1">Remove</button>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="!showVendorForm" class="text-sm text-ink-subtle">No vendors assigned yet.</p>
                    </div>
                </div>

                <!-- Financial summary sidebar -->
                <div class="space-y-4">
                    <div class="bg-surface rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-ink mb-3">Financial Summary</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-ink-subtle">Total Amount</dt>
                                <dd class="font-semibold">LKR {{ booking.financial?.total?.toLocaleString() }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-ink-subtle">Paid</dt>
                                <dd class="text-green-600 font-medium">LKR {{ booking.financial?.paid?.toLocaleString() }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-border pt-2 mt-2">
                                <dt class="text-ink-muted font-medium">Balance Due</dt>
                                <dd :class="(booking.financial?.balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600'" class="font-bold">
                                    LKR {{ booking.financial?.balance?.toLocaleString() }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div v-if="booking.notes" class="bg-surface rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-ink mb-2">Notes</h3>
                        <p class="text-sm text-ink-muted whitespace-pre-line">{{ booking.notes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-surface rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-ink mb-4">Cancel Booking</h3>
                <p class="text-sm text-ink-subtle mb-4">This action cannot be undone. The booking will be marked as cancelled.</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-ink-muted mb-1">Reason (optional)</label>
                    <textarea v-model="cancelForm.cancellation_reason" rows="3"
                        class="w-full border-border rounded-md text-sm focus:border-red-500 focus:ring-red-500 resize-none"
                        placeholder="Reason for cancellation..."
                    />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="showCancelModal = false; cancelForm.reset();"
                        class="px-4 py-2 border border-border text-ink-muted text-sm rounded-lg hover:bg-surface-muted">
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
