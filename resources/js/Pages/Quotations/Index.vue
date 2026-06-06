<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    quotations: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let t;
watch(search, () => { clearTimeout(t); t = setTimeout(applyFilters, 400); });
watch(status, applyFilters);

function applyFilters() {
    router.get('/admin/quotations', { search: search.value, status: status.value }, { preserveState: true, replace: true });
}

const statusColors = {
    draft: 'bg-gray-100 text-gray-600',
    sent: 'bg-blue-100 text-blue-700',
    viewed: 'bg-indigo-100 text-indigo-700',
    accepted: 'bg-green-100 text-green-700',
    declined: 'bg-red-100 text-red-600',
    expired: 'bg-orange-100 text-orange-700',
    cancelled: 'bg-gray-100 text-gray-500',
};
</script>

<template>
    <AppLayout title="Quotations">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Quotations</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage quotations and proposals</p>
                </div>
                <Link href="/admin/quotations/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Quotation
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Search by client or quotation number..." class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="viewed">Viewed</option>
                    <option value="accepted">Accepted</option>
                    <option value="declined">Declined</option>
                    <option value="expired">Expired</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quotation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="q in quotations.data" :key="q.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ q.quotation_number }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ q.client?.full_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ q.event_date }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">${{ q.financial?.total?.toLocaleString() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ q.valid_until }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[q.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ q.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link :href="`/admin/quotations/${q.id}`" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!quotations.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">No quotations found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="quotations.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ quotations.meta.from }}–{{ quotations.meta.to }} of {{ quotations.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="quotations.links?.prev" :href="quotations.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="quotations.links?.next" :href="quotations.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
