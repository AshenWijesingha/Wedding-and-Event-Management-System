<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ venueOccupancy: Array, monthlyOccupancy: Array, filters: Object, years: Array });

const year = computed({
    get: () => props.filters.year,
    set: (val) => router.get('/admin/reports/occupancy', { year: val }, { preserveState: true, replace: true }),
});

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
                <select v-model="year" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Monthly occupancy bar chart -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Occupancy Rate — {{ year }}</h3>
                    <div class="flex items-end gap-2 h-48">
                        <div v-for="m in monthlyOccupancy" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
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
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Venue Occupancy — {{ year }}</h3>
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
                        <tr v-for="m in monthlyOccupancy" :key="m.month" :class="m.bookings > 0 ? '' : 'text-gray-400'">
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
