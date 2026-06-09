<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ReportFilter from '@/Components/ReportFilter.vue';

const props = defineProps({
    months: Array,
    bookingsByStatus: Object,
    topVenues: Array,
    totals: Object,
    recentBookings: Array,
    upcomingBookings: Array,
    filters: Object,
    years: Array,
});

const period = computed(() => props.filters.label);

const maxRevenue = computed(() => {
    const vals = Object.values(props.months).map(m => m.revenue);
    return Math.max(...vals, 1);
});

const statusColors = {
    pending:    'bg-gray-100 text-gray-600',
    tentative:  'bg-yellow-100 text-yellow-700',
    confirmed:  'bg-green-100 text-green-700',
    in_progress:'bg-blue-100 text-blue-700',
    completed:  'bg-indigo-100 text-indigo-700',
    cancelled:  'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout title="Reports">
        <div class="space-y-5">
            <!-- Header + filters -->
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-900">Financial Dashboard</h2>
                <ReportFilter path="/admin/reports" :filters="filters" :years="years" />
            </div>

            <!-- Sub-report links -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <Link href="/admin/reports/revenue" class="bg-white rounded-lg shadow-sm p-4 flex items-center gap-3 hover:shadow-md transition-shadow">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Revenue Report</p>
                        <p class="text-xs text-gray-500">Collections, methods, trends</p>
                    </div>
                </Link>
                <Link href="/admin/reports/bookings" class="bg-white rounded-lg shadow-sm p-4 flex items-center gap-3 hover:shadow-md transition-shadow">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Booking Report</p>
                        <p class="text-xs text-gray-500">Volume, status, event types</p>
                    </div>
                </Link>
                <Link href="/admin/reports/occupancy" class="bg-white rounded-lg shadow-sm p-4 flex items-center gap-3 hover:shadow-md transition-shadow">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Occupancy Report</p>
                        <p class="text-xs text-gray-500">Venue utilization rates</p>
                    </div>
                </Link>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">LKR {{ totals.revenue?.toLocaleString() ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ period }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Bookings</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ totals.bookings ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ totals.confirmed_bookings }} confirmed</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Outstanding</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">LKR {{ totals.outstanding?.toLocaleString() ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">balance due</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Avg per Booking</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        LKR {{ totals.bookings > 0 ? Math.round(totals.revenue / totals.bookings).toLocaleString() : 0 }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ period }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Revenue bar chart -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Revenue â€” {{ period }}</h3>
                    <div class="flex items-end gap-2 h-48">
                        <div
                            v-for="(data, m) in months"
                            :key="m"
                            class="flex-1 flex flex-col items-center gap-1"
                        >
                            <span class="text-xs text-gray-500">{{ data.revenue > 0 ? 'LKR ' + Math.round(data.revenue / 1000) + 'k' : '' }}</span>
                            <div
                                class="w-full bg-indigo-500 rounded-t hover:bg-indigo-600 transition-colors"
                                :style="{ height: Math.max(4, (data.revenue / maxRevenue) * 160) + 'px' }"
                                :title="'LKR ' + data.revenue.toLocaleString()"
                            ></div>
                            <span class="text-xs text-gray-500">{{ data.label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Booking status breakdown -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Bookings by Status</h3>
                    <div class="space-y-2">
                        <div v-for="(count, status) in bookingsByStatus" :key="status" class="flex items-center justify-between">
                            <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[status] ?? 'bg-gray-100 text-gray-600']">
                                {{ status.replace('_', ' ') }}
                            </span>
                            <span class="text-sm font-semibold text-gray-900">{{ count }}</span>
                        </div>
                        <p v-if="!Object.keys(bookingsByStatus).length" class="text-sm text-gray-400">No bookings yet.</p>
                    </div>
                </div>
            </div>

            <!-- Top venues -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Top Venues â€” {{ period }}</h3>
                <table class="min-w-full text-sm" v-if="topVenues.length">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase">
                            <th class="pb-2 pr-4">Venue</th>
                            <th class="pb-2 pr-4">Bookings</th>
                            <th class="pb-2">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in topVenues" :key="row.venue">
                            <td class="py-2 pr-4 font-medium text-gray-900">{{ row.venue }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ row.bookings }}</td>
                            <td class="py-2 text-gray-900 font-semibold">LKR {{ row.revenue?.toLocaleString() }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-sm text-gray-400">No venue data yet.</p>
            </div>

            <!-- Recent + Upcoming bookings -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Recent Bookings</h3>
                    <div v-if="recentBookings?.length" class="space-y-2">
                        <div v-for="b in recentBookings" :key="b.booking_number" class="flex items-center justify-between py-2 border-b border-gray-100 text-sm">
                            <div>
                                <p class="font-medium text-gray-900">{{ b.booking_number }}</p>
                                <p class="text-xs text-gray-500">{{ b.client }} Â· {{ b.venue }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">LKR {{ b.total_amount?.toLocaleString() }}</p>
                                <p class="text-xs text-gray-500 capitalize">{{ b.status }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No recent bookings.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Upcoming Events</h3>
                    <div v-if="upcomingBookings?.length" class="space-y-2">
                        <div v-for="b in upcomingBookings" :key="b.booking_number" class="flex items-center justify-between py-2 border-b border-gray-100 text-sm">
                            <div>
                                <p class="font-medium text-gray-900">{{ b.event_date }}</p>
                                <p class="text-xs text-gray-500">{{ b.client }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium">{{ b.venue }}</p>
                                <p class="text-xs text-gray-500 capitalize">{{ b.status }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No upcoming events.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
