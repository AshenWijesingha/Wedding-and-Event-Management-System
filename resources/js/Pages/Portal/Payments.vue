<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ payments: Object, summary: Object });

const statusColors = {
    pending:   'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    failed:    'bg-red-100 text-red-600',
    refunded:  'bg-orange-100 text-orange-700',
};
</script>

<template>
    <AppLayout title="My Payments">
        <div class="max-w-3xl mx-auto space-y-4">
            <h2 class="text-xl font-semibold text-gray-900">My Payments</h2>

            <!-- Summary -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">${{ summary?.total_paid?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">${{ summary?.total_pending?.toLocaleString() ?? 0 }}</p>
                </div>
            </div>

            <!-- Payments table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200" v-if="payments.data?.length">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ p.payment_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ p.installment_name }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">${{ p.amount?.toLocaleString() }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ p.payment_date }}</td>
                            <td class="px-4 py-3">
                                <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[p.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ p.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="p-12 text-center text-gray-400 text-sm">No payments yet.</div>
            </div>
        </div>
    </AppLayout>
</template>
