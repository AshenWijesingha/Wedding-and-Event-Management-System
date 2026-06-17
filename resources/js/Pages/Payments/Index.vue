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
    <AppLayout tour="payments" title="Payments">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-ink">Payments</h2>
            </div>

            <!-- Summary cards -->
            <div data-tour="payments.summary" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-sm text-ink-subtle">Total Received</p>
                    <p class="text-2xl font-bold text-ink mt-1">LKR {{ summary?.total?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-sm text-ink-subtle">This Month</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">LKR {{ summary?.this_month?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-sm text-ink-subtle">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">LKR {{ summary?.pending?.toLocaleString() ?? 0 }}</p>
                </div>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4 flex gap-3">
                <select v-model="status" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
                <select v-model="method" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All methods</option>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="cheque">Cheque</option>
                    <option value="online">Online</option>
                </select>
            </div>

            <div data-tour="payments.list" class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Payment #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Installment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4 text-sm font-medium text-ink">{{ payment.payment_number }}</td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ payment.installment_name }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-ink">LKR {{ payment.amount?.toLocaleString() }}</td>
                            <td class="px-6 py-4 text-sm text-ink-muted capitalize">
                                {{ payment.payment_method?.replace('_', ' ') }}
                                <span v-if="payment.gateway" class="ml-1 inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-primary/5 text-primary uppercase">{{ payment.gateway }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ payment.payment_date }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[payment.status] ?? 'bg-surface-sunken text-ink-muted']">
                                    {{ payment.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                <Link :href="`/admin/payments/${payment.id}`" class="text-ink-subtle hover:text-ink-muted mr-3">View</Link>
                                <a v-if="payment.status === 'completed'" :href="`/admin/payments/${payment.id}/receipt`" class="text-primary hover:text-primary-dark">Receipt</a>
                            </td>
                        </tr>
                        <tr v-if="!payments.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-ink-subtle text-sm">No payments found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="payments.meta?.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ payments.meta.from }}â€“{{ payments.meta.to }} of {{ payments.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="payments.links?.prev" :href="payments.links.prev" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="payments.links?.next" :href="payments.links.next" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
