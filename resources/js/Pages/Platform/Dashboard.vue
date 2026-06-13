<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ stats: Object, recentTenants: Array, planDistribution: Array });

const money = (v) => 'LKR ' + Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 0 });

const statusColors = {
    active:    'bg-green-100 text-green-700',
    trial:     'bg-blue-100 text-blue-700',
    suspended: 'bg-red-100 text-red-700',
};
</script>

<template>
    <AppLayout title="Platform">
        <div class="space-y-6">
            <div class="bg-surface rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-ink">Platform Overview</h2>
                <p class="text-ink-subtle mt-1 text-sm">Activity across every tenant on the platform.</p>
            </div>

            <!-- Primary KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface rounded-lg shadow-sm p-5">
                    <p class="text-sm text-ink-subtle">Tenants</p>
                    <p class="text-2xl font-bold text-ink mt-1">{{ stats.tenants_total }}</p>
                    <p class="text-xs text-ink-subtle mt-1">+{{ stats.tenants_new }} this month</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-5">
                    <p class="text-sm text-ink-subtle">MRR</p>
                    <p class="text-2xl font-bold text-primary mt-1">{{ money(stats.mrr) }}</p>
                    <p class="text-xs text-ink-subtle mt-1">monthly recurring</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-5">
                    <p class="text-sm text-ink-subtle">Revenue (Month)</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ money(stats.revenue_month) }}</p>
                    <p class="text-xs text-ink-subtle mt-1">{{ money(stats.revenue_all_time) }} all time</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-5">
                    <p class="text-sm text-ink-subtle">Bookings</p>
                    <p class="text-2xl font-bold text-ink mt-1">{{ stats.bookings_total }}</p>
                    <p class="text-xs text-ink-subtle mt-1">{{ stats.users_total }} users</p>
                </div>
            </div>

            <!-- Tenant status breakdown -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-surface rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ stats.tenants_active }}</p>
                    <p class="text-xs text-ink-subtle uppercase tracking-wide mt-1">Active</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ stats.tenants_trial }}</p>
                    <p class="text-xs text-ink-subtle uppercase tracking-wide mt-1">Trial</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-red-500">{{ stats.tenants_suspended }}</p>
                    <p class="text-xs text-ink-subtle uppercase tracking-wide mt-1">Suspended</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Recent tenants -->
                <div class="lg:col-span-2 bg-surface rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                        <h3 class="font-semibold text-ink">Recent Tenants</h3>
                        <Link href="/admin/tenants" class="text-sm text-primary hover:text-primary-dark">View all</Link>
                    </div>
                    <table class="min-w-full divide-y divide-border">
                        <tbody class="divide-y divide-border">
                            <tr v-for="t in recentTenants" :key="t.id" class="hover:bg-surface-muted">
                                <td class="px-5 py-3">
                                    <Link :href="`/admin/tenants/${t.id}/edit`" class="text-sm font-medium text-ink hover:text-primary">{{ t.name }}</Link>
                                    <p class="text-xs text-ink-subtle">{{ t.plan ?? 'No plan' }} Â· {{ t.created_at }}</p>
                                </td>
                                <td class="px-5 py-3 text-sm text-ink-subtle">{{ t.users_count }} users</td>
                                <td class="px-5 py-3 text-sm text-ink-subtle">{{ t.bookings_count }} bookings</td>
                                <td class="px-5 py-3">
                                    <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[t.status] ?? 'bg-surface-sunken text-ink-muted']">{{ t.status }}</span>
                                </td>
                            </tr>
                            <tr v-if="!recentTenants.length">
                                <td colspan="4" class="px-5 py-10 text-center text-ink-subtle text-sm">No tenants yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Plan distribution -->
                <div class="bg-surface rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-ink mb-4">Plan Distribution</h3>
                    <ul class="space-y-3">
                        <li v-for="p in planDistribution" :key="p.name" class="flex items-center justify-between text-sm">
                            <span class="text-ink-muted">{{ p.name }}</span>
                            <span class="font-semibold text-ink">{{ p.count }}</span>
                        </li>
                        <li v-if="!planDistribution.length" class="text-sm text-ink-subtle">No plans configured.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
