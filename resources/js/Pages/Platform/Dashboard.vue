<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ stats: Object, recentTenants: Array, planDistribution: Array });

const money = (v) => '$' + Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 0 });

const statusColors = {
    active:    'bg-green-100 text-green-700',
    trial:     'bg-blue-100 text-blue-700',
    suspended: 'bg-red-100 text-red-700',
};
</script>

<template>
    <AppLayout title="Platform">
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900">Platform Overview</h2>
                <p class="text-gray-500 mt-1 text-sm">Activity across every tenant on the platform.</p>
            </div>

            <!-- Primary KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Tenants</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.tenants_total }}</p>
                    <p class="text-xs text-gray-400 mt-1">+{{ stats.tenants_new }} this month</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">MRR</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ money(stats.mrr) }}</p>
                    <p class="text-xs text-gray-400 mt-1">monthly recurring</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Revenue (Month)</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ money(stats.revenue_month) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ money(stats.revenue_all_time) }} all time</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Bookings</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.bookings_total }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ stats.users_total }} users</p>
                </div>
            </div>

            <!-- Tenant status breakdown -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ stats.tenants_active }}</p>
                    <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Active</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ stats.tenants_trial }}</p>
                    <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Trial</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-red-500">{{ stats.tenants_suspended }}</p>
                    <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Suspended</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Recent tenants -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Recent Tenants</h3>
                        <Link href="/admin/tenants" class="text-sm text-indigo-600 hover:text-indigo-800">View all</Link>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="t in recentTenants" :key="t.id" class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <Link :href="`/admin/tenants/${t.id}/edit`" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ t.name }}</Link>
                                    <p class="text-xs text-gray-400">{{ t.plan ?? 'No plan' }} · {{ t.created_at }}</p>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ t.users_count }} users</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ t.bookings_count }} bookings</td>
                                <td class="px-5 py-3">
                                    <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[t.status] ?? 'bg-gray-100 text-gray-600']">{{ t.status }}</span>
                                </td>
                            </tr>
                            <tr v-if="!recentTenants.length">
                                <td colspan="4" class="px-5 py-10 text-center text-gray-400 text-sm">No tenants yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Plan distribution -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">Plan Distribution</h3>
                    <ul class="space-y-3">
                        <li v-for="p in planDistribution" :key="p.name" class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ p.name }}</span>
                            <span class="font-semibold text-gray-900">{{ p.count }}</span>
                        </li>
                        <li v-if="!planDistribution.length" class="text-sm text-gray-400">No plans configured.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
