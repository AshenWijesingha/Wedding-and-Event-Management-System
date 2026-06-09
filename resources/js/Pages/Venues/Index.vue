<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    venues: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let searchTimeout;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch(status, () => applyFilters());

function applyFilters() {
    router.get('/admin/venues', { search: search.value, status: status.value }, {
        preserveState: true,
        replace: true,
    });
}

function deleteVenue(slug) {
    if (!confirm('Delete this venue? This cannot be undone.')) return;
    router.delete(`/admin/venues/${slug}`, { preserveScroll: true });
}

const statusColors = {
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-gray-100 text-gray-600',
    maintenance: 'bg-yellow-100 text-yellow-700',
};
</script>

<template>
    <AppLayout title="Venues">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Venues</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage your event venues</p>
                </div>
                <Link href="/admin/venues/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Venue
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search venues..."
                    class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <select
                    v-model="status"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="venue in venues.data" :key="venue.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ venue.name }}</div>
                                <div class="text-sm text-gray-500">{{ venue.slug }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ venue.capacity?.min ?? 'â€”' }} â€“ {{ venue.capacity?.max ?? 'â€”' }} guests
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                LKR {{ venue.pricing?.base_price?.toLocaleString() ?? 'â€”' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[venue.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ venue.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link :href="`/admin/venues/${venue.slug}/availability`" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Calendar</Link>
                                    <Link :href="`/admin/venues/${venue.slug}/edit`" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</Link>
                                    <button @click="deleteVenue(venue.slug)" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!venues.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                                No venues found.
                                <Link href="/admin/venues/create" class="text-indigo-600 hover:underline ml-1">Add one now.</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="venues.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ venues.meta.from }}â€“{{ venues.meta.to }} of {{ venues.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link
                            v-if="venues.links?.prev"
                            :href="venues.links.prev"
                            class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50"
                        >Previous</Link>
                        <Link
                            v-if="venues.links?.next"
                            :href="venues.links.next"
                            class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50"
                        >Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
