<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    booking: Object,
});

const form = useForm({
    amount: props.booking.balance_amount,
    payment_method: 'cash',
    payment_date: new Date().toISOString().slice(0, 10),
    installment_name: '',
    reference_number: '',
    notes: '',
    allow_overpay: false,
});

const submit = () => {
    form.post(`/admin/bookings/${props.booking.id}/payments`);
};
</script>

<template>
    <AppLayout title="Record Payment">
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-ink">Record Payment</h2>
                <Link :href="`/admin/bookings/${booking.id}`" class="text-sm text-ink-subtle hover:text-ink-muted">&larr; Back to booking</Link>
            </div>

            <!-- Booking summary -->
            <div class="bg-surface rounded-lg shadow-sm p-4 grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xs text-ink-subtle uppercase">Total</p>
                    <p class="text-lg font-semibold text-ink">LKR {{ booking.total_amount.toLocaleString() }}</p>
                </div>
                <div>
                    <p class="text-xs text-ink-subtle uppercase">Paid</p>
                    <p class="text-lg font-semibold text-green-600">LKR {{ booking.paid_amount.toLocaleString() }}</p>
                </div>
                <div>
                    <p class="text-xs text-ink-subtle uppercase">Balance</p>
                    <p class="text-lg font-semibold text-red-600">LKR {{ booking.balance_amount.toLocaleString() }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-surface rounded-lg shadow-sm p-6 space-y-4">
                <p class="text-sm text-ink-subtle">{{ booking.booking_number }} &middot; {{ booking.client_name }}</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Amount (LKR)</label>
                        <input v-model="form.amount" type="number" step="0.01" min="0.01"
                               class="w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.amount" class="text-xs text-red-600 mt-1">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Method</label>
                        <select v-model="form.payment_method"
                                class="w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Date</label>
                        <input v-model="form.payment_date" type="date"
                               class="w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Installment (optional)</label>
                        <input v-model="form.installment_name" type="text" placeholder="e.g. Deposit"
                               class="w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-ink-muted mb-1">Reference (optional)</label>
                        <input v-model="form.reference_number" type="text"
                               class="w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-ink-muted mb-1">Notes (optional)</label>
                        <textarea v-model="form.notes" rows="2"
                                  class="w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-ink-muted">
                    <input v-model="form.allow_overpay" type="checkbox" class="rounded border-border text-primary focus:ring-primary" />
                    Allow payment to exceed the outstanding balance
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <Link :href="`/admin/bookings/${booking.id}`" class="px-4 py-2 text-sm text-ink-muted hover:text-ink">Cancel</Link>
                    <button type="submit" :disabled="form.processing"
                            class="bg-primary text-white text-sm font-medium px-5 py-2 rounded-md hover:bg-primary-dark disabled:opacity-50">
                        Record Payment
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
