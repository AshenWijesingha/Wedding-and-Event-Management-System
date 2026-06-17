<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

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
    inactive: 'bg-surface-sunken text-ink-muted',
    maintenance: 'bg-yellow-100 text-yellow-700',
};
</script>

<template>
    <AppLayout tour="venues" title="Venues">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-ink">Venues</h2>
                    <p class="text-sm text-ink-subtle mt-0.5">Manage your event venues</p>
                </div>
                <Link v-if="can('venues.create')" href="/admin/venues/create" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Venue
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search venues..."
                    class="flex-1 border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
                <select
                    v-model="status"
                    class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Venue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Capacity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Base Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="venue in venues.data" :key="venue.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4">
                                <div class="font-medium text-ink">{{ venue.name }}</div>
                                <div class="text-sm text-ink-subtle">{{ venue.slug }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">
                                {{ venue.capacity?.min ?? 'â€”' }} â€“ {{ venue.capacity?.max ?? 'â€”' }} guests
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">
                                LKR {{ venue.pricing?.base_price?.toLocaleString() ?? 'â€”' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[venue.status] ?? 'bg-surface-sunken text-ink-muted']">
                                    {{ venue.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link :href="`/admin/venues/${venue.slug}/availability`" class="text-ink-subtle hover:text-ink-muted text-sm font-medium">Calendar</Link>
                                    <Link v-if="can('venues.edit')" :href="`/admin/venues/${venue.slug}/edit`" class="text-primary hover:text-primary-dark text-sm font-medium">Edit</Link>
                                    <button v-if="can('venues.delete')" @click="deleteVenue(venue.slug)" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!venues.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-ink-subtle text-sm">
                                No venues found.
                                <Link v-if="can('venues.create')" href="/admin/venues/create" class="text-primary hover:underline ml-1">Add one now.</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="venues.meta?.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ venues.meta.from }}â€“{{ venues.meta.to }} of {{ venues.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link
                            v-if="venues.links?.prev"
                            :href="venues.links.prev"
                            class="px-3 py-1 border border-border rounded hover:bg-surface-muted"
                        >Previous</Link>
                        <Link
                            v-if="venues.links?.next"
                            :href="venues.links.next"
                            class="px-3 py-1 border border-border rounded hover:bg-surface-muted"
                        >Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
