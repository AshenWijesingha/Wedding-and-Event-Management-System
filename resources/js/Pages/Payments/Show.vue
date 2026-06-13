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
                <h2 class="text-xl font-semibold text-gray-900">{{ payment.payment_number }}</h2>
                <Link href="/admin/payments" class="text-sm text-gray-500 hover:text-gray-700">&larr; All payments</Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-3xl font-bold text-gray-900">LKR {{ payment.amount?.toLocaleString() }}</span>
                    <span :class="['inline-flex px-3 py-1 rounded-full text-xs font-medium capitalize', statusColors[payment.status] ?? 'bg-gray-100 text-gray-600']">
                        {{ payment.status }}
                    </span>
                </div>

                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="flex justify-between py-2"><dt class="text-gray-500">Method</dt><dd class="font-medium capitalize">{{ payment.payment_method?.replace('_', ' ') }}</dd></div>
                    <div class="flex justify-between py-2"><dt class="text-gray-500">Date</dt><dd class="font-medium">{{ payment.payment_date ?? '—' }}</dd></div>
                    <div v-if="payment.installment_name" class="flex justify-between py-2"><dt class="text-gray-500">For</dt><dd class="font-medium">{{ payment.installment_name }}</dd></div>
                    <div v-if="payment.gateway" class="flex justify-between py-2"><dt class="text-gray-500">Gateway</dt><dd class="font-medium capitalize">{{ payment.gateway }}</dd></div>
                    <div v-if="payment.gateway_payment_id" class="flex justify-between py-2"><dt class="text-gray-500">Gateway Reference</dt><dd class="font-medium">{{ payment.gateway_payment_id }}</dd></div>
                    <div v-if="payment.reference_number" class="flex justify-between py-2"><dt class="text-gray-500">Reference</dt><dd class="font-medium">{{ payment.reference_number }}</dd></div>
                    <div v-if="payment.notes" class="flex justify-between py-2"><dt class="text-gray-500">Notes</dt><dd class="font-medium text-right max-w-xs">{{ payment.notes }}</dd></div>
                </dl>

                <div class="pt-2">
                    <a :href="`/admin/payments/${payment.id}/receipt`"
                       class="inline-flex items-center bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-700">
                        Download Receipt
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
