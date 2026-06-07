<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ReportFilter from '@/Components/ReportFilter.vue';

const props = defineProps({ venueOccupancy: Array, monthlyOccupancy: Array, filters: Object, years: Array });

const period = computed(() => props.filters.label);
const query = computed(() => props.filters.from && props.filters.to
    ? `from=${props.filters.from}&to=${props.filters.to}`
    : `year=${props.filters.year}`);

const maxPct = computed(() => Math.max(...props.monthlyOccupancy.map(m => m.occupancy_pct), 1));
</script>

<template>
    <AppLayout title="Occupancy Report">
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/reports" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-900">Occupancy Report</h2>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`/admin/reports/occupancy/export?${query}`"
                        class="inline-flex items-center gap-2 border border-gray-300 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        CSV
                    </a>
                    <a :href="`/admin/reports/occupancy/pdf?${query}`"
                        class="inline-flex items-center gap-2 border border-gray-300 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF
                    </a>
                </div>
            </div>

            <ReportFilter path="/admin/reports/occupancy" :filters="filters" :years="years" />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Monthly occupancy bar chart -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Occupancy Rate — {{ period }}</h3>
                    <div class="flex items-end gap-2 h-48">
                        <div v-for="m in monthlyOccupancy" :key="m.label" class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-xs text-gray-500">{{ m.occupancy_pct > 0 ? m.occupancy_pct + '%' : '' }}</span>
                            <div class="w-full bg-purple-500 rounded-t hover:bg-purple-600 transition-colors"
                                :style="{ height: Math.max(4, (m.occupancy_pct / maxPct) * 160) + 'px' }"
                                :title="m.bookings + ' bookings (' + m.occupancy_pct + '%)'"></div>
                            <span class="text-xs text-gray-500">{{ m.label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Venue occupancy table -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Venue Occupancy — {{ period }}</h3>
                    <div v-if="venueOccupancy.length" class="space-y-3">
                        <div v-for="v in venueOccupancy" :key="v.venue">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-900 truncate">{{ v.venue }}</span>
                                <span class="text-gray-500 ml-2 shrink-0">{{ v.booked_days }} days ({{ v.occupancy_pct }}%)</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-2 bg-purple-500 rounded-full" :style="{ width: v.occupancy_pct + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No venue data.</p>
                </div>
            </div>

            <!-- Monthly table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bookings</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Occupancy %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="m in monthlyOccupancy" :key="m.label" :class="m.bookings > 0 ? '' : 'text-gray-400'">
                            <td class="px-6 py-3">{{ m.label }}</td>
                            <td class="px-6 py-3 font-semibold">{{ m.bookings }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-1.5 bg-purple-500 rounded-full" :style="{ width: m.occupancy_pct + '%' }"></div>
                                    </div>
                                    <span>{{ m.occupancy_pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
