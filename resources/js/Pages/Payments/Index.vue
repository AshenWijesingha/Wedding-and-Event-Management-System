<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payments: Object,
    filters: Object,
    summary: Object,
});

const status = ref(props.filters?.status ?? '');
const method = ref(props.filters?.method ?? '');

watch([status, method], () => {
    router.get('/admin/payments', { status: status.value, method: method.value }, { preserveState: true, replace: true });
});

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-600',
    refunded: 'bg-orange-100 text-orange-700',
};
</script>

<template>
    <AppLayout title="Payments">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Payments</h2>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-sm text-gray-500">Total Received</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">LKR {{ summary?.total?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-sm text-gray-500">This Month</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">LKR {{ summary?.this_month?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">LKR {{ summary?.pending?.toLocaleString() ?? 0 }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex gap-3">
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
                <select v-model="method" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All methods</option>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="cheque">Cheque</option>
                    <option value="online">Online</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Installment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ payment.payment_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ payment.installment_name }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">LKR {{ payment.amount?.toLocaleString() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ payment.payment_method?.replace('_', ' ') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ payment.payment_date }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[payment.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ payment.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!payments.data?.length">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No payments found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="payments.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ payments.meta.from }}â€“{{ payments.meta.to }} of {{ payments.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="payments.links?.prev" :href="payments.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="payments.links?.next" :href="payments.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
