<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ plans: Array });

function destroy(plan) {
    if (!window.confirm(`Delete the ${plan.name} plan?`)) return;
    router.delete(`/admin/plans/${plan.id}`, { preserveScroll: true });
}

const money = (v) => 'LKR ' + Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 0 });
</script>

<template>
    <AppLayout title="Plans">
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Plans</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Subscription tiers offered to tenants.</p>
                </div>
                <Link href="/admin/plans/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Add Plan
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="plan in plans" :key="plan.id" class="bg-white rounded-lg shadow-sm p-5 flex flex-col">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ plan.name }}</h3>
                            <p class="text-xs text-gray-400">{{ plan.slug }}</p>
                        </div>
                        <span :class="plan.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium">
                            {{ plan.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <span class="text-2xl font-bold text-gray-900">{{ money(plan.price_monthly) }}</span>
                        <span class="text-sm text-gray-500">/mo</span>
                        <span class="text-sm text-gray-400 ml-2">Â· {{ money(plan.price_yearly) }}/yr</span>
                    </div>

                    <p v-if="plan.description" class="text-sm text-gray-500 mt-2">{{ plan.description }}</p>

                    <ul v-if="plan.features?.length" class="mt-3 space-y-1 flex-1">
                        <li v-for="(f, i) in plan.features" :key="i" class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ f }}
                        </li>
                    </ul>
                    <div v-else class="flex-1"></div>

                    <div v-if="Object.keys(plan.limits || {}).length" class="mt-3 flex flex-wrap gap-2">
                        <span v-for="(v, k) in plan.limits" :key="k" class="inline-flex px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-xs capitalize">
                            {{ k }}: {{ v }}
                        </span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-sm">
                        <span class="text-gray-400">{{ plan.tenants_count }} tenant(s)</span>
                        <div>
                            <Link :href="`/admin/plans/${plan.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                            <button @click="destroy(plan)" class="text-red-500 hover:text-red-700">Delete</button>
                        </div>
                    </div>
                </div>

                <div v-if="!plans.length" class="col-span-full bg-white rounded-lg shadow-sm p-12 text-center text-gray-400 text-sm">
                    No plans yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
