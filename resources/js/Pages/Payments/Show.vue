<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payment: Object,
});

const statusColors = {
    pending:   'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    failed:    'bg-red-100 text-red-600',
    refunded:  'bg-orange-100 text-orange-700',
};
</script>

<template>
    <AppLayout title="Payment">
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-ink">{{ payment.payment_number }}</h2>
                <Link href="/admin/payments" class="text-sm text-ink-subtle hover:text-ink-muted">&larr; All payments</Link>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-3xl font-bold text-ink">LKR {{ payment.amount?.toLocaleString() }}</span>
                    <span :class="['inline-flex px-3 py-1 rounded-full text-xs font-medium capitalize', statusColors[payment.status] ?? 'bg-surface-sunken text-ink-muted']">
                        {{ payment.status }}
                    </span>
                </div>

                <dl class="divide-y divide-border text-sm">
                    <div class="flex justify-between py-2"><dt class="text-ink-subtle">Method</dt><dd class="font-medium capitalize">{{ payment.payment_method?.replace('_', ' ') }}</dd></div>
                    <div class="flex justify-between py-2"><dt class="text-ink-subtle">Date</dt><dd class="font-medium">{{ payment.payment_date ?? '—' }}</dd></div>
                    <div v-if="payment.installment_name" class="flex justify-between py-2"><dt class="text-ink-subtle">For</dt><dd class="font-medium">{{ payment.installment_name }}</dd></div>
                    <div v-if="payment.gateway" class="flex justify-between py-2"><dt class="text-ink-subtle">Gateway</dt><dd class="font-medium capitalize">{{ payment.gateway }}</dd></div>
                    <div v-if="payment.gateway_payment_id" class="flex justify-between py-2"><dt class="text-ink-subtle">Gateway Reference</dt><dd class="font-medium">{{ payment.gateway_payment_id }}</dd></div>
                    <div v-if="payment.reference_number" class="flex justify-between py-2"><dt class="text-ink-subtle">Reference</dt><dd class="font-medium">{{ payment.reference_number }}</dd></div>
                    <div v-if="payment.notes" class="flex justify-between py-2"><dt class="text-ink-subtle">Notes</dt><dd class="font-medium text-right max-w-xs">{{ payment.notes }}</dd></div>
                </dl>

                <div class="pt-2">
                    <a :href="`/admin/payments/${payment.id}/receipt`"
                       class="inline-flex items-center bg-primary text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-primary-dark">
                        Download Receipt
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
