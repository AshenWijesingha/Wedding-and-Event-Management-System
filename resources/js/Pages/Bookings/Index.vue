<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    bookings: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let t;
watch(search, () => { clearTimeout(t); t = setTimeout(applyFilters, 400); });
watch(status, applyFilters);

function applyFilters() {
    router.get('/admin/bookings', { search: search.value, status: status.value }, { preserveState: true, replace: true });
}

const statusColors = {
    pending: 'bg-gray-100 text-gray-600',
    tentative: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-green-100 text-green-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-gray-100 text-gray-600',
    cancelled: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout title="Bookings">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Bookings</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage event bookings</p>
                </div>
                <Link v-if="can('bookings.create')" href="/admin/bookings/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Booking
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Search by booking number or client..." class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="tentative">Tentative</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Financials</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="booking in bookings.data" :key="booking.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-sm">{{ booking.booking_number }}</div>
                                <div class="text-xs text-gray-400">{{ booking.created_at }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ booking.client?.full_name }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 capitalize">{{ booking.event?.type }}</div>
                                <div class="text-xs text-gray-500">{{ booking.event?.date }} Â· {{ booking.event?.guest_count }} guests</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ booking.venue?.name ?? 'â€”' }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">LKR {{ booking.financial?.total?.toLocaleString() }}</div>
                                <div class="text-xs text-gray-500">Balance: LKR {{ booking.financial?.balance?.toLocaleString() }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[booking.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ booking.status?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/admin/bookings/${booking.id}`" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</Link>
                            </td>
                        </tr>
                        <tr v-if="!bookings.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">No bookings found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="bookings.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ bookings.meta.from }}â€“{{ bookings.meta.to }} of {{ bookings.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="bookings.links?.prev" :href="bookings.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="bookings.links?.next" :href="bookings.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
