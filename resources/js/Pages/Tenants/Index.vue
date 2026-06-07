<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ tenants: Object, filters: Object, statuses: Array, stats: Object });

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let timer;
watch([search, status], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/tenants',
            { search: search.value, status: status.value },
            { preserveState: true, replace: true });
    }, 300);
});

function destroy(tenant) {
    if (!window.confirm(`Delete ${tenant.name}? This removes the tenant and its users. This cannot be undone.`)) return;
    router.delete(`/admin/tenants/${tenant.id}`, { preserveScroll: true });
}

const statusColors = {
    active:    'bg-green-100 text-green-700',
    trial:     'bg-blue-100 text-blue-700',
    suspended: 'bg-red-100 text-red-700',
};
</script>

<template>
    <AppLayout title="Tenants">
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Tenants</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage the organizations on the platform.</p>
                </div>
                <Link href="/admin/tenants/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Add Tenant
                </Link>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Active</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.active }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Trial</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.trial }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Suspended</p>
                    <p class="text-2xl font-bold text-red-500 mt-1">{{ stats.suspended }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name, slug or domain..."
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 min-w-[180px]" />
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="t in tenants.data" :key="t.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ t.name }}</p>
                                <p class="text-xs text-gray-500">{{ t.domain ?? t.slug }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ t.plan ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[t.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ t.status }}
                                </span>
                                <span v-if="t.status === 'trial' && t.trial_ends_at" class="block text-xs text-gray-400 mt-1">ends {{ t.trial_ends_at }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ t.users_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ t.bookings_count }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="`/admin/tenants/${t.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                <button @click="destroy(t)" class="text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!tenants.data?.length">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No tenants found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="tenants.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ tenants.from }}–{{ tenants.to }} of {{ tenants.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="tenants.prev_page_url" :href="tenants.prev_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="tenants.next_page_url" :href="tenants.next_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
