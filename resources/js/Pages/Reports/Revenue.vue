<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ReportFilter from '@/Components/ReportFilter.vue';

const props = defineProps({ months: Array, byMethod: Array, totals: Object, filters: Object, years: Array });

const period = computed(() => props.filters.label);
const query = computed(() => props.filters.from && props.filters.to
    ? `from=${props.filters.from}&to=${props.filters.to}`
    : `year=${props.filters.year}`);

const maxRevenue = computed(() => Math.max(...props.months.map(m => m.revenue), 1));
</script>

<template>
    <AppLayout title="Revenue Report">
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/reports" class="text-ink-subtle hover:text-ink-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <h2 class="text-xl font-semibold text-ink">Revenue Report</h2>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`/admin/reports/revenue/export?${query}`"
                        class="inline-flex items-center gap-2 border border-border rounded-md px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        CSV
                    </a>
                    <a :href="`/admin/reports/revenue/pdf?${query}`"
                        class="inline-flex items-center gap-2 border border-border rounded-md px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF
                    </a>
                </div>
            </div>

            <ReportFilter path="/admin/reports/revenue" :filters="filters" :years="years" />

            <!-- KPI cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-xs text-ink-subtle uppercase">Collected</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">LKR {{ totals.collected?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-xs text-ink-subtle uppercase">Outstanding</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">LKR {{ totals.outstanding?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-xs text-ink-subtle uppercase">Refunded</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">LKR {{ totals.refunded?.toLocaleString() ?? 0 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Monthly bar chart -->
                <div class="lg:col-span-2 bg-surface rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-ink mb-4">Revenue â€” {{ period }}</h3>
                    <div class="flex items-end gap-2 h-48">
                        <div v-for="m in months" :key="m.label" class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-xs text-ink-subtle">{{ m.revenue > 0 ? 'LKR ' + Math.round(m.revenue / 1000) + 'k' : '' }}</span>
                            <div class="w-full bg-green-500 rounded-t hover:bg-green-600 transition-colors"
                                :style="{ height: Math.max(4, (m.revenue / maxRevenue) * 160) + 'px' }"
                                :title="'LKR ' + m.revenue.toLocaleString() + ' (' + m.count + ' payments)'"></div>
                            <span class="text-xs text-ink-subtle">{{ m.label.slice(0, 3) }}</span>
                        </div>
                    </div>
                </div>

                <!-- By payment method -->
                <div class="bg-surface rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-ink mb-4">By Payment Method</h3>
                    <div class="space-y-3">
                        <div v-for="m in byMethod" :key="m.method" class="text-sm">
                            <div class="flex justify-between mb-1">
                                <span class="capitalize text-ink-muted">{{ m.method?.replace('_', ' ') }}</span>
                                <span class="font-semibold text-ink">LKR {{ m.total.toLocaleString() }}</span>
                            </div>
                            <p class="text-xs text-ink-subtle">{{ m.count }} transactions</p>
                        </div>
                        <p v-if="!byMethod.length" class="text-sm text-ink-subtle">No payment data.</p>
                    </div>
                </div>
            </div>

            <!-- Monthly table -->
            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full text-sm divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Payments</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="m in months" :key="m.label" :class="m.revenue > 0 ? '' : 'text-ink-subtle'">
                            <td class="px-6 py-3">{{ m.label }}</td>
                            <td class="px-6 py-3 font-semibold">LKR {{ m.revenue.toLocaleString() }}</td>
                            <td class="px-6 py-3">{{ m.count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
