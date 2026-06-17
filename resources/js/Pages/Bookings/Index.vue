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
    pending: 'bg-surface-sunken text-ink-muted',
    tentative: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-green-100 text-green-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-surface-sunken text-ink-muted',
    cancelled: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout title="Bookings" tour="bookings">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-ink">Bookings</h2>
                    <p class="text-sm text-ink-subtle mt-0.5">Manage event bookings</p>
                </div>
                <Link v-if="can('bookings.create')" data-tour="bookings.new" href="/admin/bookings/create" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Booking
                </Link>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Search by booking number or client..." class="flex-1 border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                <select v-model="status" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="tentative">Tentative</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div data-tour="bookings.list" class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Booking</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Venue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Financials</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="booking in bookings.data" :key="booking.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4">
                                <div class="font-medium text-ink text-sm">{{ booking.booking_number }}</div>
                                <div class="text-xs text-ink-subtle">{{ booking.created_at }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ booking.client?.full_name }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-ink capitalize">{{ booking.event?.type }}</div>
                                <div class="text-xs text-ink-subtle">{{ booking.event?.date }} Â· {{ booking.event?.guest_count }} guests</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ booking.venue?.name ?? 'â€”' }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-ink">LKR {{ booking.financial?.total?.toLocaleString() }}</div>
                                <div class="text-xs text-ink-subtle">Balance: LKR {{ booking.financial?.balance?.toLocaleString() }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[booking.status] ?? 'bg-surface-sunken text-ink-muted']">
                                    {{ booking.status?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/admin/bookings/${booking.id}`" class="text-primary hover:text-primary-dark text-sm font-medium">View</Link>
                            </td>
                        </tr>
                        <tr v-if="!bookings.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-ink-subtle text-sm">No bookings found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="bookings.meta?.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ bookings.meta.from }}â€“{{ bookings.meta.to }} of {{ bookings.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="bookings.links?.prev" :href="bookings.links.prev" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="bookings.links?.next" :href="bookings.links.next" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
