<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Card, StatCard, StatusBadge, Alert, Button } from '@/Components/ui';

const props = defineProps({
    payments: Object,
    summary: Object,
    payableBookings: { type: Array, default: () => [] },
    payhereEnabled: { type: Boolean, default: false },
});

const page = usePage();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// Per-booking amount inputs, defaulted to the full outstanding balance.
const amounts = ref(
    Object.fromEntries(props.payableBookings.map((b) => [b.id, b.balance_amount])),
);
</script>

<template>
    <PortalLayout title="My Payments">
        <div class="max-w-3xl mx-auto space-y-4">
            <h2 class="font-display text-xl font-semibold text-ink">My Payments</h2>

            <Alert v-if="page.props.flash?.info" variant="info">{{ page.props.flash.info }}</Alert>
            <Alert v-if="page.props.flash?.error" variant="error">{{ page.props.flash.error }}</Alert>

            <!-- Summary -->
            <div class="grid grid-cols-2 gap-4">
                <StatCard label="Total Paid" accent="success" :value="`LKR ${summary?.total_paid?.toLocaleString() ?? 0}`" />
                <StatCard label="Pending" accent="warning" :value="`LKR ${summary?.total_pending?.toLocaleString() ?? 0}`" />
            </div>

            <!-- Pay now -->
            <Card v-if="payhereEnabled && payableBookings.length" body-class="p-4 space-y-4">
                <h3 class="text-sm font-semibold text-ink">Outstanding Balances</h3>
                <div v-for="b in payableBookings" :key="b.id"
                     class="flex flex-wrap items-center justify-between gap-3 border border-border rounded-lg p-3">
                    <div>
                        <p class="text-sm font-medium text-ink">{{ b.booking_number }}</p>
                        <p class="text-xs text-ink-subtle">Balance: LKR {{ b.balance_amount.toLocaleString() }}</p>
                    </div>
                    <form method="POST" action="/portal/payments/initiate" class="flex items-center gap-2">
                        <input type="hidden" name="_token" :value="csrf" />
                        <input type="hidden" name="booking_id" :value="b.id" />
                        <span class="text-sm text-ink-subtle">LKR</span>
                        <input type="number" name="amount" v-model="amounts[b.id]" min="0.01" :max="b.balance_amount" step="0.01"
                               class="w-32 rounded-lg border-border px-2 py-1.5 text-sm focus:border-primary focus:ring-primary" />
                        <button type="submit" class="bg-primary text-white text-sm font-semibold px-4 py-1.5 rounded-lg hover:bg-primary-dark">Pay now</button>
                    </form>
                </div>
            </Card>

            <!-- Payments table -->
            <div class="bg-surface border border-border rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border" v-if="payments.data?.length">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-ink-subtle uppercase">Payment #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-ink-subtle uppercase">Booking</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-ink-subtle uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-ink-subtle uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-ink-subtle uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-ink-subtle uppercase">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-surface-muted">
                            <td class="px-4 py-3 text-sm font-medium text-ink">{{ p.payment_number }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ p.installment_name }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-ink">LKR {{ p.amount?.toLocaleString() }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ p.payment_date }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="p.status" /></td>
                            <td class="px-4 py-3 text-right">
                                <a v-if="p.status === 'completed'" :href="`/portal/payments/${p.id}/receipt`"
                                   class="text-primary hover:text-primary-dark text-sm font-medium">Download</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="p-12 text-center text-ink-subtle text-sm">No payments yet.</div>
            </div>
        </div>
    </PortalLayout>
</template>
