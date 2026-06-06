<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    months: Object,
    bookingsByStatus: Object,
    topVenues: Array,
    totals: Object,
    filters: Object,
    years: Array,
});

const year = computed({
    get: () => props.filters.year,
    set: (val) => router.get('/admin/reports', { year: val }, { preserveState: true, replace: true }),
});

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
            <!-- Header + year filter -->
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Financial Dashboard</h2>
                <select v-model="year" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">${{ totals.revenue?.toLocaleString() ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ year }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Bookings</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ totals.bookings ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ totals.confirmed_bookings }} confirmed</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Outstanding</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">${{ totals.outstanding?.toLocaleString() ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">balance due</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Avg per Booking</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        ${{ totals.bookings > 0 ? Math.round(totals.revenue / totals.bookings).toLocaleString() : 0 }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ year }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Revenue bar chart -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Revenue — {{ year }}</h3>
                    <div class="flex items-end gap-2 h-48">
                        <div
                            v-for="(data, m) in months"
                            :key="m"
                            class="flex-1 flex flex-col items-center gap-1"
                        >
                            <span class="text-xs text-gray-500">{{ data.revenue > 0 ? '$' + Math.round(data.revenue / 1000) + 'k' : '' }}</span>
                            <div
                                class="w-full bg-indigo-500 rounded-t hover:bg-indigo-600 transition-colors"
                                :style="{ height: Math.max(4, (data.revenue / maxRevenue) * 160) + 'px' }"
                                :title="'$' + data.revenue.toLocaleString()"
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
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Top Venues — {{ year }}</h3>
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
                            <td class="py-2 text-gray-900 font-semibold">${{ row.revenue?.toLocaleString() }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-sm text-gray-400">No venue data yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
