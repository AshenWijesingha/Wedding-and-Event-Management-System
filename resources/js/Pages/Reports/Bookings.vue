<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ months: Array, byEventType: Array, totals: Object, filters: Object, years: Array });

const year = computed({
    get: () => props.filters.year,
    set: (val) => router.get('/admin/reports/bookings', { year: val }, { preserveState: true, replace: true }),
});

const maxTotal = computed(() => Math.max(...props.months.map(m => m.total), 1));
</script>

<template>
    <AppLayout title="Booking Report">
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/reports" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-900">Booking Report</h2>
                </div>
                <select v-model="year" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Bookings</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ totals.total }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Confirmed</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ totals.confirmed }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Cancelled</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ totals.cancelled }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Revenue</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">${{ totals.revenue?.toLocaleString() ?? 0 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Monthly bar chart -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Monthly Bookings — {{ year }}</h3>
                    <div class="flex items-end gap-2 h-48">
                        <div v-for="m in months" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-xs text-gray-500">{{ m.total > 0 ? m.total : '' }}</span>
                            <div class="w-full bg-indigo-500 rounded-t hover:bg-indigo-600 transition-colors"
                                :style="{ height: Math.max(4, (m.total / maxTotal) * 160) + 'px' }"
                                :title="m.total + ' bookings'"></div>
                            <span class="text-xs text-gray-500">{{ m.label.slice(0, 3) }}</span>
                        </div>
                    </div>
                </div>

                <!-- By event type -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">By Event Type</h3>
                    <div class="space-y-3">
                        <div v-for="t in byEventType" :key="t.type" class="text-sm">
                            <div class="flex justify-between mb-0.5">
                                <span class="capitalize text-gray-600">{{ t.type }}</span>
                                <span class="font-semibold">{{ t.count }}</span>
                            </div>
                            <p class="text-xs text-gray-400">${{ t.revenue.toLocaleString() }}</p>
                        </div>
                        <p v-if="!byEventType.length" class="text-sm text-gray-400">No data.</p>
                    </div>
                </div>
            </div>

            <!-- Monthly table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Confirmed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cancelled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="m in months" :key="m.month" :class="m.total > 0 ? '' : 'text-gray-400'">
                            <td class="px-6 py-3">{{ m.label }}</td>
                            <td class="px-6 py-3 font-semibold">{{ m.total }}</td>
                            <td class="px-6 py-3 text-green-600">{{ m.confirmed }}</td>
                            <td class="px-6 py-3 text-indigo-600">{{ m.completed }}</td>
                            <td class="px-6 py-3 text-red-500">{{ m.cancelled }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
