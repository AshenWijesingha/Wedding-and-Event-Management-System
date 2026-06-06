<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    inquiries: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const eventType = ref(props.filters?.event_type ?? '');

let t;
watch([search], () => { clearTimeout(t); t = setTimeout(applyFilters, 400); });
watch([status, eventType], applyFilters);

function applyFilters() {
    router.get('/admin/inquiries', { search: search.value, status: status.value, event_type: eventType.value }, {
        preserveState: true, replace: true,
    });
}

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-700',
    contacted: 'bg-blue-100 text-blue-700',
    qualified: 'bg-indigo-100 text-indigo-700',
    proposal_sent: 'bg-purple-100 text-purple-700',
    converted: 'bg-green-100 text-green-700',
    closed: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <AppLayout title="Inquiries">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Inquiries</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage incoming event inquiries</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Search by client name or email..." class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="contacted">Contacted</option>
                    <option value="qualified">Qualified</option>
                    <option value="proposal_sent">Proposal Sent</option>
                    <option value="converted">Converted</option>
                    <option value="closed">Closed</option>
                </select>
                <select v-model="eventType" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All event types</option>
                    <option value="wedding">Wedding</option>
                    <option value="corporate">Corporate</option>
                    <option value="birthday">Birthday</option>
                    <option value="anniversary">Anniversary</option>
                    <option value="gala">Gala</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inquiry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preferred Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="inquiry in inquiries.data" :key="inquiry.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ inquiry.inquiry_number }}</div>
                                <div class="text-xs text-gray-400">{{ inquiry.created_at }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ inquiry.client?.full_name }}</div>
                                <div class="text-xs text-gray-500">{{ inquiry.client?.email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 capitalize">{{ inquiry.event_type }}</div>
                                <div class="text-xs text-gray-500">{{ inquiry.guest_count }} guests</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ inquiry.preferred_date }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[inquiry.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ inquiry.status?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/admin/inquiries/${inquiry.id}`" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</Link>
                            </td>
                        </tr>
                        <tr v-if="!inquiries.data?.length">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No inquiries found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="inquiries.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ inquiries.meta.from }}–{{ inquiries.meta.to }} of {{ inquiries.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="inquiries.links?.prev" :href="inquiries.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="inquiries.links?.next" :href="inquiries.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
